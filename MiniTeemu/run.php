#!/usr/bin/php -q
<?php

require_once("miniteemu.php");

$irc =& new irc();
$GLOBALS['irc'] =& $irc;

irc::trace("Yhdistetään...");
$irc->connect();
irc::trace("Kuunnellaan...");
$irc->listen();



?>