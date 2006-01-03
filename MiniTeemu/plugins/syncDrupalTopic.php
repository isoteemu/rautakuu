<?php

static $db;

if( $init==true ) {
    $plugin->addRule('code', "TOPIC");
    $plugin->addRule('channel', "#rautakuu");

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

$topic = mb_convert_encoding($plugin->line->msg, "utf-8");

$sql = sprintf("UPDATE `node` SET `title`='%s' WHERE `nid` = '32'", $topic);

$res =& $db->query($sql);

if( DB::IsError($res) ) {
    irc::trace("DB Error: ".$res->getMessage());
}

?>