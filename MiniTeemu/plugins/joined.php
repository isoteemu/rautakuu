<?php
/**
 * joined.php - merkkaa kanavalle liittymisen.
 */
if( $init==true ) {
    $plugin->addRule('code', 'JOIN');
    $plugin->addRule('nick', &$plugin->irc->botNick);
    $plugin->addRule('break', true);
    return;
}

if(isset($plugin->irc->_channel[$plugin->line->channel])) unset($plugin->irc->_channel[$plugin->line->channel]);

?>