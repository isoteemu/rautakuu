#!/bin/sh

## Asetetaan oletukset
# Mihin mountataan
MNTPOINT="/mnt/sata/b"

# DM Mapper nimi
MAPPER="warez"

# Laite joka decryptataan
DEVICE="/dev/sda1"

CRYPTOPTS="-c aes -h sha512"

MOUNTOPTS="-t reiser4"

## Sitten, mit�halutaan

parse_opt() {
    case "$1" in
        *\=*)
            echo "$1" | cut -f2- -d=
        ;;
    esac
}

case "$*" in
    --mount-point=*)
        MNTPOINT=`parse_opt "$*"`
    ;;
    --mapper=*)
        MAPPER=`parse_opt "$*"`
    ;;
    --device=*)
        DEVICE=`parse_opt "$*"`
    ;;
    --crypt-options=*)
        CRYPTOPTS=`parse_opt "$*"`
    ;;
    --mount-options=*)
        MOUNTOPTS=`parse_opt "$*"`
    ;;
    --kdedir=*)
        KDEDIR=`parse_opt "$*"`
    ;;
    *)
        echo "Error: Unknown option '$*'!"
    ;;
esac


##             ##
## KOODI OSUUS ##
##             ##

if [ -x "$KDEDIR/bin/kdialog" ]; then
    #foo
    echo "Löytyi \$KDEDIR"
elif [ -x "/opt/kde3/bin/kdialog" ]; then
    KDEDIR="/opt/kde3"
elif [ -x "/usr/kde/3.5/bin/kdialog" ]; then
    KDEDIR="/usr/kde/3.5"
elif [ -x "/usr/kde/3.4/bin/kdialog" ]; then
    KDEDIR="/usr/kde/3.4"
elif [ -x "/usr/kde/3.3/bin/kdialog" ]; then
    KDEDIR="/usr/kde/3.3"
else
    echo "Ei KDEn hakemistoa. Paniikki!"
    exit 1
fi

echo "KDEDIR: $KDEDIR"

if which cryptsetup; then
    cryptsetup=$(which cryptsetup)
else
    echo "Ei CryptSetuppia."
    exit 0
fi


if grep -q $MNTPOINT /etc/mtab; then
	echo "Mountpoint $MNTPOINT jo mountattu."
	exit 0
fi

pidpref=/tmp/$(whoami)-$(echo $DISPLAY)

NO_SHUTDOWN_DCOP="/bin/true"
NO_SHUTDOWN_KWIN="/bin/true"

modprobe sd_mod

if $KDEDIR/bin/dcop > /dev/null;then
    echo "DCOP päällä"
else
    echo "Käynnistetään DCOP"
    $KDEDIR/bin/dcopserver
    echo $! > $pidpref-satab-dcop.pid
    NO_SHUTDOWN_DCOP="/bin/false"

    echo "K�nnistet�n KWIN"
    $KDEDIR/bin/kwin &
    echo $! > $pidpref-satab-kwin.pid && NO_SHUTDOWN_KWIN="/bin/false"
fi

function kdedown () {
    $NO_SHUTDOWN_DCOP || ( `which dcopserver_shutdown` || kill `cat $pidpref-satab-dcop.pid` ) && echo "Ei sammuteta DCOPt�
    $NO_SHUTDOWN_KWIN || kill `cat $pidpref-satab-kwin.pid` && echo "Ei sammuteta KWINi�
}

function breakme () {
    kdedown
    exit 0
}

function success () {
	kdedown
	exit
}

if [ -c /dev/mapper/control ]; then
    echo "Controller file jo olemassa"
else

	# Get major, minor, and mknod
	MAJOR=$(sed -n 's/^ *\([0-9]\+\) \+misc$/\1/p' /proc/devices)
	MINOR=$(sed -n "s/^ *\([0-9]\+\) \+device-mapper\$/\1/p" /proc/misc)
	if test -z "$MAJOR" -o -z "$MINOR" ; then
		echo "device-mapper kernel module not loaded: can't create $CONTROL."
		exit 0
	fi

	mkdir -p --mode=755 /dev/mapper
	echo "Creating /dev/mapper/control character device with major:$MAJOR minor:$MINOR."
	mknod --mode=600 /dev/mapper/control c $MAJOR $MINOR

fi

while true; do
    if grep -q $MNTPOINT /etc/mtab; then
    	echo "Mountpoint $MNTPOINT jo liitetty"
        $KDEDIR/bin/kdialog --msgbox "BUGI."
        breakme
    fi

    if [ -b /dev/mapper/$MAPPER ]; then
        echo "Poistetaan block device $MAPPER"
        $cryptsetup remove $MAPPER
    fi
    # Katsotaan poistuiko? block device jostain syyst�ei n�, vaikka mapperi olisikin olemassa
    if [ "$(dmsetup ls | grep "$MAPPER")" ]; then
        echo "dmsetup listasi devicen $MAPPER"
        $cryptsetup remove $MAPPER
    fi
    if [ -e /dev/mapper/$MAPPER ]; then
        echo "Poistetaan tiedosto $MAPPER"
        rm -f /dev/mapper/$MAPPER
    fi

    PASSWD=$($KDEDIR/bin/kdialog --title "CryptSetup" --password "Crypto osion $MAPPER salasana:")

    if [ $? != 0 ]; then
        echo "Painettu Cancelia"
        #/opt/kde3/bin/kdialog --msgbox "Painettu Cancelia"
        breakme
    fi

    echo "$PASSWD" | $cryptsetup $CRYPTOPTS -b `blockdev --getsize $DEVICE` create $MAPPER $DEVICE || true
    echo $?
    echo "Cryptsetup suoritettu"

    if  mount $MOUNTOPTS -n -o ro /dev/mapper/$MAPPER $MNTPOINT > /dev/null; then
        echo "Testimount onnistui"
        #/opt/kde3/bin/kdialog --msgbox "Testimount onnistui"
        umount -n $MNTPOINT > /dev/null || true
        break
    else
        echo "Testimount ep�nnistui"
        umount -n $MNTPOINT > /dev/null || true
        $KDEDIR/bin/kdialog --warningyesno "Yrit�uudelleen?" || breakme
    fi
done

FSCKMSG=$(fsck.reiser4 -a /dev/mapper/$MAPPER)

FSCK_RETURN=$?
if test $FSCK_RETURN -gt 1; then
    $KDEDIR/bin/kdialog --title "Shit!" --error "fsck ep�nistui.\n$FSCKMSG"
    breakme
fi

mount $MOUNTOPTS -oasync /dev/mapper/$MAPPER $MNTPOINT &> /dev/null && kdialog --passivepopup "Crypto osio $MAPPER liitetty." 4

success
