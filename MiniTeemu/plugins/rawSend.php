<?php
if( $init==true ) {
    $plugin->addRule('code',   "PRIVMSG");
    $plugin->addRule('prefix', "MiniMe, rawSend ");
    $plugin->addRule('nick',   "IsoTeemu");
    $plugin->addRule('break',  true);
    return;
}

if(preg_match('/^MiniMe, rawSend (.+)$/', $plugin->line->msg, $match)) {
    irc::trace("RAW send: {$match[1]}");
    $plugin->irc->send($match[1]);
    $plugin->irc->flushBuffer();
}

?>