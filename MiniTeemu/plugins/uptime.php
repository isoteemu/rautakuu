<?php
/**
 * uptime.php - suorittaa uptimen
 */
if( $init==true ) {
    $plugin->addRule('code', "PRIVMSG");
    $plugin->addRule('msg',  "uptime");
    return;
}

$plugin->irc->message("{$plugin->line->nick}: ".trim(exec("uptime")));

?>