<?php
header("content-type: text/html; charset=UTF-8");
include_once("DB.php");

$db =& DB::Connect("mysql://miniteemu:a24cdcf4903642@localhost/rautakuuirc");

$lainaus = "Rautakuu [dot] org -- Meillä jopa kusi on paskaa";

function rgb2html($tablo) {
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
                //->Retour Ã  la ligne, fermer toutes les balises ouvertes
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
                $buffer.= htmlspecialchars($chr);
                break;
        }
    }
    return $buffer;
}


if(!DB::IsError($db)) {
    $res = $db->query("SELECT `nick`, `msg` FROM `ircmsg` WHERE `action` LIKE 'PRIVMSG' AND `time` >= DATE_SUB(CURDATE(),  INTERVAL 18 DAY) ORDER BY RAND() LIMIT 0, 1");

    if($res->numRows() >= 1) {

        $row = array();
        $res->fetchInto($row, DB_FETCHMODE_ASSOC);

        $lainaus = mb_convert_encoding($row['msg'], "UTF-8");

        $lainaus = irc2html($lainaus);

        $lainaus = eregi_replace( "([[:alnum:]]+)://([^[:space:]]*)([[:alnum:]#?/&=])", "<a href=\"\\1://\\2\\3\" target=\"_blank\">\\1://\\2\\3</a>", $lainaus);
        $lainaus = eregi_replace( "(([a-z0-9_]|\\-|\\.)+@([^[:space:]]*)([[:alnum:]-]))", "<a href=\"mailto:\\1%s\" >\\1</a>", $lainaus);

        $lainaus = "&#060;{$row['nick']}&#062; $lainaus";
    }
}


?>
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <title>Rautakuu [dot] org</title>
    <style>

body,td,a,p,.h {
    font-family:arial,sans-serif;
  }


.h {
    font-size:20px;
  }


.q {
    color:#00c;
  }

a {
    text-decoration:none;
}
a:hover {
    text-decoration:underline;
}

#search {
    background-color : #ffffff;
    border-bottom-color : #000000;
    border-bottom-style : solid;
    border-bottom-width : 1px;
    border-left-color : #000000;
    border-left-style : solid;
    border-left-width : 1px;
    border-right-color : #000000;
    border-right-style : solid;
    border-right-width : 1px;
    border-top-color : #000000;
    border-top-style : solid;
    border-top-width : 1px;
    width : 240px;
  }

</style>
    <script type="text/javascript">
    k=document;v=Date;x=false;z=Array;af=Math.floor;ag=RegExp;b=new z(8);s=new z("null","rautakuu","horde","cs","tao","mantis","globe-beta","binocular");aa=new z(11);ab=10;t=0;u=0;n=0;o=new v();h=5;m=385;c=0;w=x;var title;var firstHoverOccurred=x;m=385;p=0;function d(ac){c=ac;o=new v();setTimeout("gidle()",20);}function e(ac){c=0;w=x;o=new v();setTimeout("gidle()",20);}function ae(){for(var j=1;j<b.length;j++){b[j]=35}title=k.getElementById('imageTitle');for(i=0;i<b.length;i++){aa[i]=new Image();aa[i].src=s[i+1]+".gif"}setTimeout("gidle()",20);}function gidle(){var l=0;for(var i=1;i<b.length;i++){var imagename="image"+i;var imageElem=k.getElementById(imagename);if(c!=i){if(b[i]>35){b[i]-=h;if(b[i]<=35){b[i]=35;imageElem.src=s[i]+".gif"}imageElem.width=b[i];imageElem.height=b[i];if(c==0){var g=af(255-255*(b[i]-35)/35);title.style.color="rgb("+g+","+g+","+g+")"}p=1}l+=b[i]}}if(c!=0&&b[c]<70){imagename="image"+c;imageElem=k.getElementById(imagename);if(w==x){w=true;if(c<4){var y=240-c*70;title.innerHTML=k.getElementById(imagename).alt+'<img src="spacer.gif" width="'+y+'" height="1">'}else{var y=(c-4)*70+35;title.innerHTML='<img src="spacer.gif" width="'+y+'" height="1">'+k.getElementById(imagename).alt}}b[c]+=h;p=1;if(b[c]>70){b[c]=70}l+=b[c];if(l<m){b[c]+=m-l;if(b[c]>70){b[c]=70}l=m}var g=af(255-255*(b[c]-35)/35);title.style.color="rgb("+g+","+g+","+g+")";imageElem.width=b[c];imageElem.height=b[c];k.getElementById(imagename).src=s[c]+".gif"}m=l;var ad=new v();ab=ad.getTime()-o.getTime();o=ad;t+=ab;u++;n=t/u;h=5;if(u>4){if(n>30){h=10}if(n>60){h=15}if(n>90){h=20}}if(p){setTimeout("gidle()",20);p=0}}
    </script>
  </head>
  <body onload="ae()" alink="red" bgcolor="white" link="#0000cc" text="black" vlink="#551a8b">
    <center>
      <form action="http://rautakuu.org/drupal/search/node" method="post" name="f">
        <img src="pc.gif" alt="Rautakuu [dot] org" id="logo" height="219" width="276">
        <table border="0" width="100%">
          <tbody>
            <tr>
              <td align="center" valign="bottom">
                <b><div id="imageTitle" style="color: rgb(255, 255, 255);"><b><img src="spacer.gif" height="1" width="280">Rautakuu [dot] org</b></div></b>
              </td>
            </tr>
          </tbody>
        </table>
        <table border="0" cellpadding="0" cellspacing="0" width="100%">
          <tbody>
            <tr height="72">
              <td align="center" valign="bottom">
                <a id="1a" class="q" href="http://rautakuu.org/drupal" onclick="return qs(this)"><img id="image1" src="rautakuu.gif" alt="Rautakuu [dot] org" onmouseover="d('1')" onmouseout="e('1')" border="0" height="35" width="35"></a><!-- SEP --><a id="2a" class="q" href="https://horde.rautakuu.org" onclick="return qs(this)"><img id="image2" src="horde.gif" alt="Horde email" onmouseover="d('2')" onmouseout="e('2')" border="0" height="35" width="35"></a><!-- SEP --><a id="3a" class="q" href="http://rautakuu.org/hlstats" onclick="return qs(this)"><img id="image3" src="cs.gif" alt="Hlstλts - Pelaajien ranking" onmouseover="d('3')" onmouseout="e('3')" border="0" height="35" width="35"></a><!-- SEP --><a id="4a" class="q" href="https://tao.rautakuu.org" onclick="return qs(this)"><img id="image4" src="tao.gif" alt="Tao - 道" onmouseover="d('4')" onmouseout="e('4')" border="0" height="35" width="35"></a><!-- SEP --><a id="5a" class="q" href="http://bugs.rautakuu.org" onclick="return qs(this)"><img id="image5" src="mantis.gif" alt="Mantis Bug Tracker" onmouseover="d('5')" onmouseout="e('5')" border="0" height="35" width="35"><?php if(rand(0,3) == 1 || isset($_COOKIE['wikiUserName'])) { ?><!-- SEP --><a id="6a" class="q" href="http://wiki.rautakuu.org" onclick="return qs(this)"><img id="image6" src="globe.gif" alt="BETA [[ wiki ]] BETA" onmouseover="d('6')" onmouseout="e('6')" border="0" height="35" width="35"></a><!-- SEP --><?php } ?><a id="6a" class="q" href="javascript:document.f.submit();" onclick="return qs(this)"><img id="image7" src="binocular.gif" alt="Etsi" onmouseover="d('7')" onmouseout="e('7')" border="0" height="35" width="35"></a>
              </td>
            </tr>
          </tbody>
        </table>
        <table cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td align="center">
                <input id="search" maxlength="256" size="30" name="edit[keys]" value="">
              </td>
            </tr>
          </tbody>
        </table>
      </form>
      <br>
      <br>
      <font size="-1"><i>"<a href="http://rautakuu.org/drupal/node/8" style="color:#000000;"><?= $lainaus ?></a>"</i></font>
    </center>
    <script language="text/javascript" src="/awstats_misc_tracker.js"></script>
  </body>
</html>
