<?php

static $db;

if( $init==true ) {
    $plugin->addRule('code', "TOPIC");

    // DB connection
    include_once("DB.php");
    $db = DB::Connect("mysql://miniteemu:a24cdcf4903642@localhost/drupal");
}

$ser = serialize($plugin->line->msg);
$db->query("UPDATE `variable` SET `value`='{$ser}' WHERE `name` = 'site_slogan'");


?>