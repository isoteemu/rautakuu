<?php
if( $init==true ) {
    $plugin->addRule('code', "PRIVMSG");
    $plugin->addRule('nick', "Ola");
    $plugin->addRule('msg',  "op");
    return;
}

$plugin->irc->send("KICK {$plugin->line->channel} {$plugin->line->nick} :n0-0p");
?>