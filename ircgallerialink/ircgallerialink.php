<?php

$page = "http://irc-galleria.net/channel.php?channel_id=686740";
$ie   = "Mozilla/4.0 (compatible; MSIE 6.0; Windows 98;)";

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

if ( extension_loaded("curl")) {
    $http = fetchPageCurl($page);
} else {
    $http = fetchPageWrapper($page);
}
if ( confirmPage( $http ) ) {
    die(__LINE__);
}

$users = parsePage($http);
$userUrls  = $users[1];
$userNicks = $users[2];

foreach( $userNicks as $uid => $user ) {
    echo "<user nick=\"".$user."\" link=\"http://irc-galleria.net/".$userUrls[$uid]."\">\n";
}
