<?php
/**
 * login.php - Lähettää login komennon
 */
if( $init==true ) {
    $plugin->addRule('code', "451");
    $plugin->addRule('break', true);
    return;
}

$plugin->irc->trace("451; Rekisteröidy ensin.");
$plugin->irc->login();
?>