<?php

// Storage driver. currently supported:
// * DB - uses pear DB layer
// * logfile - reads messages from logfile
$storage = "logfile";

// "throtling".
// If system is near this AVG load, (%80),
// fetching new messages are delayed.
// Set $loadavg to false if throtling is not wanted
// (eg, in windows enviroment, it does not work).
$loadavg = 4;
$delaytime = 5;
$maxdelay = 10;

//
// logfile storage
//

// logfile
$logfile = "/home/isoteemu/irclogs/QuakeNET/#rautakuu.log";

// Starting offset in bytes (how many bytes are readed from end of file?)
$startoffsetbytes = 2048;

// Format of logfile. Currently supported:
// * mirc - For those who use m-IRC (or only compatible logfile)
// * irssi - For default irssi logfiles
// * egg - Eggdrop logfile (set quick-logs 1 on eggdrop to see dynamic updates)
$logfileformat = "irssi";

//
// DB storage
//

// DB dns
$dbdns = "mysql://miniteemu:*******@localhost/rautakuuirc";

// How many rows to show at begining?
$startrows = 20;

// Default channel
$channel = "#rautakuu";

header("Content-Type: text/html;charset=utf-8");
if( function_exists("putenv")) putenv('LANG="fi_FI.UTF-8');
if( function_exists("iconv_set_encoding") ) iconv_set_encoding("output_encoding", "UTF-8");
if( function_exists("mb_internal_encoding") ) mb_internal_encoding("UTF-8");

ini_set("default_charset", "uft-8");
ini_set("mbstring.encoding_translation", "on");

if( ini_get("session.use_trans_sid") == 1 ) ini_set("session.use_trans_sid", 0 );

// Code from http://www.phpcs.com/codes/COLORISATION-HTML-DES-LOGS-IRC/30393.aspx
function rgb2html($tablo) {
    //Vérification des bornes...
    /*
    for($i=0;$i<=2;$i++) {
        $tablo[$i]=bornes($tablo[$i],0,255);
    }
    */
    //Le str_pad permet de remplir avec des 0
    //parce que sinon rgb2html(Array(0,255,255)) retournerai #0ffff<=manque un 0 !
    return "#".str_pad(dechex(($tablo[0]<<16)|($tablo[1]<<8)|$tablo[2]),6,"0",STR_PAD_LEFT);
}

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
                $buffer.=htmlspecialchars($chr,ENT_QUOTES);
                break;
        }
    }
    return $buffer;
}


function htmlline($str) {
    if(function_exists("mb_convert_encoding"))
        $str = mb_convert_encoding($str, "UTF-8","UTF-8,Windows-1252,ISO-8859-15,ISO-8859-1");

    //$str = htmlspecialchars($str);

    $str = irc2html($str);

    $str = eregi_replace( "([[:alnum:]]+)://([^[:space:]]*)([[:alnum:]#?/&=])", "<a href=\"\\1://\\2\\3\" target=\"_blank\">\\1://\\2\\3</a>", $str);
    $str = eregi_replace( "(([a-z0-9_]|\\-|\\.)+@([^[:space:]]*)([[:alnum:]-]))", "<a href=\"mailto:\\1%s\" >\\1</a>", $str);
    return addslashes($str);
}

function formatNick($nick) {
    return htmlentities($nick, ENT_QUOTES);
}

function formatMircTime(&$time, $channel) {
    $times = explode(":", $time);
    $times = array_slice($times, 0, 3);
    while(count($times) < 3) {
        array_push($times, "00");
    }

    $time = mktime($times[0], $times[1], $times[2]);
}


function getMessagesDB(&$pos, $channel) {
    include_once("DB.php");

    global $dbdns, $startrows;
    static $DB;

    if(!isset($DB)) {
        $DB = DB::Connect($dbdns);
    }

    if( $pos == null ) {
        $sql = "
            SELECT
                `key`, UNIX_TIMESTAMP(`time`) , `action`, `nick` , `msg`
            FROM
                `ircmsg`
            WHERE
                `channel` = '@GLOBAL' OR
                `channel` = '{$channel}'
            ORDER BY
                `time` DESC, `key` DESC
            LIMIT 0 , {$startrows}";
            $pos = 0;
    } else {
        $sql = "
            SELECT
                `key`, UNIX_TIMESTAMP(`time`) , `action`, `nick` , `msg`
            FROM
                `ircmsg`
            WHERE
                `key` > '{$pos}' AND
                (
                    `channel` = '@GLOBAL' OR
                    `channel` = '{$channel}'
                )
            ORDER BY
                `time` DESC, `key` DESC";
    }
    $res =& $DB->query($sql);
    if(DB::IsError($res)) {
        die("Error: DB: ".$res->getMessage());
    }

    $times = "";
    $actions = "";
    $nicks = "";
    $mesgs = "";

    $results = array();
    while(list($key, $time, $action,  $nick, $msg)=$res->fetchRow()) {
        if($key > $pos) $pos = $key;
        $results[] = array('time' => $time, 'action' => $action, 'nick' => $nick, 'msg' => $msg);
    }

    $results = array_reverse($results);
    return $results;
}

function _parserMessagesMirc($log) {

    preg_match_all('/^\[([^\]]*)] (.*)$/mU', $log, $match, PREG_PATTERN_ORDER);

    $match = array_slice($match, 1);

    $times =& $match[0];

    array_walk($times,'formatMircTime');

    $results = array();

    foreach($match[1] as $key => $val) {

        // Is action?
        if(preg_match('/^\*\*\* (.*) (.*)$/U', $val, $tulitikut)) {
            $nick =& $tulitikut[1];
            $act  =& $tulitikut[2];

            if(preg_match('/^\(([^\)]*)\) has joined/',$act, $whom)) {
                $action = "JOIN";
                $msg    = $whom[1];
            } elseif(preg_match('/^has left .* \(([^\)]*)\)$/U',$act, $whom)) {
                // Crappy mirc. Part, left and quit are all logged as left.
                $action = "QUIT";
                $msg    = $whom[1];
            } elseif(preg_match('/^is now known as (.*)$/U',$act, $whom)) {
                $action = "NICK";
                $msg    = $whom[1];
            } elseif(preg_match('/^sets mode: (.*)$/U',$act, $whom)) {
                $action = "MODE";
                $msg    = $whom[1];
            } elseif(preg_match('/^was kicked by .* \(([^\)]*)\)$/U',$act, $whom)) {
                $action = "KICK";
                $msg    = $whom[1];
            } else {
                continue;
            }

        } elseif (preg_match('/^<([^>]*)> (.*)$/U', $val, $tulitikut)) {
            $action = "PRIVMSG";
            $nick   = $tulitikut[1];
            $msg    = $tulitikut[2];
        } else {
            continue;
        }
        $results[] = array(
            'time'     => $times[$key],
            'action'   => $action,
            'nick'     => $nick,
            'msg'      => $msg
        );
        //$results[count($results)-1]['raw'] = $val;
    }
    return $results;
}

function _parserMessagesIrssi($log) {

    preg_match_all('/^([\d:]*) (.*)$/mU', $log, $match, PREG_PATTERN_ORDER);

    $match = array_slice($match, 1);

    $times =& $match[0];

    array_walk($times,'formatMircTime');

    $results = array();

    foreach($match[1] as $key => $val) {

        // Is action?
        if(preg_match('/^-!- (.*) (.*)$/U', $val, $tulitikut)) {
            $nick =& $tulitikut[1];
            $act  =& $tulitikut[2];

            if(preg_match('/^\[([^\]]*)\] has joined/U',$act, $whom)) {
                $action = "JOIN";
                $msg    = $whom[1];
            } elseif(preg_match('/^\[[^\]]*\] has left .* \[([^\]]*)\]$/U',$act, $whom)) {
                $action = "PART";
                $msg    = $whom[1];
            } elseif(preg_match('/^\[[^\]]*\] has quit \[([^\]]*)\]$/U',$act, $whom)) {
                $action = "QUIT";
                $msg    = $whom[1];
            } elseif(preg_match('/^is now known as (.*)$/U',$act, $whom)) {
                $action = "NICK";
                $msg    = $whom[1];
            } elseif(preg_match('/mode\/.* \[([^\]]*)\] by (.*)$/U',$val, $whom)) {
                $action = "MODE";
                $msg    = $whom[1];
                $nick   = $whom[2];
            } elseif(preg_match('/^was kicked from .* \[([^\]]*)\]/U',$act, $whom)) {
                $action = "KICK";
                $msg    = $whom[1];
            } else {
                continue;
            }

        } elseif (preg_match('/^<([^>]*)> (.*)$/U', $val, $tulitikut)) {
            $action = "PRIVMSG";
            // First character in nicks in irssi logs is mode character
            $nick   = substr($tulitikut[1],1);
            $msg    = $tulitikut[2];
        } else {
            continue;
        }
        $results[] = array(
            'time'     => $times[$key],
            'action'   => $action,
            'nick'     => $nick,
            'msg'      => $msg
        );
    }
    return $results;
}

function _parserMessagesEgg($log) {

    preg_match_all('/^\[([^\]]*)] (.*)$/mU', $log, $match, PREG_PATTERN_ORDER);

    $match = array_slice($match, 1);

    $times =& $match[0];

    array_walk($times,'formatMircTime');

    $results = array();

    foreach($match[1] as $key => $val) {

        // Is action?
        if (preg_match('/^<([^>]*)> (.*)$/U', $val, $tulitikut)) {
            $action = "PRIVMSG";
            $nick   = $tulitikut[1];
            $msg    = $tulitikut[2];
        } elseif (preg_match('%(.*) (.*)$%U', $val, $tulitikut)) {
            $nick =& $tulitikut[1];
            $act  =& $tulitikut[2];


            if(preg_match('/^\(([^\)]*)\) joined .*$/U',$act, $whom)) {
                $action = "JOIN";
                $msg    = $whom[1];
            } elseif (preg_match('/^\([^\)]*\) left irc: (.*)$/U',$act, $reason)) {
                $action = "QUIT";
                $msg    = $reason[1];
            } elseif (preg_match('/^\([^\)]*\) left .*\(([^\)]*)\)/U',$act, $reason)) {
                $action = "PART";
                $msg    = $reason[1];
            } elseif (preg_match('/^\([^\)]*\) left .*$/U',$act, $reason)) {
                // Without reason
                $action = "PART";
                $msg    = "";
            } elseif (preg_match('/^Nick change: (.*) -> (.*)$/U',$val, $whom)) {
                $action = "NICK";
                $nick   = $whom[1];
                $msg    = $whom[2];
            } elseif (preg_match('/^[^:]*: mode change \'([^\']*)\' by ([^!]*)!.*/U',$val, $mode)) {
                $action = "MODE";
                $msg    = $mode[1];
                $nick   = $mode[2];
            } elseif (preg_match('/^kicked from .* by [^:]*: (.*)$/U',$act, $reason)) {
                $action = "KICK";
                $msg    = $reason[1];
            } else {
                //die(__LINE__.$val);
                continue;
            }

        } else {
            //die(__LINE__.$val);
            continue;

        }
        $results[] = array(
            'time'     => $times[$key],
            'action'   => $action,
            'nick'     => $nick,
            'msg'      => $msg
        );
    }
    return $results;
}


function getMessagesLogfile(&$pos,$channe=null) {
    global $logfile, $logfileformat, $startoffsetbytes;
    if(!file_exists($logfile)) {
        die("Error: logfile '{$logfile}' does not exists");
    }

    if(!$fp = fopen($logfile, "r")) {
        die("Error: Error opening logfile '{$logfile}' handler");
    }
    $totalsize = filesize($logfile);

    // Move pointer
    if($pos == null) {
        if($startoffsetbytes > $totalsize) $startoffsetbytes = $filesize;
        fseek($fp, -$startoffsetbytes, SEEK_END);
        $readAmmount=$startoffsetbytes;
    } else {
        $pos = intval($pos);
        if($pos < 0 || $pos > $totalsize) {
            // You fuckwad
            fseek($fp, -$startoffset, SEEK_END);
            $readAmmount=$startoffsetbytes;
        } else {
            fseek($fp, $pos);
            $readAmmount=$totalsize-$pos;
        }
    }

    if($readAmmount <= 0) return array();

    // read logfile
    $log = fread($fp, $readAmmount);
    $pos = ftell($fp);
    fclose($fp);

    switch(strtolower($logfileformat)) {
        case "mirc" :
            $results = _parserMessagesMirc($log);
            break;
        case "egg" :
            $results = _parserMessagesEgg($log);
            break;
        case "irssi" :
            $results = _parserMessagesIrssi($log);
            break;
        default :
            die("Unknown logtype {$logfileformat}");
    }

    return $results;

}

function getMessages($channel="#rautakuu",$time=null) {
    global $storage;

    if(substr($channel,0,1) != "#") $channel = "#".$channel;

    switch($storage) {
        case "DB" :
            $results = getMessagesDB($time,$channel);
            break;
        case "logfile" :
            $results = getMessagesLogfile($time,$channel);
            break;
        default :
            die("No usable driver");
            break;
    }

    foreach($results as $row) {
        $times .= '"'.$row['time'].'",';
        $actions .= '"'.$row['action'].'",';
        $nicks .= '"'.formatNick($row['nick']).'",';
        if($row['action'] == "PRIVMSG")
            $mesgs .= '"'.htmlline($row['msg']).'",';
        else
            $mesgs .= '"'.htmlentities($row['msg'], ENT_QUOTES, "UTF-8").'",';
    }

    $times = substr($times,0,strlen($times)-1);
    $actions = substr($actions,0,strlen($actions)-1);
    $nicks = substr($nicks,0,strlen($nicks)-1);
    $mesgs = substr($mesgs,0,strlen($mesgs)-1);

    return "new Array(\"{$time}\", new Array({$times}), new Array({$actions}), new Array({$nicks}), new Array({$mesgs}));";
}

/**
 * This function calculates load avarage for linux system.
 * This data is then used to add litle delay to page load, so
 * system won't be totally trashed
 * Code partly stolen from phpsysinfo
 * http://cvs.sourceforge.net/viewcvs.py/phpsysinfo/phpsysinfo-dev/includes/os/class.Linux.inc.php
 *
 * @return estimate of load avarage of system total capacity in percents
 */

function loadavg() {
    if ($fd = fopen('/proc/loadavg', 'r')) {
        $avg = preg_split("/\s/", fgets($fd, 4096),4); // We wan't only the most curren avarage
        fclose($fd);
        return $avg[0];
    } else {
        return false;
    }
}


if(empty($_GET['channel'])) $_GET['channel'] = $channel;

if(isset($_GET['time'])) {

    // It really does not matter if updates aren't instant.
    // Use sleep so if server is under load, frequent updates
    // won't trash it totally.
    if($loadavg != false) {
        $load = loadavg();
        $loadprc = ($load/$loadavg);
        if($loadprc > 0.80) {
            // calculate how log to delay.
            $delay = ($loadprc-0.8)*5*$delaytime;
            if($delay > $maxdelay) $delay = $maxdelay;
            sleep($delay);
        }
    }

    die(getMessages($_GET['channel'], $_GET['time']));
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<!-- Irclog.php: Copyright 2006 Teemu A <teemu@rautakuu.org>. irclog is under the GPL.    -->
<!-- Irclog.php: http://svn.rautakuu.org/repos/homebrevcomputing/rautakuusivut/irclog.php -->
<!-- GNU Public License: http://www.fsf.org/copyleft/gpl.html                             -->
<html>
    <head>
        <title><?= htmlspecialchars($_GET['channel']);?> IRC viestit</title>
        <meta http-equiv="Content-Language" content="fi">
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <style>
body {
    font-family: Monospace;
    font-size: 10px;
    color: #7b7b7b;
}

p, td {
    font-family: Monospace;
    font-size: 10px;
    color: #7b7b7b;
}

a:link, a:visited, a:active {
    text-decoration: none;
    color: #005D93;
}

a:hover {
    text-decoration: underline;
     color: #990000;
}

#fadevert {
    background-image:url("/rautakuu/towhite-vert.png");
    background-repeat:repeat-y;
    background-position:right center;
    z-index:20;
    width:25px;
    top:0;
    bottom:0;
    right:0;
    position:fixed;
}

#fadehori {
    background-image:url("/rautakuu/towhite-hori.png");
    background-repeat:repeat-x;
    background-position:center top;
    z-index:21;
    height:25px;
    width:100%;
    top:0;
    left:0;
    right:0;
    position:fixed;
}
/*
#irssi {
    background-image:url("/rautakuu/irssilogo.jpg");
    background-repeat:no-repeat;
    background-position:right bottom;
    bottom:0;
    right:0;
    width:100%;
    height:100%;
    position:fixed;
}
*/

#container {
    width:100%;
    height:100%;
    position:absolute;
}

#foo {
    background:inherit;
}

        </style>
        <script>

var topuri = "<?= $_SERVER['REQUEST_URI'] ?>?time=";

var channelparam = "&amp;channel=<?= urlencode($_GET['channel']); ?>";

var xmlResult = <?= getMessages($_GET['channel']);?>

var xmlHttp = null;

var _sleepTime=_sleepDefTime= 1;

var _appendId = "viestit";

var nickColors = new Array();

// Estää tupla refreshauksen
var _refreshing = false;

function getXMLHTTPResult() {
    if(_refreshing==true) {
        return false;
    } else if(xmlHttp&&xmlHttp.readyState!=0) {
        xmlHttp.abort();
        requesterInit();
    } else if(!xmlHttp) {
        // Turhaa edes yrittää
        _refreshing = true;
    } else {
        if(_refreshing == false ) {
            var openUri=topuri+xmlResult[0]+channelparam;
            _refreshing = true;
            xmlHttp.open("GET",openUri,true);
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

    if(xmlResult[1].length < 1) return;

    // kertoo sen hetkisen työskentely divin.
    var workTR = null;

    for( var f=0; f<xmlResult[1].length; ++f) {
        // Array alkaa sijotuksella 0 niinp�+1:ht�ei tarvita

        workTR=document.createElement("TR");
        setStyle(workTR);

        var timeTD=document.createElement("TD");
        setText(timeTD, "["+unixtimetodate(xmlResult[1][f])+"]");
        workTR.appendChild(timeTD);

        var nickTD=document.createElement("TD");
        if ( xmlResult[2][f] == "PRIVMSG" ) {
            setText(nickTD, "&lt;"+xmlResult[3][f]+"&gt;");
            nickTD.style.color=colorNick(xmlResult[3][f]);
            nickTD.style.textAlign="right";
        } else {
            var nick = "<font color=\""+colorNick(xmlResult[3][f])+"\">"+xmlResult[3][f]+"</font>";
            setText(nickTD, "-<font color=\"#0000ff\"><strong>!</strong></font>- "+nick);
            nickTD.style.textAlign="right";
        }
        workTR.appendChild(nickTD);

        var msgTD=document.createElement("TD");
        switch (xmlResult[2][f]) {
            case "NICK" :
                setText(msgTD, "is now known as <font color=\""+colorNick(xmlResult[4][f])+"\">"+xmlResult[4][f]+"</font>");
                break;
            case "PART" :
                setText(msgTD, "has left ["+xmlResult[4][f]+"]");
                break;
            case "QUIT" :
                setText(msgTD, "has quit ["+xmlResult[4][f]+"]");
                break;
            case "JOIN" :
                setText(msgTD, "has joined");
                break;
            case "KICK" :
                setText(msgTD, "was kicked ["+xmlResult[4][f]+"]");
                break;
            case "MODE" :
                setText(msgTD, "mode ["+xmlResult[4][f]+"] by <font color=\""+colorNick(xmlResult[3][f])+"\">"+xmlResult[3][f]+"</font>");
                break;
            case "TOPIC" :
                setText(msgTD, "changed the topic to "+xmlResult[4][f]);
                break;
            default :
            case "PRIVMSG" :
                setText(msgTD, xmlResult[4][f]);
                break;
        }
        /*
        if ( xmlResult[2][f] == "PRIVMSG" ) {
            setText(msgTD, xmlResult[4][f]);
        } else {
            var msg = "";
            if( xmlResult[4][f] != "" ) {
                msg = " ["+xmlResult[4][f]+"]";
            }
            setText(msgTD, "Has "+xmlResult[2][f]+msg);
        }
        */
        workTR.appendChild(msgTD);

        _appendId.appendChild(workTR);
    }
    scrollme();
    getSleepTimer();
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

function unixtimetodate(time) {
    var theDate = new Date(time * 1000);
    //dateString = formatInt(theDate.getHours())+":"+formatInt(theDate.getMinutes())+":"+formatInt(theDate.getSeconds());
    dateString = formatInt(theDate.getHours())+":"+formatInt(theDate.getMinutes());
    return dateString;
}

function formatInt(sstr) {
    var str = new String(sstr);
    if(str.length < 2) str = "0"+str;
    return str;
}

/**
 * Resetoi ajan pienimmilleen
 */
function getSleepTimer() {
    _sleepTime = _sleepDefTime;
}

function getTimer() {
    //return getSleepTimer*1000;
    if(_sleepTime < 15 ) ++_sleepTime;
    return _sleepTime*1000;
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

function scrollme(){
    var mypos=window.innerHeight+window.pageYOffset;
    if (mypos<document.getElementById("foo").offsetHeight) {
        window.scrollBy(0,2);
        setTimeout("scrollme()",50)
    }
}

        </script>
    </head>
    <body bgcolor="#ffffff" onLoad="init()">
        <div id="container">
            <!--<div id="irssi"></div>-->
            <div id="foo"><noscript>Sorry peipe, tämä vaatii javascriptin :/</noscript></div>
            <div id="fadevert" style="bottom:0px;"><img src="#" height="0" widht="0"></div>
            <div id="fadehori" style="top:0px;"><img src="#" height="0" widht="0"></div>
        </div>
    </body>
</html>
