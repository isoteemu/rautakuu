<?php
/**
 * nickInUse.php - vaihtaa nickin jos jo käytössä.
 */
if( $init==true ) {
    $plugin->addRule('code', "433");
    $plugin->addRule('break', true);
    return;
}

$nick =& $plugin->irc->botNick;

$nickLchar = substr($nick, -1);

if( is_int($nickLchar)) {
    $nickLchar++;
    $nick = substr($nick, 0, strlen($nick)-1).$nickLchar;
} else {
    $nick .= 0;
}

irc::trace("Nick is on use, using different nick, $nick");

$plugin->irc->login();
?>