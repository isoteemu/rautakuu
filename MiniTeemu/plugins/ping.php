<?php
/**
 * ping.php - Vastaa ping komentoon.
 */
if( $init==true ) {
    $plugin->addRule('ping', true);
    $plugin->addRule('break', true);
    return;
}

$plugin->irc->pong(substr( $plugin->line->getLine(), 5));
?>