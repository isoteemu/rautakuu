<?php

static $db;

if( $init==true ) {
    $plugin->addRule('code', "TOPIC");

    // DB connection
    include_once("DB.php");
    $db = DB::Connect("mysql://miniteemu:a24cdcf4903642@localhost/drupal");
    if (PEAR::isError($db)) {
        irc::trace("DB Error: ".$db->getMessage());
    }
    $db = false;
}

if( $db == false ) return;

$ser = serialize($plugin->line->msg);
$res =& $db->query("UPDATE `variable` SET `value`='{$ser}' WHERE `name` = 'site_slogan'");

if( PEAR::isError($res) ) {
    irc::trace("DB Error: ".$res->getMessage());
}

?>