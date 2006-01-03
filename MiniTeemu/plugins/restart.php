<?php
/**
 * restart.php - Forkaa uuden version itsestään ja lopettaa itsensä.
 */
if( $init==true ) {
    $plugin->addRule('code',  "PRIVMSG");
    $plugin->addRule('msg',   "MiniMe, restart");
    $plugin->addRule('nick',  "IsoTeemu");
    $plugin->addRule('break', true);
    /*
    declare(ticks = 1);
    $noHup=create_function('$sig', 'irc::trace("Got hub. ignoring.");');
    pcntl_signal(SIGHUP,  $noHup);
    */
    return;
}
$plugin->irc->disconnect("restarting...");
passthru("php -q ".MINITEEMU." &");
sleep(1);
die("Killing myself due restart");

?>