<?php
 /***************************************************************************
          ajaxtop.php  -  JavaScript + php powered web top-like program.
            -------------------
          begin                : Sat Mar 05 2005
          copyright            : (C) 2005 by Teemu A
          email                : teemu@terrasolid.fi
 ***************************************************************************/

/***************************************************************************
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU General Public License as published by  *
 *   the Free Software Foundation; either version 2 of the License, or     *
 *   (at your option) any later version.                                   *
 *                                                                         *
 ***************************************************************************/

$toprc = dirname(__FILE__)."/toprc";
$topexec = "/usr/bin/top -n1 -b";

// Kuinka useasti autorefreshataan. sekuntteina.
$web['refreshtime'] = 30;

session_start();

/**
 * Toistuva osa.
 */
if( $_GET['do'] == "top" ) {

    // Tehdään home kansio.
    $home = tempnam("/tmp", "ajaxtop");
    unlink($home);
    mkdir($home);
    if(file_exists($toprc)) {
        copy($toprc, $home."/.toprc");
    }

    // Joissain käyttiksissä shellinä on /bin/false, eikä se luonnollisesti toimi
    // $top = shell_exec("HOME=".escapeshellcmd($home)." top -n1 -b");
    exec("HOME=".escapeshellcmd($home)." TERM=xterm COLUMNS=400 {$topexec}", $exec);
    $top = implode("\n", $exec);

    if(file_exists($home."/.toprc")) unlink($home."/.toprc");
    rmdir($home);

    $trows = explode("\n", $top);

    $ps = false;
    $pids = array();
    $header;

    foreach( $trows as $row ) {
        if( $ps == false && substr(trim($row), 0, 3) == "PID") {
            // Anna headeri
            $pids[0] = $row;
            $ps = true;
            continue;
        } elseif( $ps == false ) {
            continue;
        }
        //echo "$row\n";
        if(empty($row)) continue;

        preg_match("/^([\d]*).*/", trim($row), $res);
        $pids[$res[1]] = $row;
    }
    ksort($pids);

    $pidres = "";
    $topres = "";
    $stares = "";

    $opids =& $_SESSION['oldpids'];

    // Käydään ensin uudet pidit
    foreach($pids as $pid => $psrow) {
        // Ensin päivitetyt ja uudet rivit
        if($opids[$pid] != $psrow ) {
            $pidres .= '"'.$pid.'",';
            $topres .= '"'.$psrow.'",';
            $stares .= 'true,';
        }
        // Poistetaan vanha pid merkintä
        unset($opids[$pid]);
    }

    // Jos vielä vanhjo pideä, merkitään ne kuolleiksi
    if( count( $opids ) > 0 ) {
        foreach( $opids as $rippid => $null ) {
            $pidres .= '"'.$rippid.'",';
            $topres .= '"",';
            $stares .= 'false,';
        }
    }

    // siirretään uudet pidit vanhoiksi
    $_SESSION['oldpids'] = $pids;

    // Poistetaan pilkut lopusta
    $pidres = substr($pidres,0,strlen($pidres)-1);
    $topres = substr($topres,0,strlen($topres)-1);
    $stares = substr($stares,0,strlen($stares)-1);

    // Generoidaan lauseke
    die('new Array(new Array('.$pidres.'), new Array('.$topres.'), new Array('.$stares.'));');
}

/**
 * Kerran ladattava sivu.
 * Tuhotaan samalla vanha session container
 */
$_SESSION['oldpids'] = array();
?>

<html>
    <head>
<script language="JavaScript">
<!--

var topuri = "<?= $_SERVER['REQUEST_URI'] ?>?do=top";

var xmlResult = new Array();
var _stanByDiv = null;

var _xmlHttp = null;

var _sleepTime = <?=$web['refreshtime'];?>;

var topDivs = new Array();

var fontSize = 14;

var _appendId = "top"

// Estää tupla refreshauksen
var _refreshing = false;

function getXMLHTTPResult() {
    if(_refreshing == false ) {
        _xmlHttp.open("GET",topuri,true);
        _xmlHttp.onreadystatechange=parseResult;
        _xmlHttp.send(null);
        return true;
    }
}

function parseResult() {
    if(_xmlHttp.readyState==4&&_xmlHttp.responseText) {
        xmlResult = eval(_xmlHttp.responseText);
        buildLayout();
        removeStanBy();
    }
}

function buildLayout() {

    // mihin liitetään kaikki?
    var topAppend = document.getElementById(_appendId);
    // kertoo sen hetkisen työskentely divin.
    var workDiv = null;
    // Senhetkinen tekstikenttä
    var topRow = null;
    // Poistettava DIV
    var toRemove = null;

    var foo = null;

    for( var f=0; f<xmlResult[0].length; ++f){
        // Array alkaa sijotuksella 0 niinpä +1:htä ei tarvita
        var myPlace=topDivs.length;

        // Jos haluttu poistettavaksi, poista.
        if(xmlResult[2][f] == false ) {
            if(toRemove=document.getElementById(xmlResult[0][f])) {
                topAppend.removeChild(toRemove);
            } else {
                message("<strong>E:</strong> Ei voitu poistaa elementtiä "+xmlResult[0][f]);
            }
            topDivs.sort();
            continue;
        }

        // Jos diviä _ei_ ole, tee se.
        if((workDiv=document.getElementById(xmlResult[0][f])) == null ) {
            topDivs[myPlace]=document.createElement("DIV");
            topDivs[myPlace].id=xmlResult[0][f];
            setStyle(topDivs[myPlace]);
            workDiv=topDivs[myPlace];
            topAppend.appendChild(workDiv);
        }
        // aseta divin teksti.
        setText(workDiv, xmlResult[1][f]);
    }
    topDivs.sort();
}

function setStyle(tag) {
    tag.style.wordWrap="break-word";
    tag.style.fontFamily="monospace";
    tag.style.whiteSpace="pre";
    tag.style.fontSize=fontSize+"px";
}

// Koska jotkin selaimet käyttävät innerTextiä ja toiset
// innerHTMLlää, niin tämä asettaa kummatkin.
function setText(tag,text) {
    tag.innerText = text;
    tag.innerHTML = text;
}

function stanBy() {
    if(_refreshing == false ) showWait("Stand By...");
}

function autoRefreshing() {
    if(_refreshing == false ) showWait("Auto refreshing...");
}

// Käytä tätä functiota ei-standby tyyppisille visteille.
function message(text) {
    showWait(text);
    // Hävittää viestin 2.5s päästä
    setTimeout("rmMessage()", 2500);
}

function showWait(text) {
    if(_stanByDiv==null) {
        alert("_standByDiv:iä ei ole asetettu.");
    }
    _stanByDiv.innerHTML = text;
    _stanByDiv.innerText = text;
    _stanByDiv.style.visibility="visible";
}

function removeStanBy() {
    _refreshing = false;
    rmMessage();
}

function rmMessage() {
    if(_stanByDiv==null) {
        alert("_standByDiv:iä ei ole asetettu.");
    }
    _stanByDiv.style.visibility="hidden";
}

function reloadTop() {
    if(_refreshing == false ) {
        stanBy();
        getXMLHTTPResult();
    }
}

function top() {
    if(_stanByDiv == null) {
        _stanByDiv=document.getElementById("stanBy");
        stanBy();
    }
    _xmlHttp = new XMLHttpRequest();
    mainLoop();
    stanBy();
}

// Tämä kutsuu itseään uudestaan ja uudestaan ja uudestaan...
mainLoop=function() {
    if(_refreshing == false ) {
        autoRefreshing();
        getXMLHTTPResult();
    }
    setTimeout("mainLoop()", getTimer());
}

function getTimer() {
    return _sleepTime*1000;
}

function plusFontSize() {
    fontSize+=2;
    changeFontSize();
}

function minusFontSize() {
    if(fontSize>2) {
        fontSize-=2;
    }
    changeFontSize();
}

function changeFontSize() {
    message("Fontin koko: "+fontSize+"px");
    for( var f=0; f<topDivs.length; ++f){
        topDivs[f].style.fontSize=fontSize+"px";
    }
}

// -->
</script>
<style>
body {
    font-size:14px;
    font-family:"Times new Roman";
}

#stanBy {
    background-color : #ffffff;
    border-bottom-color : #ff0000;
    border-bottom-style : solid;
    border-bottom-width : 1px;
    border-left-color : #ff0000;
    border-left-style : solid;
    border-left-width : 1px;
    border-right-color : #ff0000;
    border-right-style : solid;
    border-right-width : 1px;
    border-top-color : #ff0000;
    border-top-style : solid;
    border-top-width : 1px;
    color : #FF0000;
    margin-left : 190px;
    padding-bottom : 5px;
    padding-left : 10px;
    padding-right : 10px;
    padding-top : 5px;
    position : absolute;
    width : 90px;
    text-align : center;
    visibility : visible;
    white-space: nowrap;
  }

#reload {
    background-color : #ffffff;
    border-bottom-color : #0000ff;
    border-bottom-style : solid;
    border-bottom-width : 1px;
    border-left-color : #0000ff;
    border-left-style : solid;
    border-left-width : 1px;
    border-right-color : #0000ff;
    border-right-style : solid;
    border-right-width : 1px;
    border-top-color : #0000ff;
    border-top-style : solid;
    border-top-width : 1px;
    color : #0000FF;
    padding-left : 10px;
    padding-right : 10px;
    padding-top : 5px;
    padding-bottom : 5px;
    text-decoration : none;
    width:90px;
    text-align:center;
    position:absolute;
  }


#reload:hover {
    background-color : #ffffff;
    border-bottom-color : #0000cd;
    border-bottom-style : solid;
    border-bottom-width : 1px;
    border-left-color : #1919ff;
    border-left-style : solid;
    border-left-width : 1px;
    border-right-color : #0000cd;
    border-right-style : solid;
    border-right-width : 1px;
    border-top-color : #1919ff;
    border-top-style : solid;
    border-top-width : 1px;
    color : #0000FF;
    padding-left : 9px;
    padding-right : 11px;
    padding-top : 4px;
    padding-bottom : 6px;
    text-decoration : none;
    position:absolute;
  }


#reload:active {
    background-color : #ffffff;
    border-bottom-color : #1919ff;
    border-bottom-style : solid;
    border-bottom-width : 1px;
    border-left-color : #0000cd;
    border-left-style : solid;
    border-left-width : 1px;
    border-right-color : #1919ff;
    border-right-style : solid;
    border-right-width : 1px;
    border-top-color : #0000cd;
    border-top-style : solid;
    border-top-width : 1px;
    color : #0000FF;
    padding-left : 11px;
    padding-right : 9px;
    padding-top : 6px;
    padding-bottom : 4px;
    text-decoration : none;
    position:absolute;
  }

#nav {
    padding:8px;
    top:0px;
    position:fixed;
}

#plusFontSize {
    background-color : #ffffff;
    border-bottom-color : #0000ff;
    border-bottom-style : solid;
    border-bottom-width : 1px;
    border-left-color : #0000ff;
    border-left-style : solid;
    border-left-width : 1px;
    border-right-color : #0000ff;
    border-right-style : solid;
    border-right-width : 1px;
    border-top-color : #0000ff;
    border-top-style : solid;
    border-top-width : 1px;
    color : #0000FF;
    margin-left : 120px;
    padding-left : 5px;
    padding-right : 5px;
    padding-top : 5px;
    padding-bottom : 5px;
    text-decoration : none;
    width:15px;
    text-align:center;
    position:absolute;
}

#plusFontSize:hover {
    background-color : #ffffff;
    border-bottom-color : #0000cd;
    border-bottom-style : solid;
    border-bottom-width : 1px;
    border-left-color : #1919ff;
    border-left-style : solid;
    border-left-width : 1px;
    border-right-color : #0000cd;
    border-right-style : solid;
    border-right-width : 1px;
    border-top-color : #1919ff;
    border-top-style : solid;
    border-top-width : 1px;
    color : #0000FF;
    margin-left : 120px;
    padding-left : 4px;
    padding-right : 6px;
    padding-top : 4px;
    padding-bottom : 6px;
    text-decoration : none;
    width:15px;
    text-align:center;
    position:absolute;
}


#plusFontSize:active {
    background-color : #ffffff;
    border-bottom-color : #1919ff;
    border-bottom-style : solid;
    border-bottom-width : 1px;
    border-left-color : #0000cd;
    border-left-style : solid;
    border-left-width : 1px;
    border-right-color : #1919ff;
    border-right-style : solid;
    border-right-width : 1px;
    border-top-color : #0000cd;
    border-top-style : solid;
    border-top-width : 1px;
    color : #0000FF;
    margin-left : 120px;
    padding-left : 6px;
    padding-right : 4px;
    padding-top : 6px;
    padding-bottom : 4px;
    text-decoration : none;
    width:15px;
    text-align:center;
    position:absolute;
  }

#minusFontSize {
    background-color : #ffffff;
    border-bottom-color : #0000ff;
    border-bottom-style : solid;
    border-bottom-width : 1px;
    border-left-color : #0000ff;
    border-left-style : solid;
    border-left-width : 1px;
    border-right-color : #0000ff;
    border-right-style : solid;
    border-right-width : 1px;
    border-top-color : #0000ff;
    border-top-style : solid;
    border-top-width : 1px;
    color : #0000FF;
    margin-left : 155px;
    padding-left : 5px;
    padding-right : 5px;
    padding-top : 5px;
    padding-bottom : 5px;
    text-decoration : none;
    width:15px;
    text-align:center;
    position:absolute;
  }
#minusFontSize:hover {
    background-color : #ffffff;
    border-bottom-color : #0000cd;
    border-bottom-style : solid;
    border-bottom-width : 1px;
    border-left-color : #1919ff;
    border-left-style : solid;
    border-left-width : 1px;
    border-right-color : #0000cd;
    border-right-style : solid;
    border-right-width : 1px;
    border-top-color : #1919ff;
    border-top-style : solid;
    border-top-width : 1px;
    color : #0000FF;
    margin-left : 155px;
    padding-left : 4px;
    padding-right : 6px;
    padding-top : 4px;
    padding-bottom : 6px;
    text-decoration : none;
    width:15px;
    text-align:center;
    position:absolute;
  }

#minusFontSize:active {
    background-color : #ffffff;
    border-bottom-color : #1919ff;
    border-bottom-style : solid;
    border-bottom-width : 1px;
    border-left-color : #0000cd;
    border-left-style : solid;
    border-left-width : 1px;
    border-right-color : #1919ff;
    border-right-style : solid;
    border-right-width : 1px;
    border-top-color : #0000cd;
    border-top-style : solid;
    border-top-width : 1px;
    color : #0000FF;
    margin-left : 155px;
    padding-left : 6px;
    padding-right : 4px;
    padding-top : 6px;
    padding-bottom : 4px;
    text-decoration : none;
    width:15px;
    text-align:center;
    position:absolute;
  }


</style>
    </head>
    <body>
        <div id="nav">
            <a href="JavaScript:reloadTop();" id="reload" title="Päivitä prosessitiedot">Reload</a>
            <a href="JavaScript:plusFontSize();" id="plusFontSize" title="Suurenna fontin kokoa">+</a>
            <a href="JavaScript:minusFontSize();" id="minusFontSize" title="Pienennä fontin kokoa">-</a>
            <span id="stanBy">Stand By...</span>
        </div>
        <div id="top" style="padding-top : 35px;">
        <script language="JavaScript">
            top();
        </script>
        <noscript>
            Selaimesi ei tue JavaScriptiä. Sorry.
        </noscript>
        </div>
    </body>
</html>