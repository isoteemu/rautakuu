<?php
header("Content-Type: text/html;charset=utf-8");

require_once("config.inc.php");
include_once("DB.php");
include_once("Text/Wiki.php");

session_start();

// Asetetaan suomen locale
putenv("LANG", "fi_FI.utf8");
setlocale(LC_ALL, "fi_FI.utf8");
mb_internal_encoding("utf-8");

// Sivu
$page = basename(substr($_SERVER['PATH_INFO'], 1));
if( empty( $page )) $page = "EtuSivu";

$notfound = false;
$content = "";

$db = DB::Connect($settings['horde']['db']);

// WIKI
$wiki =& new Text_Wiki();
$wiki->setRenderConf('xhtml', 'wikilink', 'view_url', "/rautakuu/index.php/");
$wiki->setRenderConf('xhtml', 'wikilink', 'new_url', '');
$wiki->setRenderConf('xhtml', 'wikilink', 'new_text', '<!--NO WARRANTY-->');

$wiki->setRenderConf('xhtml', 'freelink', 'view_url', "/rautakuu/index.php/");
$wiki->setRenderConf('xhtml', 'freelink', 'new_url', "/rautakuu/index.php/");
$wiki->setRenderConf('xhtml', 'freelink', 'new_text', '<!--NO WARRANTY-->');

$wiki->setRenderConf('xhtml', 'url', 'target', '');
$wiki->setRenderConf('xhtml', 'url', 'images', false);
//$wiki->setRenderConf('xhtml', 'image', 'base', 'liitteet/');

$wiki->setRenderConf('xhtml', 'freelink', 'pages', $pages);
$wiki->setRenderConf('xhtml', 'wikilink', 'pages', $pages);

// /WIKI


if( file_exists( "pages/".$page.".inc")) {
    include("pages/".$page.".inc");
    $title = " :: $page";
} else {

    // Jos sivu alkaa "Priv", vaadi autentikointi
    if(substr($page,0,4) == "Priv") {
        $user = addslashes($_SERVER['PHP_AUTH_PW']);
        $uidRes = $cdb->query("
            SELECT `password`
            FROM `accountuser`
            WHERE `username` LIKE '{$user}'");

    }

    /*
    header('WWW-Authenticate: Basic realm="Suojattu nick"');
    header('HTTP/1.0 401 Unauthorized');
    echo "<strong><font color=\"red\">Suojattu nick. Viestiä ei lähetetty.</font></strong>";
    */

    // Hae wiki sivu
    $pres = &$db->query("SELECT `page_text`, `change_author`, `version_created`  FROM `wicked_pages` WHERE 1 AND `page_name` = '".addslashes($page)."'");

    if( $pres->numRows() >= 1 ) {
        $title = " :: $page";
        $rtxt = $pres->fetchRow();
        $lmtime = gmstrftime( "%a, %d %b %Y %T %Z", $rtxt[2]);

        $ares = &$db->query("SELECT `page_name` FROM `wicked_pages`");

        while ( $apage = &$ares->fetchRow() ) {
            $pages[] = $apage[0];
        }

        // Hae lisäksi pages kansion sivut
        $d = dir("pages/");
        while (false !== ($entry = $d->read())) {
            if(is_dir( $entry )) continue;
            if(substr( $entry, -4 ) != ".inc") continue;
            $pages[] = substr($entry, 0, strlen($entry)-4);
        }

        $db->query("UPDATE `wicked_pages` SET page_hits = page_hits + 1 WHERE page_name = '$page'");

        $content .= $wiki->transform($rtxt[0], 'Xhtml');
        // Muutetaan UTF-8 koodiksi
        $content = mb_convert_encoding($content, "utf-8", "iso-8859-1");
        $content .= "<p>&nbsp;</p><p>".strftime( "%c", $rtxt[2] )."/<a href=\"mailto:".$rtxt[1]."@rautakuu.org\">".$rtxt[1]."</a></p>";
    } else {
        $notfound = true;
    }
}

if( isset($wiki) ) {
    $linkit =& $wiki->getTokens(array("Wikilink","Url","Freelink"));

}

if( isset($lmtime)) Header("Last-Modified: ".$lmtime);
if( $notfound == true ) {
    header("Status: 404 Not Found");
} else {
    include_once("inc/forum.inc.php");
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
    <meta http-equiv="Content-Language" content="fi">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <style type="text/css">
body {margin: 0px; padding: 0px; background-color: white;}
#m {font-family: verdana; font-size: 10px; padding-top: 0px}
#o {background-image: url('http://rautakuu.org/rautakuu/splash.jpg'); background-repeat:no-repeat; font-family: verdana; font-size: 10pt; padding-top: 200px; color: #000000;}
#l {background-image: url('http://rautakuu.org/rautakuu/nimeton_1.jpg'); background-repeat:no-repeat; font-family: verdana; font-size: 10pt; padding-top: 200px; color: #000000;}
#i, #i table {font-family: verdana; font-size: 10px; padding-top: 0px; color: #FF3C12; width: 407px;}
a:link #m, a:visited #m, a:active #m {text-decoration: none; color: #B72000;}
a:hover #m {text-decoration: underline; color: #7b7b7b;}
a:link, a:visited, a:active {text-decoration: none; color: #545454;}
a:hover {text-decoration: none; color: #7A7A7A;}
.header {background-image: url('http://rautakuu.org/rautakuu/header.jpg'); background-repeat:no-repeat; font-family: verdana; font-size: 12px; padding-left: 20px; color: #FF3C12; height:19px;}

input, textarea {
    border-color:#FF3C12;
    border-style:solid;
    border-width:1px;
    color:#545454;
    font-family:verdana;
    font-size:10px;
    visibility:inherit;
}

</style>
    <base href="http://rautakuu.org/rautakuu/">
    <title>Rautakuu [dot] org<?php if(isset($title)) echo " $title";?></title>
<!--[if IE 6]><link rel="stylesheet" href="noIE/noIE.css" type="text/css"><![endif]-->
<script language="JavaScript">
    <!-- Begin
         var irc;
         function ircWindow() {
             irc = window.open("http://rautakuu.org/cgi-bin/cgiirc/irc.cgi","Rautakuu [dot] org :: IRC","toolbar=no,scrollbars,width=780,height=620");
             irc.focus();
         }
    // End -->
  </script>
</head>

<body>

  <table border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td id="o" valign="top">
        <p align="center">
        <a href="http://rautakuu.org/rautakuu/index.php/AllPages" style="color: #000000;">Rautakuu [dot] org</a></p>
      </td>
      <td id="l" valign="top" width="407" height="221" align="left">[
          <a href="https://horde.rautakuu.org" title="Siirry sähköpostiin">Horde</a> |
          <a href="http://rautakuu.org/hlstats" title="Counter Striken tilastot">HLStats</a> |
          <a href="javascript:ircWindow()" onRelease="ircWindow();" title="CGI:IRC">#rautakuu</a> ]
      </td>
    </tr>
    <tr>
      <td align="left" colspan="2"><img src="nimeton_2.jpg" alt="" border="0" width="312" height="19"></td>
    </tr>
    <tr>
      <td valign="top"><img src="nimeton_3.jpg" width="312" alt="" border="0"  height="245">
          <p>&nbsp;</p>'
          <p>
<?php
if(isset($linkit)) {
    foreach($linkit as $key => $val ) {
        echo "$key, $val<br>"
    }
}
?>
          </p>
              <p align="center">
<?php

if(isset($_SERVER['HTTP_USER_AGENT']) &&
    preg_match('/msie.*.(win)/i',$_SERVER['HTTP_USER_AGENT']) &&
    !preg_match('/opera/i',$_SERVER['HTTP_USER_AGENT'])) {
    sleep(rand(1,6)/3);

?>
                  <script type="text/javascript"><!--
                                google_ad_client = "pub-3452268181804196";
                                google_ad_width = 120;
                                google_ad_height = 240;
                                google_ad_format = "120x240_as";
                                google_ad_channel ="";

                                 google_color_border = "FF3C12";
                                 google_color_bg = "FFFFFF";
                                 google_color_link = "000000";
                                 google_color_url = "666666";
                                 google_color_text = "FF3C12";
                                 //--></script>
                  <script type="text/javascript"
                      src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
                  </script>
<?php
}
?>
            </p>
          </td>
      <td valign="top" id="i">
<?php

if($notfound == true) {
    if(file_exists("pages/404.inc")) {
        include_once("pages/404.inc");
    } else {
        $content .= '
<p><strong>404: Sivua ei löytynyt.</strong></p>
<p>
    Siirry:
    <blockquote>
        <a href="index.php/AllPages">Kaikki dokumentoinnin sivut</a><br>
        <a href="index.php/PageSearch">Etsi Rautakuu [dot] org palvelimelta</a>
    </blockquote>
</p>
';
    }
}
echo $content;

if($notfound != true) {
    echo '<p>';
    echo $forum;
    echo '</p>';
}
?>

<p>
    [
    <a href="https://horde.rautakuu.org" title="Siirry sähköpostiin">Horde</a> |
    <a href="http://rautakuu.org/hlstats" title="Counter Striken tilastot">HLStats</a> |
    <a href="javascript:ircWindow()" onRelease="ircWindow();" title="CGI:IRC">#rautakuu</a>
    ]
</p>
<p>
&nbsp;
</p>
<p>
[
<!-- Creative Commons Lisenssi -->
<a rel="license" href="http://creativecommons.org/licenses/by-sa/1.0/fi/">Creative Commons</a>.
<!-- /Creative Commons Lisenssi -->
|
<a href=http://Finnish-29996965986.SpamPoison.com>Taistele Spammia Vastaan</a>
]
</p>

</td>
    </tr>
  </table>

<!--

<rdf:RDF xmlns="http://web.resource.org/cc/"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
<Work rdf:about="">
   <dc:title>Rautakuu [dot] org</dc:title>
   <dc:description>Rautakuu [dot] org :: Dokumentaatio</dc:description>
   <dc:type rdf:resource="http://purl.org/dc/dcmitype/Text" />
   <license rdf:resource="http://creativecommons.org/licenses/by-sa/1.0/fi/" />
</Work>

<License rdf:about="http://creativecommons.org/licenses/by-sa/1.0/fi/">
   <permits rdf:resource="http://web.resource.org/cc/Reproduction" />
   <permits rdf:resource="http://web.resource.org/cc/Distribution" />
   <requires rdf:resource="http://web.resource.org/cc/Notice" />
   <requires rdf:resource="http://web.resource.org/cc/Attribution" />
   <permits rdf:resource="http://web.resource.org/cc/DerivativeWorks" />
   <requires rdf:resource="http://web.resource.org/cc/ShareAlike" />
</License>

</rdf:RDF>

-->
</body>
</html>

