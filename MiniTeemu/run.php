#!/usr/bin/php -q
<?php

require_once("miniteemu.php");

$irc =& new irc(array("server"=>"fi.quakenet.org"));
$GLOBALS['irc'] =& $irc;

irc::trace("Yhdistetään...");
$irc->connect();
$irc->join("#rautakuu");

$authcode = '
if( $init==true ) {
    $plugin->addRule("code", "366");
    $plugin->addRule("expire", time()+10);
    return;
}

$plugin->irc->trace("366: QuakeNet auth");
//$plugin->irc->send("PRIVMSG Q@CServe.quakenet.org :auth MiniTeemu *******");
';
$irc->irc_data->triggers->newPlugin($authcode,"QAuth");

$irc->listen();

$irc->disconnect();

?>
