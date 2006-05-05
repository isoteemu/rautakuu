#!/bin/sh

mtdpidfile="$HOME/tmp/mtd.pid"

if [ -f /etc/init.d/mythbackend ]; then
    mythpidfile="/var/run/mythbackend.pid"
    MythBackend="sudo /etc/init.d/mythbackend start"
else
    mythpidfile="$HOME/tmp/mythbackend.pid"
    MythBackend="mythbackend -d --pidfile $mythpidfile -l $HOME/tmp/mythbackend.log"
fi


##
## Functioita
##

function normal()   { echo -en "\033[0m"; }
function bold()     { echo -en "\033[1m"; }
function green()    { echo -en "\033[1;32m"; }
function cyan()     { echo -en "\033[1;36m"; }
function red()      { echo -en "\033[1;31m"; }

function initDo {
    if [ "$INITING" != "false" ]; then
        if [ $? == 0 ]; then
            initDoSuccess
        else
            initDoFail
        fi
    fi
    bold
    echo -n " > "
    cyan
    echo -n "$1:"
    normal
    if [ -z $2 ]; then
        INITING="false"
    else
        INITING=$2
    fi
}

function initDoFail {
    INITING="false"
    red;
    echo " [FAIL]"
    normal;
}

function initDoSuccess {
    if [ "$INITING" != "false" ]; then
        export INITDONE="$INITDONE $INITING"
    fi
    INITING="false"
    green
    echo " [DONE]"
    normal
}


##
## Käynnistys osa
##

#if [ "$(hostname)" = "isoteemu" ]; then
#    initDo "Tarkistetaan MythTVn osiota"
#    if grep -i /mnt/sata/b /etc/mtab > /dev/null; then
#        initDoSuccess
#    else
#        initDoFail
#        exit 1
#    fi
#fi


if [ ! $(/sbin/pidof mythbackend > /dev/null 2>&1) ]; then
    initDo "Käynnistetään MythBackend"
    $MythBackend >/dev/null && initDoSuccess || initDoFail
fi

if [ ! $(/sbin/pidof mtd > /dev/null 2>&1) ]; then
    initDo "Käynnistetään MTD:"
    mtd -d > /dev/null && initDoSuccess || initDoFail
fi

if [ $(which artsshell 2>/dev/null) ]; then
    artsshell status > /dev/null 2>&1
    if [ $? == 0 ]; then
        initDo "Yritetään vapauttaa äänilaite KDE:ltä"
    fi
    artsshell suspend > /dev/null 2>&1 && initDoSuccess
fi

#if [ $(which amixer 2>/dev/null) ]; then
#    initDo "Asetetaan äänen tasot"
#    (
#        amixer set Master,0 75%,75% unmute    > /dev/null
#        amixer set PCM,0 75%,75% unmute       > /dev/null
#        amixer set Line,0 75%,75% mute captur   > /dev/null
#        #amixer set Capture,0 75%,75% captur   > /dev/null
#    ) && initDoSuccess || initDoFail
#fi

# Asetetaan away viesti
if dcop | grep -q "kopete"; then
    initDo "Asetetaan kopete away"
    dcop kopete KopeteIface setAway 'Katsomassa Japanilaista väkivalta animepornoo MythTVn tuoman teknologisen ylivertaisuuden avulla' && initDoSuccess || initDoFail
fi

# Ei taustakuvaa (vaihtumista)
if dcop | grep -q "kdedesktop"; then
    initDo "Poistetaan taustakuva"
    dcop kdesktop KBackgroundIface setBackgroundEnabled false && initDoSuccess || initDoFail
    initDo "Poistetaan hienot näytönsäästäjät"
    dcop kdesktop KScreensaverIface setBlankOnly blankOnly true && initDoSuccess || initDoFail
fi

initDo "Käynnistetään X"
xinit $HOME/bin/myth.sh -- :1 -layout "DVDMax-50"

# X sessio sammunut. Asetetaan Online
dcop | grep -q "kopete" && dcop kopete KopeteIface setAvailable

dcop kdesktop KBackgroundIface setBackgroundEnabled true
dcop kdesktop KScreensaverIface setBlankOnly blankOnly false
