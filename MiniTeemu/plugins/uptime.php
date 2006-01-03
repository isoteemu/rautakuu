<?php
/**
 * uptime.php - suorittaa uptimen
 */
if( $init==true ) {
    $plugin->addRule('code', "PRIVMSG");
    $plugin->addRule('msg',  "uptime");
    return;
}

$plugin->message("{$plugin->line->nick}: ".trim(exec("uptime")));

?>