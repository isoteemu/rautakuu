<?php
/* kate: space-indent true; encoding utf-8; indent-width 2; */
$links = array(
  'Rautakuu [dot] org' => 'http://rautakuu.org/drupal',
  'Web IRC' =>  'http://irc.rautakuu.org',
  'Koodit'  =>  'http://svn.rautakuu.org/',
  'IRC statistiikka' => 'http://janoks.sivut.rautakuu.org/pisg/',
  'Horde' => 'https://horde.rautakuu.org',
  'VIP' => 'https://vip.rautakuu.org/',
  'A-pu-va!' => array(
    'url' => 'http://rautakuu.org/drupal/book',
    'class' => 'help'
  )
);

$blinks = array(
  'Mainosta Rautakuussa' => array(
    'url' => 'https://adwords.google.com/select/OnsiteSignupLandingPage?client=ca-pub-3452268181804196&amp;referringUrl=http://rautakuu.org/&amp;hl=fi&amp;gl=FI',
    'class' => 'external'
  ),
  't3h s0urc3' => 'http://svn.rautakuu.org/trac/homebrevcomputing/browser/rautakuusivut/splash',
  't3h s0urc3' => 'http://svn.rautakuu.org/trac/homebrevcomputing/browser/rautakuusivut/splash',
  'Server Info' => 'http://rautakuu.org/phpsysinfo/',
  'Horde Dynamic Mail' => array(
    'url' => 'https://horde.rautakuu.org/dimp/?frameset_loaded=1',
    'class' => 'beta'
   ),
   /*
  'pr0npr0x' =>  array(
    'url' => 'https://1984.rautakuu.org/',
    'class' => 'beta',
  ),
  */
  'Olen Rikollinen?' => array(
    'url' => 'http://www.olenrikollinen.org/',
    'class' => 'external',
  ),
  'RoundCube email' => array(
    'url' => 'http://rautakuu.org/roundcubemail/',
    'class' => 'beta',
  ),
);

/**
 * Köyhän miehen fortune
 */
function lentavahollantilainen() {
  static $str;
  if(isset($str)) return $str;

  $lauseet = array(
    'Halutaan paklata okrein kivottaja',
    'Vote for change, vote for judgement day',
    'Rautakuu [dot] org',
    'ÄLÄ HÄTÄILE',
    'To Serve and to Protect',
    'Säätäminen on tuuninkia',
  );

  $str = $lauseet[array_rand($lauseet)];
  return $str;
}

function aboutLink(&$links) {
  if(is_array(current($links))) {
    $k =& key($links);

    $x = "";
    $url = $links[$k]['url'];
    if($links[$k]['title']) $tit = $links[$k]['title'];
    else $tit = $k;
    if($links[$k]['class']) $xtr .= ' class="'.$links[$k]['class'].'"';
  } else {
    $url = current($links);
    $tit = key($links);
    $xtr = "";
  }
  next($links);
  return sprintf('<a href="%s"%s>%s</a>', $url, $xtr, $tit);
}

header("Content-Type: text/html; charset=UTF-8");

// Haetaan kÃ¤yttÃ¤jÃ¤t joilla on nettisivutilaa tietokannasta
include_once("conf.inc.php");

if($cyrus_dns) {
  include_once("DB.php");
  $cyrus = DB::Connect($cyrus_dns);
  if(DB::IsError($cyrus)) {
    break;
  }
  $res = $cyrus->query("SELECT `username`, `home` FROM `pureftpd` WHERE `enabled` = 'y' AND `public` = '1' AND `home` REGEXP '/var/www/sites/([^/]+)$'");
  while($row =& $res->fetchRow()) {
    $blinks[] = array(
      'title' => "Käyttäjä: ".$row[0],
      'url'   => "http://".rawurlencode(basename($row[1])).".sivut.rautakuu.org/",
      'class' => "external",
    );
  }
  $cyrus->disconnect();
}


while(count($blinks) > 0) {
  $i = array_rand($blinks);
  if(rand(0,count($links)) == 0) {
    $links[$i] = $blinks[$i];
  }
  unset($blinks[$i]);
  ksort($blinks);
}

// jos ei ole teemaa valittu, kokeillan hokasta joku.
if(empty($_COOKIE['style'])) {
  include_once("conf.inc.php");
  include_once("DB.php");

  if(!$theme) {
    // Ensin katsotaan CS teema
    $hlds = DB::Connect($hlds_dns);
    if(DB::IsError($hlds)) {
      break;
    }
    $res = $hlds->query("SELECT COUNT(*) FROM `hlstats_Link_Trace` WHERE `time` >  DATE_SUB(NOW(), INTERVAL 7 DAY) AND `whom` = '{$_SERVER['REMOTE_ADDR']}'");
    if($res->fetchRow() > 0) {
      $theme = "Counter-Strike";
    }
    $res->free();
    $hlds->disconnect();
  }
  if(!$theme) {
    $drupal = DB::Connect($drupal_dns);
    if(DB::IsError($drupal)) {
      break;
    }
    $res = $hlds->query("SELECT COUNT(*) FROM `watchdog` WHERE `timestamp` >  UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 7 DAY)) AND `hostname` = '{$_SERVER['REMOTE_ADDR']}'");
    if($res->fetchRow() > 0) {
      $theme = "NausicaÃ¤";
    }
    $res->free();
    $drupal->disconnect();
  }
  if($theme) {
    $thmstr = 'setActiveStyleSheetEx("'.$theme.'");';
  }
}


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title>Rautakuu [dot] org ||  Vote for change, vote for judgement day</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" href="css/page.css" type="text/css" media="all" />
    <link rel="alternate stylesheet" href="css/nausicaa.css" type="text/css" title="Nausicaä" />
    <link rel="alternate stylesheet" href="css/cs.css" type="text/css" title="Counter-Strike" />
    <link rel="alternate stylesheet" href="css/et.css" type="text/css" title="Enemy Territory" />
    <script type="text/javascript" src="http://www.beyondunreal.com/images/styleswitcher.js"><?= $thmstr; ?></script>
    <script type="text/javascript">
    k=document;v=Date;x=false;z=Array;af=Math.floor;ag=RegExp;b=new z(8);s=new z("null","rautakuu","horde","cs","tao","mantis","binocular");aa=new z(11);ab=10;t=0;u=0;n=0;o=new v();h=5;m=385;c=0;w=x;var title;var firstHoverOccurred=x;m=385;p=0;function d(ac){c=ac;o=new v();setTimeout("gidle()",20);}function e(ac){c=0;w=x;o=new v();setTimeout("gidle()",20);}function ae(){for(var j=1;j<b.length;j++){b[j]=35}title=k.getElementById('imageTitle');for(i=0;i<b.length;i++){aa[i]=new Image();aa[i].src="img/"+s[i+1]+".gif"}setTimeout("gidle()",20);}function gidle(){var l=0;for(var i=1;i<b.length;i++){var imagename="image"+i;var imageElem=k.getElementById(imagename);if(c!=i){if(b[i]>35){b[i]-=h;if(b[i]<=35){b[i]=35;imageElem.src="img/"+s[i]+".gif"}imageElem.width=b[i];imageElem.height=b[i];if(c==0){var g=af(255-255*(b[i]-35)/35);title.style.color="rgb("+g+","+g+","+g+")"}p=1}l+=b[i]}}if(c!=0&&b[c]<70){imagename="image"+c;imageElem=k.getElementById(imagename);if(w==x){w=true;if(c<4){var y=240-c*70;title.innerHTML=k.getElementById(imagename).alt+'<img src="img/spacer.gif" width="'+y+'" height="1">'}else{var y=(c-4)*70+35;title.innerHTML='<img src="img/spacer.gif" width="'+y+'" height="1">'+k.getElementById(imagename).alt}}b[c]+=h;p=1;if(b[c]>70){b[c]=70}l+=b[c];if(l<m){b[c]+=m-l;if(b[c]>70){b[c]=70}l=m}var g=af(255-255*(b[c]-35)/35);title.style.color="rgb("+g+","+g+","+g+")";imageElem.width=b[c];imageElem.height=b[c];k.getElementById(imagename).src="img/"+s[c]+".gif"}m=l;var ad=new v();ab=ad.getTime()-o.getTime();o=ad;t+=ab;u++;n=t/u;h=5;if(u>4){if(n>30){h=10}if(n>60){h=15}if(n>90){h=20}}if(p){setTimeout("gidle()",20);p=0}}

    var lentavahollantilainen = '<?= lentavahollantilainen();?>';

    function clearVal(fuckmegently) {
      if(fuckmegently.value == lentavahollantilainen) {
        fuckmegently.value="";
        fuckmegently.style.color="#000000";
      }
    }

    </script>
  </head>
  <body onload="ae()">
  <div id="body">
  <div id="header">
    <div id="title"><a href="http://rautakuu.org"><strong>Rautakuu [dot] org</strong> -- Vote for change, vote for judgement day</a></div>
    <div id="ThemeSettings">
      <span id="ThemeMenu">
        <ul class="menu">
          <li class="submenu">Valitse teema
            <ul>
              <li><a href="JavaScript:setActiveStyleSheetEx('Plain');"  onclick="setActiveStyleSheetEx('Plain');" id="DefThemeBtn" class="themeBtn">Oletus</a></li>
              <li><a href="JavaScript:setActiveStyleSheetEx('Nausicaä');" onclick="setActiveStyleSheetEx('Nausicaä');" id="NausThemeBtn" class="themeBtn">Nausicaä</a></li>
              <li><a href="JavaScript:setActiveStyleSheetEx('Counter-Strike');" onclick="setActiveStyleSheetEx('Counter-Strike');" id="NausThemeBtn" class="themeBtn">Counter-Strike</a></li>
              <li><a href="JavaScript:setActiveStyleSheetEx('Enemy Territory');" onclick="setActiveStyleSheetEx('Enemy Territory');" id="NausThemeBtn" class="themeBtn">Enemy Territory</a></li>
            </ul>
          </li>
        </ul>
      </span>
      &nbsp;[T]
    </div>
  </div>
  <div id="container">
    <table border="0" cellpadding="0" cellspacing="0" align="center" id="main">
      <thead>
        <tr>
          <td align="center"><a href="http://rautakuu.org/drupal"><img id="logo" src="img/spacer.gif" alt="Rautakuu [dot] org" width="276" height="219" align="baseline" border="0" align="center" /></a></td>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td valign="bottom" height="150">
            <form action="http://rautakuu.org/drupal/search/opensearch" method="post" name="f">
            <table border="0" width="520" cellpadding="0" cellspacing="0" height="25">
              <tr>
                <td align="center" valign="bottom">
                  <div id="imageTitle" style="color: rgb(255, 255, 255);">&nbsp;<img src="spacer.gif" height="1" width="170"></div>
                </td>
              </tr>
            </table>
            <table border="0" cellpadding="0" cellspacing="0" width="100%" id="navbuttons">
              <tr height="72">
                <td align="center" valign="bottom">
                  <a id="1a" class="q" href="http://rautakuu.org/drupal" onclick="return qs(this)"><img id="image1" src="img/rautakuu.gif" alt="Rautakuu [dot] org" onmouseover="d('1')" onmouseout="e('1')" border="0" height="35" width="35"></a><!-- SEP --><a id="2a" class="q" href="https://horde.rautakuu.org" onclick="return qs(this)"><img id="image2" src="img/horde.gif" alt="Horde email" onmouseover="d('2')" onmouseout="e('2')" border="0" height="35" width="35"></a><!-- SEP --><img id="image3" src="img/cs.gif" alt="Deprecated" onmouseover="d('3')" onmouseout="e('3')" border="0" height="35" width="35"><!-- SEP --><a id="4a" class="q" href="https://tao.rautakuu.org" onclick="return qs(this)"><img id="image4" src="img/tao.gif" alt="Tao - é" onmouseover="d('4')" onmouseout="e('4')" border="0" height="35" width="35"></a><!-- SEP --><a id="5a" class="q" href="http://svn.rautakuu.org" onclick="return qs(this)"><img id="image5" src="img/mantis.gif" alt="Projektit" onmouseover="d('5')" onmouseout="e('5')" border="0" height="35" width="35"><!-- SEP --><a id="6a" class="q" href="javascript:document.f.submit();" onclick="return qs(this)"><img id="image6" src="img/binocular.gif" alt="Etsi" onmouseover="d('6')" onmouseout="e('6')" border="0" height="35" width="35"></a>
               </td>
              </tr>
            </table>
            <table border="0" cellpadding="0" cellspacing="0" width="100%" id="searchfield" class="searchbox">
              <tbody>
                <tr>
                  <td align="center">
                    <input onblur="clearVal(this);" type="text" name="edit[keys]" value="<?= lentavahollantilainen();?>" size="30" maxlength="256" id="search" onclick="clearVal(this);" onfocus="clearVal(this);"/>
                  </td>
                </tr>
              </tbody>
            </table>
            </form>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  <div id="footer">
    <table align="center" id="about">
      <tr>
        <td id="about-left" class="column" valign="top">
<?php
$num =  ceil(count($links)/3);
$i = 0;
while($i < $num) {
  echo aboutLink($links);
  $i++;
}
?>
        </td>
        <td id="about-center" class="column" valign="top">
<?php
$num =  round(count($links)/3)+$num;
while($i < $num) {
  echo aboutLink($links);
  $i++;
}
?>
        </td>
        <td id="about-right" class="column" valign="top">
<?php
$num =  floor(count($links)/3)+$num;
while($i < $num) {
  echo aboutLink($links);
  $i++;
}
?>
        </td>
      </tr>
    </table>
    <div id="copyright">
      <!--Creative Commons License-->
      <a rel="license" href="http://creativecommons.org/licenses/by-sa/1.0/fi/">&copy; WTF?</a><!--/Creative Commons License--><!-- <rdf:RDF xmlns="http://web.resource.org/cc/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
          <Work rdf:about="">
              <license rdf:resource="http://creativecommons.org/licenses/by-sa/1.0/fi/" />
      <dc:title>Rautakuu [dot] org</dc:title>
      <dc:type rdf:resource="http://purl.org/dc/dcmitype/Text" />
      <dc:source rdf:resource="http://rautakuu.org" />
          </Work>
          <License rdf:about="http://creativecommons.org/licenses/by-sa/1.0/fi/"><permits rdf:resource="http://web.resource.org/cc/Reproduction"/><permits rdf:resource="http://web.resource.org/cc/Distribution"/><requires rdf:resource="http://web.resource.org/cc/Notice"/><requires rdf:resource="http://web.resource.org/cc/Attribution"/><permits rdf:resource="http://web.resource.org/cc/DerivativeWorks"/><requires rdf:resource="http://web.resource.org/cc/ShareAlike"/></License></rdf:RDF> -->
      </div>
    </div>
    </div>
  <div style="display:none;">
<!-- <a href="http://rautakuu.org/gustydefensive.php">contact</a> -->
<!-- <a href="mailto:spamtrap@rautakuu.org">spamtrap@rautakuu.org</a> -->
  </div>
  </body>
</html>
