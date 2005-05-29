<?php
header("Content-Type: text/html;charset=utf-8");

if( ini_get("session.use_trans_sid") == 1 ) ini_set("session.use_trans_sid", 0 );

include_once("DB.php");
//include_once("config.inc.php");


// Code from http://www.phpcs.com/codes/COLORISATION-HTML-DES-LOGS-IRC/30393.aspx
function chooseColor($irc){
    switch($irc){
        case "0":$color=rgb2html(array(255, 255, 255));break;
        case "1":$color=rgb2html(array(0, 0, 0));break;
        case "2":$color=rgb2html(array(0, 0, 127));break;
        case "3":$color=rgb2html(array(0, 127, 0));break;
        case "4":$color=rgb2html(array(255, 0, 0));break;
        case "5":$color=rgb2html(array(127, 0, 0));break;
        case "6":$color=rgb2html(array(127, 0, 127));break;
        case "7":$color=rgb2html(array(255, 127, 0));break;
        case "8":$color=rgb2html(array(255, 255, 0));break;
        case "9":$color=rgb2html(array(0, 255, 0));break;
        case "10":$color=rgb2html(array(63, 127, 127));break;
        case "11":$color=rgb2html(array(0, 255, 255));break;
        case "12":$color=rgb2html(array(0, 0, 255));break;
        case "13":$color=rgb2html(array(255, 0, 255));break;
        case "14":$color=rgb2html(array(127, 127, 127));break;
        case "15":$color=rgb2html(array(191, 191, 191));break;
        default:$color=rgb2html(array(0, 0, 0));break;
    }
    return $color;
}

function irc2html($texte){

    $buffer = "";

    $is_bold=false;
    $is_under=false;
    $is_fg=false;
    $is_bg=false;
    $is_space=false;

    $fg=1;
    $bg=0;
    for($i=0;$i<strlen($texte);$i++){
        $chr = substr($texte,$i,1);
        $ord = ord($chr);

        switch($ord){
            case "10":
                //->Retour à la ligne, fermer toutes les balises ouvertes
                if($is_bold) {$buffer.= "</b>";$is_bold=false;}
                if($is_under) {$buffer.= "</u>";$is_under=false;}
                if($is_fg) {$buffer.= "</span>";$is_fg=false;}
                if($is_bg) {$buffer.= "</span>";$is_bg=false;}
                $is_space=false;
                //$buffer.= "<br>";
                break;

            case "2":
                //->Mettre en gras
                if($is_bold) {$buffer.= "</b>";$is_bold=false;}
                else {$buffer.= "<b>";$is_bold=true;}

                break;

            case "3":
                //->Mettre en couleur
                $fg1="";$fg2="";$bg1="";$bg2="";
                $i++;$chr = substr($texte,$i,1);
                if(ereg("[0-9]",$chr)){
                    $fg1=$chr;$i++;
                    $chr=substr($texte,$i,1);
                    if(ereg("[0-9]",$chr)){
                        $fg2=$chr;$i++;$chr=substr($texte,$i,1);
                    }

                    if($chr==","){
                        $i++;$chr = substr($texte,$i,1);
                        if(ereg("[0-9]",$chr)){
                            $bg1 = $chr;$i++;
                            $chr = substr($texte,$i,1);
                            if(ereg("[0-9]",$chr)){
                                $bg2=$chr;
                            }
                            else{
                                $i--;
                            }
                        }
                    }
                    else{
                        $i--;
                    }
                }
                $fg=($fg1.$fg2)+0;
                $bg=($bg1.$bg2)+0;
                //echo "<b>[C : ".$fg." / ".$bg."]</b>";
                if($is_fg){$buffer.= "</span>";$is_fg=false;}
                if($fg!=0) {$buffer.= "<span style='color:".chooseColor($fg).";'>";$is_fg=true;}

                if($is_bg){$buffer.= "</span>";$is_bg=false;}
                if($bg!=0) {$buffer.= "<span style='background-color:".chooseColor($bg).";'>";$is_bg=true;}

                break;

            case "15":
                //->Enlever les couleurs
                if($is_fg) {$buffer.= "</span>";$is_fg=false;}
                if($is_bg) {$buffer.= "</span>";$is_bg=false;}
                if($is_bold) {$buffer.= "</b>";$is_bold=false;}
                if($is_under) {$buffer.= "</u>";$is_under=false;}
                break;

            case "22":
                //->Inverser BG et FG
                if($is_fg) {$buffer.= "</span>";$is_fg=false;}
                if($is_bg) {$buffer.= "</span>";$is_bg=false;}

                $temp=$fg;
                $fg=$bg;
                $bg=$temp;

                $buffer.= "<span style='color:".chooseColor($fg).";'>";$is_fg=true;
                $buffer.= "<span style='background-color:".chooseColor($bg).";'>";$is_bg=true;

                break;

            case "31":
                //->Souligner
                if($is_under) {$buffer.= "</u>";$is_under=false;}
                else {$buffer.= "<u>";$is_under=true;}
                break;
            case "32":
                //->Espace
                if($is_space) {$buffer.= "&nbsp;";$is_space=false;}
                else {$buffer.=" ";$is_space=true;}
                break;
            default:
                //->Chr normal, afficher
                $buffer.= htmlentities($chr);
                break;
        }
    }
    return $buffer;
}


function htmlline($str) {
    //$str = mb_convert_encoding($str, "UTF-8", "ISO-8859-15");
    //$str = htmlspecialchars($str);

    $str = irc2html($str);

    $str = eregi_replace( "([[:alnum:]]+)://([^[:space:]]*)([[:alnum:]#?/&=])", "<a href=\"\\1://\\2\\3\" target=\"_blank\">\\1://\\2\\3</a>", $str);
    $str = eregi_replace( "(([a-z0-9_]|\\-|\\.)+@([^[:space:]]*)([[:alnum:]-]))", "<a href=\"mailto:\\1%s\" >\\1</a>", $str);
    return addslashes($str);
}

function formatNick($nick) {
    return htmlentities($nick);
}

function formatTime($time) {
    //lazy
    return substr($time,-8,2).":".substr($time,-5,2);
}

function getMessages($time=null) {
    static $DB;
    if(!isset($DB)) {
        $DB = DB::Connect("");
    }
    if( $time == null ) {
        $sql = "
            SELECT
                `time` , `action`, `nick` , `msg`
            FROM
                `ircmsg`
            ORDER BY
                `time` DESC
            LIMIT 0 , 10";
            $now = 0;
    } else {
        $sql = "
            SELECT
                `time` , `action`, `nick` , `msg`
            FROM
                `ircmsg`
            WHERE
                `time` > '{$time}'
            ORDER BY
                `time` DESC";
            $now = $time;
    }
    $res =& $DB->query($sql);
    if(DB::IsError($res)) {
        die("DB Error: ".$res->getMessage());
    }

    $times = "";
    $actions = "";
    $nicks = "";
    $mesgs = "";

    $tmp = array();
    while(list($time, $action,  $nick, $msg)=$res->fetchRow()) {
        $tmp[] = array('time' => $time, 'action' => $action, 'nick' => $nick, 'msg' => $msg);
    }

    $tmp = array_reverse($tmp);

    foreach($tmp as $row) {
        if($row['time'] > $now) $now = $row['time'];
        $times .= '"'.formatTime($row['time']).'",';
        $actions .= '"'.$row['action'].'",';
        $nicks .= '"'.formatNick($row['nick']).'",';
        $mesgs .= '"'.htmlline($row['msg']).'",';
    }

    $times = substr($times,0,strlen($times)-1);
    $actions = substr($actions,0,strlen($actions)-1);
    $nicks = substr($nicks,0,strlen($nicks)-1);
    $mesgs = substr($mesgs,0,strlen($mesgs)-1);

    return "new Array(\"{$now}\", new Array({$times}), new Array({$actions}), new Array({$nicks}), new Array({$mesgs}));";
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
body {
    font-family: monospace;
    font-size: 10px;
    color: #FF3C12;
}

p, td {
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

#irssi {
    background-image:url("/rautakuu/irssilogo.jpg");
    background-repeat:no-repeat;
    background-position:right bottom;
    width:100%;
    height:100%;
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

    // kertoo sen hetkisen ty�kentely divin.
    var workTR = null;

    for( var f=0; f<xmlResult[1].length; ++f) {
        // Array alkaa sijotuksella 0 niinp�+1:ht�ei tarvita

        workTR=document.createElement("TR");
        setStyle(workTR);

        var timeTD=document.createElement("TD");
        setText(timeTD, "["+xmlResult[1][f]+"]");
        workTR.appendChild(timeTD);

        var nickTD=document.createElement("TD");
        if ( xmlResult[2][f] == "PRIVMSG" ) {
            setText(nickTD, "&lt;"+xmlResult[3][f]+"&gt;");
            nickTD.style.color=colorNick(xmlResult[3][f]);
            nickTD.style.textAlign="right";
        } else {
            var nick = "<font color=\""+colorNick(xmlResult[3][f])+"\">"+xmlResult[3][f]+"</font>";
            setText(nickTD, "-<font color=\"#0000ff\"><strong>!</strong></font>- "+nick);
            nickTD.style.textAlign="left";
        }
        workTR.appendChild(nickTD);

        var msgTD=document.createElement("TD");
        if ( xmlResult[2][f] == "PRIVMSG" ) {
            setText(msgTD, xmlResult[4][f]);
        } else {
            var msg = "";
            if( xmlResult[4][f] != "" ) {
                msg = " ["+xmlResult[4][f]+"]";
            }
            setText(msgTD, "Has "+xmlResult[2][f]+msg);
        }
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
    tag.style.zIndex="5";
}

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
    var table=document.createElement("table");
    _appendId=document.createElement("tbody");
    table.appendChild(_appendId);
    var toAppend=document.getElementById("foo");
    toAppend.appendChild(table);

    _appendId

    buildLayout();
    requesterInit();
    mainLoop();
}

// T��kutsuu itse�n uudestaan ja uudestaan ja uudestaan...
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
    <body bgcolor="#ffffff" onLoad="init()">
        <div id="irssi">
            <div id="foo" style="z-index:5;"></div>
            <div id="fade"><img src="#" height="0" widht="0"></div>
        </div>
    </body>
</html>
