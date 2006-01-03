<?php
if( $init==true ) {
    $plugin->addRule('code', "INVITE");
    $plugin->addRule('break', true);
    return;
}

$plugin->irc->join($plugin->line->channel);
?>