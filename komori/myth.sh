#!/bin/sh

[ -f $HOME/.Xdefaults ] && xrdb $HOME/.Xdefaults

export LC_ALL="fi_FI.UTF-8"

xsetroot -solid black
#if [ -x /usr/lib/misc/xscreensaver/glslideshow ]; then
#    (
#        /usr/lib/misc/xscreensaver/glslideshow -root -duration 300 -pan 30 -delay 74522 -clip &
#        xpid=$!
#        sleep 1
#        renice 19 $xpid
#    ) &
#else
#    xsetbg -fullscreen -onroot '/home/teemu/Documents/Pics/wallpapers/chii1.jpg' &
#fi

#xsetbg -fullscreen -onroot '/usr/share/mythtv/themes/G.A.N.T./bkg/grey.jpeg' &

xset s noblank &
xset s off &
xset -dpms &

#if [ "$(hostname)" = "isoteemu" ]; then
#    setxkbmap -model microsoftmult -layout fi -variant basic
#fi


if [ $(which evilwm 2> /dev/null) ]; then
    evilwm -bw 0 &
elif [ $(which metacity 2> /dev/null) ]; then
    metacity &
elif [ $(which kwin 2> /dev/null) ]; then
    (
        dcopserver && kwin
    ) &
fi

#if [ -f /usr/share/mythtv/mytharchive/intro/pal_mythtv_intro.mpg ]; then
#    xine -B --no-splash -pfqh /usr/share/mythtv/mytharchive/intro/pal_mythtv_intro.mpg &
#fi

if [ $(which xvattr 2> /dev/null) ]; then
    #xvattr -a XV_COLORKEY -v 66048 &
    xvattr -a XV_COLORKEY -v 1 &
fi

#if [ $(which nvidia-settings 2> /dev/null) ]; then
    #nvidia-settings -l
    #nvidia-settings --assign="SyncToVBlank=1"
    #nvidia-settings --assign="DigitalVibrance=6"
    #nvidia-settings --assign="ImageSharpening=1.9"
    #nvidia-settings --assign="TVFlickerFilter=140"
    #nvidia-settings --assign="TVOverScan=12"
#fi

unset http_proxy

mythfrontend
