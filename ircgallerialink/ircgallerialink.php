#!/usr/bin/php -q
<?php

// IRC gallerian kanavan sivu
$page    = "http://irc-galleria.net/channel.php?channel_id=686740";

// Pisgin conf tiedosto. Sielt‰ luetaan user aliakset.
$pisgcfg = "pisg.cfg";

// Tunnistetiedot. Jos joskus scripti blokataan, vaihdat vain t‰h‰n jonkin toisen selaimen.
$ie      = "Mozilla/4.0 (compatible; MSIE 5.0; Windows 98;)";

//
// KOODIOSA
//

/**
 * Pidet‰‰n t‰m‰ globalsina, ett‰ voidaan k‰sin m‰‰ritell‰
 * aliaksia tarvittaessa.
 */
$aliasdb         = array();
$aliasdb['map']  = array();
$aliasdb['rmap'] = array();

/**
 * Hakee sivun k‰ytt‰en curlin kirjastoa
 * @param  $url  haettavan sivun url
 * @return  string  Sivun sis‰ltˆ
 */
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

/**
 * Yksinkertainen PHP wrappereita k‰ytt‰v‰ sivun haku
 * @param  $url  Haettavan sivun url
 * @return  string  Sivun sis‰ltˆ
 */
function fetchPageWrapper($url) {
    $lines = file($url);
    return implode("", $lines);
}

/**
 * Varmistaa ett‰ sivu noudatta irc-gallerian tyyli‰
 */
function confirmPage( $page ) {
    if(preg_match('/<table id="channelmembers">.*<\/table>/', $page)) return true;
    return false;
}

/**
 * Erottelee nickit irc-gallerian sivusta
 */
function parseNicks($page) {
    $frontalbrobe=str_replace("\n", "", $page);

    preg_match('|<table id=\"channelmembers\">.*</table>|U', $frontalbrobe, $table);
    preg_match_all('|<a.*>(.*)</a>|U', $table[0], $users);
    return $users[1];
}

/**
 * Erottelee aliakset pisgin confista
 * @param  $pisgcfg  Pisgin conffin polku
 */
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

// Jos curl extension on k‰ytett‰viss‰, k‰yt‰ sit‰
// Jos ei, k‰yt‰ sit‰ mit‰ php sattuu k‰ytt‰m‰‰n.
if ( extension_loaded("curl")) {
    $http = fetchPageCurl($page);
} else {
    $http = fetchPageWrapper($page);
}

// Jos sivu ei vastaa irc-gallerian tyyli‰, panikoi.
if ( confirmPage( $http ) ) {
    die();
}

// Lue pisgin conf <user> attribuuteista, ja etsi aliakset
if (is_readable($pisgcfg)){
    readPisgCfg($pisgcfg);
}

// Lue nickit sivulta ja luo <user> tagit.
$userNicks = parseNicks($http);
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
