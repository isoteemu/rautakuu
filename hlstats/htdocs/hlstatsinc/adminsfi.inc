<?php

if(!defined("_HLSTATS")) die("Direct access denied.");

if(isset($_GET['js'])) {
    header("Content-Type: text/javascript");
?>
var xmlHttp;

var players = new Array();

function getUri() {
    var _url = uri;
    for( var f=0; f<players.length; ++f) {
        _url = _url+"&player[]="+players[f];
    }
    return _url;
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

function getXMLHTTPResult() {
    if(xmlHttp&&xmlHttp.readyState!=0) {
        xmlHttp.abort();
        requesterInit();
        return false;
    } else {
        xmlHttp.open("GET",getUri(),true);
        xmlHttp.onreadystatechange=parseResult;
        xmlHttp.send(null);
        return true;
    }
}

function parseResult() {
    if(xmlHttp.readyState==4&&xmlHttp.responseText) {
        var xmlResult = eval(xmlHttp.responseText);

        if(xmlResult.length < 1) return false;

        for( var f=0; f<xmlResult.length; ++f) {
            var _uasf=document.getElementById(xmlResult[f][0]);
            if(_uasf!=undefined) {
                _uasf.innerHTML = xmlResult[f][1];
            }
        }
    }
}

function uasf() {
    requesterInit();
    if(!getXMLHTTPResult()) {
        // If got not ready or other crap, wait sec and retry.
        setTimeout("uasf()", 5000);
    }
}

function addPlayer(id) {
    players.push(id);
}
<?php
    die();
}

// We depend on curl POST handling, due stupid coder @ admins.fi
if(! function_exists("curl_init")) {
    die();
} elseif($HTTP_GET_VARS["player"] && $HTTP_GET_VARS["game"]) {
    if(!is_array($HTTP_GET_VARS["player"])) {
        die("<!-- Pass player ids as array -->");
    }
    $game = strval($HTTP_GET_VARS["game"]);

    foreach($HTTP_GET_VARS["player"] as $player) {
        $player = intval($player);

        if(!isset($pquery))
            $pquery = "( playerId='$player' OR uniqueId='$player' )";
        else
            $pquery .= " OR ( playerId='$player' OR uniqueId='$player' )";
    }
    $res = $db->query("
        SELECT
            playerId,uniqueId
        FROM
            hlstats_PlayerUniqueIds
        WHERE
            {$pquery}
            AND game='$game'
    ");

    if (($nr = $db->num_rows($res)) < 1) {
        die("<!-- Returned {$nr} rows for playerid -->");
    } else {
        while($rowdata = $db->fetch_array($res)) {
            $uniqueid[$rowdata[0]] = $rowdata[1];
        }
    }
} elseif($HTTP_GET_VARS["uniqueid"]) {
    $uniqueid = array($HTTP_GET_VARS["uniqueid"]);
} else {
    die("<!-- Specify player and game or uniqueid -->");
}


class getbans {

    var $curl;

    var $steamid;
    var $url;

    // List some browser, for backup.
    var $browsers = array(
        "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)",
        "Mozilla/5.0 (Windows; U; Win98; en-US; rv:1.7.8) Gecko/20050511 Firefox/1.0.4",
        "Mozilla/5.0 (compatible; Konqueror/3.4; Linux) KHTML/3.4.91 (like Gecko)",
        "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322)",
        "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9a1) Gecko/20050914 Firefox/1.6a1",
        "Mozilla/4.0 (compatible; MSIE 6.0; X11; Linux i686; en) Opera 8.5",
        "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; fi) Opera 8.50",
        "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; en) Opera 8.02",
        "Mozilla/5.0 (compatible; Konqueror/3.3; Linux) (KHTML, like Gecko)"
    );

    var $browser;

    function getbans($steamid) {
        $this->steamid = urlencode(strval($steamid));

        // Init curl
        $this->curl = curl_init();

        // curl_exec default outputting result. Obiously not wanted
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        // We don't want header on our results. What would we do with that?
        curl_setopt($this->curl, CURLOPT_HEADER, 0);

        $this->browser = $_SERVER['HTTP_USER_AGENT'];
        if(!$this->browser) $this->browser = $this->browsers[array_rand($this->browsers)];
        curl_setopt($this->curl, CURLOPT_USERAGENT, $this->browser);
    }

    function url($url=null) {
        if($url !== null) $this->url = $url;
        return $this->url;
    }

    function fetch() {
        // Search from here.
        curl_setopt($this->curl, CURLOPT_URL, $this->url);
        $res = curl_exec($this->curl);
        $this->_closecurl();
        return $res;
    }

    function _closecurl() {
        curl_close($this->curl);
    }

    function utfify($str) {
        $str = mb_convert_encoding($str, "UTF-8");
        $str = preg_replace('/\s+/', ' ', $str); // strip crap
        return $str;
    }

    function getBan() {
        $res = $this->fetch();
        return $res;
    }

    function result() {
        return $this->getBan();
    }

}

class getbans_amxbans extends getbans {
    function getbans_amxbans($steamid) {
        $this->getbans($steamid);

        // amxbans uses POST.
        curl_setopt($this->curl, CURLOPT_POST, 1);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, "steamid=".$this->steamid);
    }

    function getBan() {
        $str = parent::getBan();
        $str = $this->utfify($str);

        preg_match('%<td[^>]*>.*(Active.bans|Aktiiviset.bannit).*</td>.*<table[^>]*class=\'listtable\'[^>]*>(.*)</table>%mU', $str, $sec);
        preg_match('|<tr[^>]*onClick=\"document\.location.=.\'(.[^\']*)\';\"[^>]*>|mU', $sec[2], $rows);

        $rows = array_slice($rows,1);
        return $rows;
    }
}

class getbans_adminsfi extends getbans {

    function getbans_adminsfi($steamid) {

        // Init parent
        $this->getbans($steamid);

        // Admins.fi uses only POST method.
        curl_setopt($this->curl, CURLOPT_POST, 1);

        // Define post variables. Only steamID for now. But for future, use : as field separator
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, "search=".$this->steamid);

        // Referer, in case they start checking this.
        curl_setopt($this->curl, CURLOPT_REFERER, "http://admins.fi/banlist/bans");
    }

    function getBan() {
        $str = parent::getBan();
        $str = $this->utfify($str);

        preg_match('|<table[^>]*class=\"banlist\"[^>]*>.*</table>|mU', $str, $table);
        preg_match_all('|<tr>(.*)</tr>|U', $table[0], $rows, PREG_SET_ORDER);

        // first row is crap. Delete it
        $rows = array_slice($rows,1);

        if(count($rows) != 1) {
            // Propably no matches (Good for him) or too many.
            return "<!-- count(\$rows) != 1 ".count($rows)." -->";
        }

        preg_match('|<td>.*<a href=\"(.[^\"]*)\".*>.*</a>.*</td>|U',$rows[0][0],$uasfID);

        // First result is TD, second is ID link, which is what we want.
        if(!isset($uasfID[1])) {
            return "<!-- !isset(\$uasfID[1]) -->";
        }

        $img = getImage("/uasf_24");
        if($img) {
            return '<a href="'.$uasfID[1].'" title="'._("Banned by United Admins Society of Finland").'"><img src="'.$img['url'].'" '.$img['size'].' alt="UASF" border="0" hspace="3" /></a>';
        } else {
            return  '<a href="'.$uasfID[1].'" title="'._("Banned by United Admins Society of Finland").'"><sup>UASF</sup></a>';
        }
    }
}

class getbans_auria extends getbans_amxbans {
    function getbans_auria($steamid) {
        $this->getbans_amxbans($steamid);
    }

    function result() {
        $rows = $this->getBan();
        if(count($rows) >= 1) {
            $img = getImage("/auria");
            if($img) {
                return '<a href="http://area51.pelikaista.net'.$rows[0].'" title="'._("Banned by Auria Pelikaista").'"><img src="'.$img['url'].'" '.$img['size'].' alt="Auria" border="0" hspace="3" /></a>';
            } else {
                return  '<a href="http://area51.pelikaista.net'.$rows[0].'" title="'._("Banned by Auria Pelikaista").'"><sup>Auria</sup></a>';
            }
        }
    }
}

class getbans_hlstatsx extends getbans {
    function getbans_hlstatsx($steamid) {
        $this->getbans($steamid);
    }
}

class getbans_steambans extends getbans {
    function getbans_steambans($steamid) {
        $this->getbans($steamid);
    }

    function result() {
        $this->url($this->url()."&".urlencode($this->steamid));
        $str = $this->getBan();
        $str = $this->utfify($str);
    }

}

$bans = "";

if($g_options['useCache']) {
    $cache_options = array(
        'cacheDir' => $g_options['cacheDir'],
        'lifeTime' => 86400,
        'pearErrorMode' => CACHE_LITE_ERROR_DIE
    );
    $b_cache = new Cache_Lite($cache_options);
} else {
    class Cache_Fake {
        function get() { return false; }
        function save() { return false; }
    }
    $b_cache = new Cache_Fake();
}

foreach($uniqueid as $key => $val) {

    $bans .= 'new Array("'.$key.'", "';

    if(!($bansfetch = $b_cache->get($val,"adminsfi"))) {

        $bansfetch = "";

        $uasf = new getbans_adminsfi($val);
        $uasf->url("http://admins.fi/banlist/bans/search/");

        /**
        * @todo make this right(tm). Temporary solution
        */
        curl_setopt($uasf->curl, CURLOPT_PROXY, "http://cache.inet.fi:8080");

        $bansfetch .= $uasf->result();

        $auria = new getbans_auria($val);
        $auria->url("http://area51.pelikaista.net/csbans/ban_search.php");

        /**
        * @todo make this right(tm). Temporary solution
        */
        curl_setopt($auria->curl, CURLOPT_PROXY, "http://cache.inet.fi:8080");

        $bansfetch .= $auria->result();

        // save to cache
        $b_cache->save($bansfetch,$val,"adminsfi");
    }

    $bans .= addslashes($bansfetch).'"),';
}

$bans = "new Array(".substr($bans,0,strlen($bans)-1).");";

/*
$uasf = new getbans_adminsfi($uniqueid);
$uasf->url("http://admins.fi/banlist/bans/search/");
$bans .= $uasf->result();

$auria = new getbans_auria($uniqueid);
$auria->url("http://area51.pelikaista.net/csbans/ban_search.php");
$bans .= $auria->result();


$hlstatsx = new getbans_hlstatsx($HTTP_GET_VARS["uniqueid"]);
$hlstatsx->url("http://www.hlstatsx.com/vacbans?&banlist_filter_type=steam_id");
$bans .= $hlstatsx->result();
*/


pageFooter($bans,false,86400);
