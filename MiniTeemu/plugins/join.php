<?php
/**
 * join.php - Liittyy kanavalle welcomen saatuaan.
 */
if( $init==true ) {
    $plugin->addRule('code', "001");
    $plugin->addRule('break', true);
    return;
}

$plugin->irc->join();
?>