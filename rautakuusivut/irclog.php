<?php
header("Content-Type: text/html;charset=utf-8");

mb_internal_encoding("UTF-8");

include_once("DB.php");
//include_once("config.inc.php");

function htmlline($str) {
    $str = htmlspecialchars($str);
    $str = eregi_replace( "([[:alnum:]]+)://([^[:space:]]*)([[:alnum:]#?/&=])", "<a href=\"\\1://\\2\\3\" target=\"_blank\">\\1://\\2\\3</a>", $str);
    $str = eregi_replace( "(([a-z0-9_]|\\-|\\.)+@([^[:space:]]*)([[:alnum:]-]))", "<a href=\"mailto:\\1%s\" >\\1</a>", $str);
    return addslashes($str);
}

function formatNick($nick) {
    return htmlentities($nick);
}

function formatTime($time) {
    //lazy
    return substr($time,-6,2).":".substr($time,-4,2);
}

function getMessages($time=null) {
    static $DB;
    if(!isset($DB)) {
        $DB = DB::Connect();
    }
    if( $time == null ) {
        $sql = "
            SELECT
                `time` , `nick` , `msg`
            FROM
                `ircmsg`
            ORDER BY
                `time` DESC
            LIMIT 0 , 10";
            $now = 0;
    } else {
        $sql = "
            SELECT
                `time` , `nick` , `msg`
            FROM
                `ircmsg`
            WHERE
                `time` > {$time}
            ORDER BY
                `time` DESC";
            $now = $time;
    }
    $res =& $DB->query($sql);
    if(DB::IsError($res)) {
        die("DB Error: ".$res->getMessages());
    }

    $times = "";
    $nicks = "";
    $mesgs = "";

    $tmp = array();
    while(list($time, $nick, $msg)=$res->fetchRow()) {
        $tmp[] = array('time' => $time, 'nick' => $nick, 'msg' => $msg);
    }

    $tmp = array_reverse($tmp);

    foreach($tmp as $row) {
        if($row['time'] > $now) $now = $row['time'];
        $times .= '"'.formatTime($row['time']).'",';
        $nicks .= '"'.formatNick($row['nick']).'",';
        $mesgs .= '"'.htmlline($row['msg']).'",';
    }

    $times = substr($times,0,strlen($times)-1);
    $nicks = substr($nicks,0,strlen($nicks)-1);
    $mesgs = substr($mesgs,0,strlen($mesgs)-1);

    return "new Array(\"{$now}\", new Array({$times}), new Array({$nicks}), new Array({$mesgs}));";
}

if(isset($_GET['time'])) {
    die(getMessages($_GET['time']));
}



?>
<html>
    <head>
        <title>Rautakuu [dot] org :: #rautakuu IRC viestit</title>
        <meta http-equiv="Content-Language" content="fi">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <style>
body, p, td {
    font-family: monospace;
    font-size: 10px;
    color: #FF3C12;
}

a:link, a:visited, a:active {
    text-decoration: none;
    color: #545454;
}

a:hover {
    text-decoration: underline;
    color: #7A7A7A;
}

#fade {
    background-image:url("/rautakuu/towhite.png");
    background-repeat:repeat-y;
    background-position:right center;
    z-index:20;
    width:25px;
    top:0;
    bottom:0;
    right:0;
    position:fixed;
}

        </style>
        <script>

var topuri = "<?= $_SERVER['REQUEST_URI'] ?>?time=";

var xmlResult = <?= getMessages();?>


var xmlHttp = null;

var _sleepTime = 2;

var _appendId = "viestit";

var nickColors = new Array();

// Estää tupla refreshauksen
var _refreshing = false;

function getXMLHTTPResult() {
    if(xmlHttp&&xmlHttp.readyState!=0&&_refreshing==false) {
        xmlHttp.abort();
        requesterInit();
    } else if(!xmlHttp) {
        // Turhaa edes yrittää
        _refreshing = true;
    } else {
        if(_refreshing == false ) {
            var openUri=topuri+xmlResult[0];
            xmlHttp.open("GET",openUri,true);
            _refreshing = true;
            xmlHttp.onreadystatechange=parseResult;
            xmlHttp.send(null);
            return true;
        }
    }
}

function parseResult() {
    if(xmlHttp.readyState==4&&xmlHttp.responseText) {
        xmlResult = eval(xmlHttp.responseText);
        buildLayout();
        _refreshing = false;
    }
}

function buildLayout() {

    // kertoo sen hetkisen työskentely divin.
    var workTR = null;

    for( var f=0; f<xmlResult[1].length; ++f) {
        // Array alkaa sijotuksella 0 niinpä +1:htä ei tarvita

        workTR=document.createElement("TR");
        setStyle(workTR);

        var timeTD=document.createElement("TD");
        setText(timeTD, "["+xmlResult[1][f]+"]");
        workTR.appendChild(timeTD);

        var nickTD=document.createElement("TD");
        setText(nickTD, "&lt;"+xmlResult[2][f]+"&gt;");
        nickTD.style.color=colorNick(xmlResult[2][f]);
        nickTD.style.textAlign="right";
        workTR.appendChild(nickTD);

        var msgTD=document.createElement("TD");
        setText(msgTD, xmlResult[3][f]);
        workTR.appendChild(msgTD);

        _appendId.appendChild(workTR);
    }
    window.scroll(0,document.body.offsetHeight);
}

function IntRandom(max) {
    return Math.floor(Math.random()*max);
}

function dechex(b) {
    var hexStr = '0123456789abcdef';
    return hexStr.charAt(Math.floor(b / 16)) + hexStr.charAt(b % 16);
}

function colorNick(nick) {
    if(!nickColors[nick]) {
        nickColors[nick]='#'+dechex(IntRandom(220))+dechex(IntRandom(220))+dechex(IntRandom(220));
    }
    return nickColors[nick];

}

function setStyle(tag) {
    tag.style.wordWrap="break-word";
    tag.style.whiteSpace="pre";
    tag.style.bgcolor="#ffff99";
}

// Koska jotkin selaimet käyttävät innerTextiä ja toiset
// innerHTMLlää, niin tämä asettaa kummatkin.
function setText(tag,text) {
    tag.innerText = text;
    tag.innerHTML = text;
}

function requesterInit() {
    try{
       xmlHttp=new ActiveXObject("Msxml2.XMLHTTP")
    } catch(e){
        try{
            xmlHttp=new ActiveXObject("Microsoft.XMLHTTP")
        } catch(sc) {
            xmlHttp=null;
        }
    }
    if(!xmlHttp&&typeof XMLHttpRequest!="undefined") {
        xmlHttp=new XMLHttpRequest();
    }
}

function init() {
    document.body.style.overflow='hidden';
    _appendId=document.createElement("table");
    var toAppend=document.getElementById("foo");
    toAppend.appendChild(_appendId);

    buildLayout();
    requesterInit();
    mainLoop();
}

// Tämä kutsuu itseään uudestaan ja uudestaan ja uudestaan...
mainLoop=function() {
    if(_refreshing == false ) {
        getXMLHTTPResult();
    }
    setTimeout("mainLoop()", getTimer());
}

function getTimer() {
    return _sleepTime*1000;
}

        </script>
    </head>
    <body bgcolor="white" onLoad="init()">
        <div id="fade"><img src="#" height="0" widht="0"></div>
        <div id="foo" style="z-index:5;">
        </div>
    </body>
</html>
