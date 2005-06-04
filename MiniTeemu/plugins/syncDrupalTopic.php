<?php

static $db;

if( $init==true ) {
    $plugin->addRule('code', "TOPIC");

    // DB connection
    include_once("DB.php");
    $db = DB::Connect("mysql://miniteemu:a24cdcf4903642@localhost/drupal");
    if(DB::IsError($db)) {
        irc::trace("DB Error: ".$db->getMessage());
    }
}

if(DB::IsError($db)) {
    irc::trace("Virhe tietokantayhteydessä, ei voi syncata topicia");
    return;
}

$sql = sprintf("UPDATE `variable` SET `value`='%s' WHERE `name` = 'site_slogan'", serialize($plugin->line->msg));

$res =& $db->query($sql);

if( DB::IsError($res) ) {
    irc::trace("DB Error: ".$res->getMessage());
}

?>