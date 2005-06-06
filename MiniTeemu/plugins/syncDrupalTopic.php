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

$topic = mb_convert_encoding($plugin->line->msg, "utf-8", "iso-8859-1");
if (substr($topic, 0, 21) == "Rautakuu [dot] org ||") {
    $topic = substr($topic, 22);
}

$sql = sprintf("UPDATE `variable` SET `value`='%s' WHERE `name` = 'site_slogan'", serialize($topic));

$res =& $db->query($sql);

if( DB::IsError($res) ) {
    irc::trace("DB Error: ".$res->getMessage());
}

?>