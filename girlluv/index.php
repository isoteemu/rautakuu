<?php
/***************************************************************************
        index.php  -  Girl'luv kuvagalleria scripti
           -------------------
    begin                : Min Dec 13 2004
    copyright            : (C) 2004 by Teemu A
    email                : teemu@rautakuu.org
 ***************************************************************************/

/***************************************************************************
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU General Public License as published by  *
 *   the Free Software Foundation; either version 2 of the License, or     *
 *   (at your option) any later version.                                   *
 *                                                                         *
 ***************************************************************************/

//
// PARAMETRIT
// Näillä säädetään aseutksia
//

// Kansio, jossa kuvat sijaitsevat. (relatiivinen)
$conf['galleria']  = "galleria";

// Kansio, johon thumbnailit luodaa. (relatiivinen)
$conf['thumbs']    = "cache";

// Gallerian otsikko
$conf['header']    = ucfirst(basename(dirname(__FILE__))).":n kuvagalleria";

// Locale asetus
setlocale(LC_ALL, "fi_FI.utf-8");
ini_set("default_charset", "UTF-8");
ini_set("mbstring.encoding_translation", "on");
if( function_exists("putenv")) putenv('LANG="fi_FI.utf-8"');
if( function_exists("mb_internal_encoding")) mb_internal_encoding("UTF-8");

// luotavien Thumbnailien koko (px)
$conf['thumbsize'] = 150;

// Käytetääkö�nopeampaa vai luetettavampaa cachen varmistusta.
// (ero, md5 nimestä vs. md5 sisällöstä)
$conf['accurate']  = true;

//
// Tyyli m�ritteet
//

$style['bgcolor']  = "#333333"; // taustaväri
$style['color']    = "#d0ffd0"; // tekstin väri
$style['thumbimg'] = "border : solid 1px; border-top-color : #B3DCB3; border-right-color :  #F3FFF3; border-bottom-color :  #F3FFF3;    border-left-color : #B3DCB3;"; // Thumbnail kuvan CSS
$style['thumbitm'] = "border : solid 1px; border-top-color : #F3FFF3; border-right-color :  #B3DCB3; border-bottom-color :  #B3DCB3;    border-left-color : #F3FFF3; background:#d0ffd0; color:#333333;"; // Kuvan laatikon CSS

$style['imgtitle'] = "padding:0px; background:#333333; margin:0px; font-size:22px; border-style:solid none none; border-width:1px medium medium; border-top-color:#bce6bc;";

/* Mit�tietoja kerrotaan kuvan vierell� T��onkin vaikeaselkoisempi osa.
 * HTML lause, jossa:
 *  \W  on kuvan leveys
 *  \H  on kuvan korkeus
 *  \N  on kuvan tiedostonimi
 *  \C  on kuvan EXIF kommetti
 *  \Z  on kuvan tiedostokoko
 *  \M  on kuvan viimeksi muokattu päivämäärä
 */

$fileInfo = "
<strong>Nimi: </strong>\N<br />
<strong>Koko: </strong>\Z<br />
<strong>Mitat: </strong>\Wx\H<br />
<strong>Muokattu: </strong>\M<br />
<strong>&nbsp;&nbsp;----</strong><br />
<strong>Kommentti: </strong>\C<br />";

//
// KOODIOSA
// JATKA VAIN JOS OLET GURU TAI TEEMU
// ----------------------------------

/**
 * Thumbnail class. Ripattu jostain
 * Hieman riisuttu versio.
 */
class thumbnail
{
    var $img;
    var $supported = false;

    function thumbnail($imgfile)
    {
        $this->img["name"] = $imgfile;

        // Check cache before anything
        if(( $fname = $this->checkCache()) !== false ) return true;

        //detect image format
        $this->img["format"] = ereg_replace(".*\.(.*)$","\\1",$imgfile);
        $this->img["format"] = strtolower($this->img["format"]);

        switch( $this->img["format"] ) {
            case "jpg" :
            case "jpeg" :
                $this->img["format"] = "jpeg";
                $this->img["src"]    = ImageCreateFromJPEG ($imgfile);
                $this->supported     = true;
                break;

            case "png" :
                $this->img["src"]    = ImageCreateFromPNG ($imgfile);
                $this->supported     = true;
                break;

            case "gif" :
                $this->img["src"]    = ImageCreateFromGIF ($imgfile);
                $this->supported     = true;
                break;

            case "wbmp" :
                $this->img["src"]    = ImageCreateFromWBMP ($imgfile);
                $this->supported     = true;
                break;

            default:
                $this->supported     = false;
                return false;
        }

        $this->img["lebar"]  = imagesx($this->img["src"]);
        $this->img["tinggi"] = imagesy($this->img["src"]);
    }

    function checkCache($path="") {
        global $conf;
        if( $path == "" ) $path = backSlash(dirname(__FILE__)).$conf["thumbs"];
        $path = backSlash( $path );
        if( $conf['accurate'] ) {
            $fname = md5_file($this->img["name"]).".jpeg";
        } else {
            $fname = md5($this->img["name"]).".jpeg";
        }
        $save = $path.$fname;

        if( file_exists( $save )) return $fname;
        return false;
    }

    function resize($size="")
    {
        if( $this->supported == false ) return false;
        global $conf;
        if( $size == "" ) $size = $conf["thumbsize"];

        //size
        if ($this->img["lebar"]>=$this->img["tinggi"]) {
            $this->img["lebar_thumb"]=$size;
            @$this->img["tinggi_thumb"] = ($this->img["lebar_thumb"]/$this->img["lebar"])*$this->img["tinggi"];
        } else {
            $this->img["tinggi_thumb"]=$size;
            @$this->img["lebar_thumb"] = ($this->img["tinggi_thumb"]/$this->img["tinggi"])*$this->img["lebar"];
        }
    }

    function save($path="")
    {
        if(( $fname = $this->checkCache()) !== false ) return $fname;

        global $conf;
        if( $path == "" ) $path = backSlash(dirname(__FILE__)).$conf["thumbs"];
        $path = backSlash( $path );
        if( $conf['accurate'] ) {
            $fname = md5_file($this->img["name"]).".jpeg";
        } else {
            $fname = md5($this->img["name"]).".jpeg";
        }
        $save = $path.$fname;

        if( file_exists( $save )) return $fname;
        if( $this->supported == false ) return false;

        /* change ImageCreateTrueColor to ImageCreate if your GD not supported
         ImageCreateTrueColor function*/
        $this->img["des"] = ImageCreateTrueColor( $this->img["lebar_thumb"],
                                                  $this->img[ "tinggi_thumb"]);
        imagecopyresized($this->img["des"], $this->img["src"], 0, 0, 0, 0,
                         $this->img["lebar_thumb"], $this->img["tinggi_thumb"],
                         $this->img["lebar"], $this->img["tinggi"]);

        // Tallenna alpha kanava, jos phpn GD tukee
        if( function_exists("imagesavealpha")) {
            imagesavealpha($this->img["des"], true);
        }
        if( imageJPEG($this->img["des"], $save, "75") ) {
            return $fname;
        } else {
            return false;
        }
    }
}


//
// Randomi functioita
//

/**
 * Lis� backslashin jos ei ole.
 */
function backSlash( $str )
{
    if( substr( $str, -1 ) != DIRECTORY_SEPARATOR ) {
        $str .= DIRECTORY_SEPARATOR;
    }
    return $str;
}

/**
 * Poistaa backslashin jos taas on
 */
function stripBackSlash( $str )
{
    if( substr( $str, -1 ) == DIRECTORY_SEPARATOR ) {
        return substr( $str, 0, (strlen($str)-1) );
    }
    return $str;
}

/**
 * Pyöristää�tiedoston koon ihmiselle selvemmäksi.
 */
function roundedFileSize( $filename ) {
    $type = Array ('b', 'kb', 'Mb', 'gb');
    $filesize = filesize ($filename);

    for ($i = 0; $filesize > 1024; $i++)
        $filesize /= 1024;

    return round ($filesize, 2)." $type[$i]";
}

/**
 * muotoilee lauseen paremmin HTMLksi sopivaksi
 */
function htmlStr( $str ) {
    $str = htmlspecialchars($str);
    $str = eregi_replace( "([[:alnum:]]+)://([^[:space:]]*)([[:alnum:]#?/&=])",
                            "<a href=\"\\1://\\2\\3\" target=\"_blank\">\\1://\\2\\3</a>", $str);
    $str = eregi_replace( "(([a-z0-9_]|\\-|\\.)+@([^[:space:]]*)([[:alnum:]-]))",
                            "<a href=\"mailto:\\1%s\" >\\1</a>", $str);
    $str = nl2br( $str );
    return $str;
}

/**
 * Yleinen suoriksen aikaa mittaava functio
 */
function timer() {
    static $start;
    list($usec, $sec) = explode(" ", microtime());
    $now = ((float)$usec + (float)$sec);
    if(!isset( $start )) {
        $start = $now;
    }
    $timed = $now - $start;
    return $timed;
}

//
// Tekevä�osa.
//

// Alustetaan timer.
timer();

// Lisätään backslash kansioiden nimiin.
$conf['thumbs']   = backSlash($conf['thumbs']);

$conf['galleria'] = backSlash($conf['galleria']);


// Tarkista, onko cache kansio kirjoitettavissa.
if(! is_writable( $myDir.$conf['thumbs'] )) {
    die("Thumbnail kuvien hakemisto {$conf['thumbs']} ei ole kirjoitettavissa");
}

if(! is_readable( $myDir.$conf['galleria'])) {
    die("Kuvien galleria hakemisto {$conf['galleria']} ei ole luettavissa");
}

// Alustetaan muutamia muuttujia.

$myDir = dirname(__FILE__)."/"; // Kansio, jossa ollaan ja jossa ty�kennell�n.

$numOfImages    = (int)    0;   // Kuvien lukumärä
$lastUpdateUNIX = (int)    0;   // Viimeksi pävitetty UNIX aikaleima
$lastUpdate     = (string) "[Tuntematon]"; // Selkokielinen "Viimeksi pävitetty" aikaleima

$baseHref       = (string) "http://".backSlash($_SERVER['SERVER_NAME']
                           .$_SERVER['REQUEST_URI']);

$kuvat          = array(); // Kuvien säilytykseen
$thumbs         = array(); // Thumbnailien säilytykseen

$jsarray        = "sivut = new Array("; // Kuvien javascript array.
$jsWidths       = "widths = new Array("; // Kuvien leveyden ilmaiseva array.
$jsHeights      = "heights = new Array("; // Kuvien korkeuden ilmaiseva array.

$str            = (string) ""; // Gallerian HTML osa

$tmp            = array();  // Väliaikaisten asioiden säilytykseen.

// Luetaan kuvat galleria hakemisosta
$gallery        = dir( $myDir.$conf['galleria'] );

while (false !== ($entry = $gallery->read())) {

    // Skipattavien lista.
    if( $entry == "." ) continue;
    if( $entry == ".." ) continue;
    if( is_dir($myDir.$conf['galleria'].$entry) ) continue;
    if(! is_readable($myDir.$conf['galleria'].$entry) ) continue;

    $thumbnail =& new thumbnail($myDir.$conf['galleria'].$entry);
    $thumbnail->resize($conf["thumbsize"]);
    if(($tmp['thumb'] = $thumbnail->save()) === false ) {
        continue;
    }

    if ( filemtime($myDir.$conf['galleria'].$entry) > $lastUpdateUNIX )
        $lastUpdateUNIX = filemtime($myDir.$conf['galleria'].$entry);

    $kuvat[$numOfImages]  = $entry;
    $thumbs[$numOfImages] = $tmp['thumb'];

    $numOfImages++;
}
$gallery->close();

// Konvertoidaan unix aika paikalliseen aikaan.
$lastUpdate = strftime( "%c", $lastUpdateUNIX );

foreach ( $kuvat as $key => $kuva ) {
    $imgParams = getimagesize($myDir.$conf['galleria'].$kuva);
    $w = $imgParams[0];
    $h = $imgParams[1];

    $img = urlencode(stripBackSlash($conf['galleria']))."/".urlencode($kuva);

    // JavaScript arrayden hahmotus
    if( $key > 0 ) {
        $jsarray    .= ", ";
        $jsWidths   .= ", ";
        $jsHeights  .= ", ";
    }
    $jsarray    .= '"'.urlencode($kuva).'"';
    $jsWidths   .= $w;
    $jsHeights  .= $h;


    $str .= '<a name="'.$kuva.'"></a>';
    $str .= '<table border="0" style="'.$style['thumbitm'].'" width="100%"><tr><td width="'.($conf["thumbsize"]+10).'" align="center">';

    $str .= '<A HREF="javascript:popupPage(\''.$key
            .'\',\''.($h+60).'\',\''.($w+15).'\');"><img '
            .'src="'.$conf["thumbs"].$thumbs[$key].'" '
            .'style="'.$style['thumbimg'].'" border="0" title="'
            .'Näytä '.$kuva.'"></a><br />';

    if( function_exists("exif_read_data")) {
        $exif = @exif_read_data($myDir.$conf['galleria'].$kuva);
        $comment = "";
        if( isset($exif['COMMENT'])) {
            foreach( $exif['COMMENT'] as $tmp['cmt'] ) {
                if( $comment != "" ) $comment .= "<br />";
                $comment .= htmlStr($tmp['cmt']);
            }
        }
    } else {
        $comment = "[Kommentit eivät tuettuja]";
    }
    // Tyls�osa. Nyt korvataan avaimet oikeilla arvoilla.
    $tmp['i'] = str_replace("\W", $w, $fileInfo);
    $tmp['i'] = str_replace("\H", $h, $tmp['i']);
    $tmp['i'] = str_replace("\N", htmlentities($kuva), $tmp['i']);
    $tmp['i'] = str_replace("\C", $comment, $tmp['i']);

    $tmp['i'] = str_replace("\Z", roundedFileSize( $myDir.$conf['galleria'].$kuva), $tmp['i']);
    $tmp['i'] = str_replace("\M", strftime("%x", filemtime($myDir.$conf['galleria'].$kuva)), $tmp['i']);

    $str .= '</td><td>'.$tmp['i'].'</td><td>&nbsp;</td></tr></table>';
}

$jsarray    .=" );";
$jsWidths   .=" );";
$jsHeights  .=" );";

Header("Content-Type: text/html; charset=utf-8");
?>
<html>
  <head>
    <title><?= $conf['header'] ?></title>
    <base href="<?=$baseHref;?>" />
    <meta name="generator" content="Girl'luv" />
    <meta name="author" content="Rautakuu [dot] org :: http://rautakuu.org" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script language="JavaScript">
    <!-- Begin
    // Hack.this.fucking.javaScript.
    var <?= $jsarray; ?>

    var galleria = "<?= $conf['galleria']; ?>";

    function popupPage(key, height, width) {
        windowprops="toolbar=no,width="+width+",height="+height+","
                    +"directories=no,status=no,scrollbars=no,"
                    +"resize=yes,menubar=no";

        sivu=window.open("", "Popup", windowprops);
        sivu.resizeTo(width,height);

        sivu.document.write("<html><head><title>Gallerian kuva "+sivut[key]+"</title>");
        sivu.document.write("<base href=\"<?=$baseHref;?>\"/>");

        sivu.document.write("<script language=\"JavaScript\">");

        sivu.document.write("var galleria = \"<?= $conf['galleria']; ?>\";");
        sivu.document.write("var key = "+key+";");
        sivu.document.write("var <?=addslashes($jsarray);?>");
        sivu.document.write("var <?=addslashes($jsWidths);?>");
        sivu.document.write("var <?=addslashes($jsHeights);?>");

        sivu.document.write("function next() {");
        sivu.document.write("   if(key == (sivut.length-1)) {");
        sivu.document.write("       key = 0;");
        sivu.document.write("   } else {");
        sivu.document.write("       key = key+1;");
        sivu.document.write("   }");
        sivu.document.write("   changeImg();");
        sivu.document.write("}");

        sivu.document.write("function prev() {");
        sivu.document.write("   if(key == 0) {");
        sivu.document.write("       key = sivut.length-1;");
        sivu.document.write("   } else {");
        sivu.document.write("       key = key-1;");
        sivu.document.write("   }");
        sivu.document.write("   changeImg();");
        sivu.document.write("}");

        sivu.document.write("function changeImg() {");
        sivu.document.write("   document[\"image\"].src=galleria+\"/\"+sivut[key];");
        sivu.document.write("   document[\"image\"].width=widths[key];");
        sivu.document.write("   document[\"image\"].height=heights[key];");
        sivu.document.write("   var toWidth=widths[key]+15;");
        sivu.document.write("   var toHeight=heights[key]+60;");
        sivu.document.write("   self.resizeTo(toWidth, toHeight);");
        sivu.document.write("   updateShowTitle();");
        sivu.document.write("}");

        sivu.document.write("function printRand() {");
        sivu.document.write("   for(var i = 0; i < sivut.length; i++) {");
        sivu.document.write("       document.write('<span id=\"'+sivut[i]+'\" style=\"display:none;\">'+unescape(sivut[i])+'</span>');");
        sivu.document.write("   }");
        sivu.document.write("   updateShowTitle();");
        sivu.document.write("}");

        sivu.document.write("function updateShowTitle() {");
        sivu.document.write("   var toHide;");
        sivu.document.write("   for(var i = 0; i < sivut.length; i++) {");
        sivu.document.write("       toHide = document.getElementById(sivut[i]);");
        sivu.document.write("       toHide.style.display='none';");
        sivu.document.write("   }");
        sivu.document.write("   var toShow = document.getElementById(sivut[key]);");
        sivu.document.write("   toShow.style.display=''");
        sivu.document.write("}");


        sivu.document.write("</scri"+"pt>");
        sivu.document.write("</head><body style=\"padding:0px; margin:0px;\" bgcolor=\"<?=$style['bgcolor'];?>\"  text=\"<?=$style['color'];?>\">");
        sivu.document.write("<center>");
        sivu.document.write("<a href=\"javascript:self.close();\"><img name=\"image\""
                             +"style=\"padding:0px; margin:0px; align:center;\" src=\""+galleria+"/"+sivut[key]
                             +"\" border=\"0\" alt=\""+sivut[key]+"\" align=\"center\" /></a>");
        sivu.document.write("<div style=\"<?= $style['imgtitle']; ?>\">");
        sivu.document.write("<a href=\"javascript:prev();\" style=\"color:<?=$style['color'];?>;\">&nbsp;&#171;&nbsp;</a>");
        sivu.document.write("<script language=\"JavaScript\">");
        sivu.document.write("printRand();");
        sivu.document.write("</script>");
        sivu.document.write("<a href=\"javascript:next();\" style=\"color:<?=$style['color'];?>;\">&nbsp;&#187;&nbsp;</a>");
        sivu.document.write("</div>");
        sivu.document.write("</center>");
        sivu.document.write("</body></html>");
        sivu.focus();
        sivu.document.close();
    }
    //  End -->
    </script>
  </head>
  <body bgcolor="<?=$style['bgcolor'];?>" text="<?=$style['color'];?>">
    <table width="90%" align="center">
      <thead>
        <tr>
          <th align="left"><h2><?= $conf['header']; ?></h2></th>
        </tr>
        <tr>
          <th align="left">
            <p>
              <em>Kuvia galleriassa:</em> <?= $numOfImages; ?><br />
              <em>Viimeisin pävitys:</em> <?= $lastUpdate ?>
            </p>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr><td><pre>
        <?= $str ?>
        </pre></td></tr>
      </tbody>
    </table>
    <p align="center">Suoritettu ajassa: <?= round(timer(), 4); ?>s.</p>
  </body>
</html>
