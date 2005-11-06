#!/bin/sh

if [ -z $@ ]; then
    echo "Ei komentoa"
    exit 0
fi

function is_sudo_root() {
    if [ ! $(which whoami 2>/dev/null) ]; then
        echo "Ei 'whoami' ohjelmaa"
        exit 1
    fi
    WHOAMI=$(echo "" | sudo -S whoami)
    if [ "$WHOAMI" = "root" ]; then
        return 1
    else
        return 0
    fi
}

#poistaa XAUTH tiedoston jos jäänyt
function cleanup() {
    rm -f $XAUTHEXPORT
    unset XAUTHEXPORT
    unset ROOTHOME
    unset PASSWD
}

function sudo_xauth() {
    if [ -z $DISPLAY ]; then
        echo "Ei DISPLAY muuttujaa"
        exit 1
    elif [ ! $(which xauth 2>/dev/null) ]; then
        echo "Ei xauth ohjelmaa"
        exit 1
    fi

    XAUTHEXPORT=`mktemp`
    HOSTNAME=`hostname`
    SUDODISPLAY=$DISPLAY
    SUDODISPLAY=`echo ${SUDODISPLAY} | sed "s/^localhost:/${HOSTNAME}\/unix:/"`
    xauth extract $XAUTHEXPORT $SUDODISPLAY

    ## Asetetaan tämä globaaliksi
    export XAUTHEXPORT
    ## poista xauth tiedosto scriptin loputtua
    trap cleanup 0 1 2 3 10 12 14 15
}

function root_home() {
    ROOTHOME=`getent passwd root|awk -F: '{print $6}'`
    if [ -z $ROOTHOME ]; then
        echo "WTF? en saanut rootin kotia..."
        exit 1
    fi
    export ROOTHOME
}

## Init
sudo_xauth
root_home
PASSWD=""

## Mainloop
while true; do
    echo $PASSWD | XAUTHORITY="$XAUTHEXPORT" HOME="$ROOTHOME" sudo -S "$@"
    if [ $? -eq 0 ]; then
        echo "A-OK"
        exit 0
    else
        ## Tarvitaan salasana
        PASSWD=$(kdialog --icon "password" --title ksudo --password "Password:")
        if [ $? != 0 ]; then
            echo "Painettu Cancelia"
            exit 0
        fi
    fi
done
