#!/usr/bin/php -q
<?php

$page    = "http://irc-galleria.net/channel.php?channel_id=686740";
$pisgcfg = "pisg.cfg";

$ie      = "Mozilla/4.0 (compatible; MSIE 6.0; Windows 98; All_Your_Oil_Belong_To_US;)";

/* KOODIOSA */

$aliasdb         = array();
$aliasdb['map']  = array();
$aliasdb['rmap'] = array();

function fetchPageCurl($url) {
    global $ie;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_USERAGENT, $ie);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($curl);
    curl_close($curl);
    return $result;
}

function fetchPageWrapper($url) {
    $lines = file($url);
    return implode("", $lines);
}

function confirmPage( $page ) {
    if(preg_match('/<table id="channelmembers">.*<\/table>/', $page)) return true;
    return false;
}


function parsePage($page) {
    $frontalbrobe=str_replace("\n", "", $page);

    preg_match('|<table id=\"channelmembers\">.*</table>|U', $frontalbrobe, $table);
    preg_match_all('|<a href="(.*)">(.*)</a>|U', $table[0], $users);
    return $users;
}

function readPisgCfg($pisgcfg) {

    global $aliasdb;

    $handle = fopen($pisgcfg, "r");
    $file = fread( $handle, filesize($pisgcfg) );
    fclose($handle);

    if(preg_match('/<user[^>]+>/', $file)) {
        $allisone=str_replace("\n", "", $file);
        preg_match_all('|<user.*nick="(.*)".*alias="(.*)".*>|Um', $allisone, $aliases);
        foreach($aliases[1] as $id => $nick) {
            if(empty($aliases[2][$id])) continue;
            $ealiases =& explode(" ", $aliases[2][$id]);
            foreach( $ealiases as $ln ) {

                $aliasdb['map'][$nick][] = $ln;
                $aliasdb['rmap'][$ln] = $nick;
            }
        }
    }
    return false;
}

if ( extension_loaded("curl")) {
    $http = fetchPageCurl($page);
} else {
    $http = fetchPageWrapper($page);
}

if ( confirmPage( $http ) ) {
    die(__LINE__);
}

// Lue pisgin conf <user> attribuuteista, ja etsi aliakset
if (is_readable($pisgcfg)){
    readPisgCfg($pisgcfg);
}

$users = parsePage($http);
$userUrls  =& $users[1];
$userNicks =& $users[2];

foreach( $userNicks as $uid => $user ) {

    if( isset( $aliasdb['rmap'][$user] )) {
        echo "<user nick=\"".$aliasdb['rmap'][$user]."\"";
        $rnick =& $aliasdb['rmap'][$user];
    } else {
        echo "<user nick=\"".$user."\"";
        $rnick =& $user;
    }
    if( isset( $aliasdb['map'][$rnick] )) {
        echo " alias=\"";
        $first = true;
        foreach( $aliasdb['map'][$rnick] as $a ) {
            if( $first == false ) {
                echo " ";
            } else {
                $first = false;
            }
            echo $a;
        }
        echo '"';
    }
    echo " link=\"http://irc-galleria.net/view.php?nick=".urlencode($userNicks[$uid])."\">\n";
}
