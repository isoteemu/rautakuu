<?php

if(!defined("_HLSTATS")) die("Direct access denied.");

function drupal_unpack($obj, $field = 'data') {
    if ($obj->$field && $data = unserialize($obj->$field)) {
        foreach ($data as $key => $value) {
            if (!isset($obj->$key)) {
                $obj->$key = $value;
            }
        }
    }
    return $obj;
}

function has_access($str="") {
    global $user, $db, $g_options, $messages;

    if(!$g_options['drupalHlstats']) return false;

    static $acl;
    if(!isset($acl[$user->uid])) {
        $db->query("SELECT access FROM drupal_steamids WHERE uid = '{$user->uid}' AND ( expire = '0000/00/00 00:00:00' OR expire > NOW() ) ORDER BY `updated` DESC LIMIT 0, 1");
        if($db->num_rows() == 1) {
            $ac = $db->fetch_row();
            $acl[$user->uid] = $ac[0];
        } else {
            $acl = "z";
        }
    }
    if(stristr($acl[$user->uid],$str)) return true;
    return false;
}

if($g_options['drupalHlstats'] && $sid = $_COOKIE[$g_options['drupal_sessionid']]) {
    include_once("DB.php");
    $drupalDB =& DB::Connect($g_options['drupal_dns']);

    $res =& $drupalDB->query("SELECT u.*, s.* FROM users u INNER JOIN sessions s ON u.uid = s.uid WHERE s.sid = '{$sid}' AND u.status < 3 AND u.uid > 0 AND s.hostname = '{$_SERVER['REMOTE_ADDR']}' LIMIT 0,1");

    if($res->numRows() == 1) {
        $user = $res->fetchRow(DB_FETCHMODE_OBJECT);
        $user = drupal_unpack($user);

        /*
        $ares =& $drupalDB->query("SELECT access FROM drupal_steamids WHERE uid = '{$user->uid}' AND ( expire = '0000/00/00 00:00:00' OR expire > NOW() ) ORDER BY `updated` DESC LIMIT 0, 1");
        $acc =& $ares->fetchRow();
        $user->access = $ares[0];
        */

        $messages['admin'][] = sprintf(_("Olet VIP %s"),'<a href="'.$g_options['drupal_url'].'/user/'.$user->uid.'">'.$user->name.'</a>');

    }

    $drupalDB->disconnect();
}
?>