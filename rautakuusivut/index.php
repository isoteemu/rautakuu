<?php
header("Content-Type: text/html;charset=utf-8");

require_once("config.inc.php");
include_once("DB.php");

$page = substr($_SERVER['PATH_INFO'], 1);
if( empty( $page )) $page = "EtuSivu";

// Yhdistet�n tietokantaan.
$db = DB::Connect($settings['horde']['db']);

// Hae sivu
$pres = &$db->query("SELECT `page_text`, `change_author`, `version_created`  FROM `wicked_pages` WHERE 1 AND `page_name` = '".addslashes($page)."'");

$rtxt = $pres->fetchRow();

$lmtime = gmstrftime( "%a, %d %b %Y %T %Z", $rtxt[2]);

Header("Last-Modified: ".$lmtime);

// Asetetaan suomen locale
setlocale(LC_ALL, "fi_FI.utf8");

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<!-- Last-Modified: <?= $lmtime ?> -->
<html>
<head>
<meta http-equiv="Content-Language" content="fi">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<style type="text/css">
body {margin: 0px; padding: 0px; background-color: white;}
#m {font-family: verdana; font-size: 10px; padding-top: 0px}
#o {background-image: url('http://rautakuu.org/rautakuu/splash.jpg'); font-family: verdana; font-size: 10pt; padding-top: 200px; color: #000000;}
#l {background-image: url('http://rautakuu.org/rautakuu/nimeton_1.jpg'); font-family: verdana; font-size: 10pt; padding-top: 200px; color: #000000;}
#i {font-family: verdana; font-size: 10px; padding-top: 0px; color: #FF3C12; width: 407px;}
a:link#m, a:visited#m, a:active#m {text-decoration: none; color: #B72000;}
a:hover#m {text-decoration: underline; color: #7b7b7b;}
a:link, a:visited, a:active {text-decoration: none; color: #545454;}
a:hover {text-decoration: none; color: #7A7A7A;}
</style>
<base href="http://rautakuu.org/rautakuu/">
<title>Rautakuu [dot] org</title>
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
        <a href="http://rautakuu.org/rautakuu/" style="color: #000000;">Rautakuu [dot] org</a></p>
      </td>
      <td id="l" valign="top" width="407" height="221" align="left">[
	      <a href="https://horde.rautakuu.org" title="Siirry sähköpostiin">Horde</a> |
	      <a href="http://rautakuu.org/hlstats" title="Counter Striken tilastot">HLStats</a> |
	      <a href="javascript:ircWindow()" onRelease="ircWindow();" title="CGI:IRC">#rautakuu</a> ]
      </td>
    </tr>
    <tr>
      <td><img src="nimeton_2.jpg" alt="" border="0" width="312" height="19"></td>
</p>

      </td>
    </tr>
    <tr>
      <td valign="top"><img src="nimeton_3.jpg" width="312" alt="" border="0"  height="245">
	      <p>
		      <p align="center">
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
			</p>
	      </td>
      <td valign="top" id="i"><br>
		      <?php

		      include_once("Text/Wiki.php");

		      $ares = &$db->query("SELECT `page_name` FROM `wicked_pages`");

		      while ( $apage = &$ares->fetchRow() ) {
			      $pages[] = $apage[0];
		      }

		      $db->query("UPDATE `wicked_pages` SET page_hits = page_hits + 1 WHERE page_name = '$page'");

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
		      $xhtml = &$wiki->transform($rtxt[0], 'Xhtml');
		      $xhtml = mb_convert_encoding($xhtml, "utf-8", "iso-8859-1");
		      echo $xhtml;
		      echo "<p>&nbsp;<p>";
		      echo "<p>".strftime( "%c", $rtxt[2] )."/".$rtxt[1]."</p>";
?>

<p>
	[
	<a href="https://horde.rautakuu.org" title="Siirry sähköpostiin">Horde</a> |
	<a href="http://rautakuu.org/hlstats" title="Counter Striken tilastot">HLStats</a> |
	<a href="javascript:ircWindow()" onRelease="ircWindow();" title="CGI:IRC">#rautakuu</a>
	]
<p>

</td>
    </tr>
  </table>
</body>
</html>

