<?php
if( $init==true ) {
    $plugin->addRule('code', "INVITE");
    $plugin->addRule('break', true);
    return;
}

$channelparts = explode(" ", $plugin->line->data);
$channel =& $channelparts[3];

irc::trace("Joining to channel ".$channel);
$plugin->irc->join($channel);
?>