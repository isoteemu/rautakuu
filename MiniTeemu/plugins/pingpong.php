<?php
if( $init==true ) {
    $plugin->addRule('code', 'PONG');
    $plugin->addRule('break', true);
    return;
}



$time = $plugin->irc->ping($plugin->line->msg);
$diff = timer()-$time;
irc::trace("Current latency is $diff");

?>