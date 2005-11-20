<?php
if(!function_exists("lctime")) {
    function lctime($time) {
        return htmlentities(strftime("%c", strtotime($time)));
    }
}

if(!function_exists("irctime")) {
    function irctime($time) {
        return strftime("<tt>[%H:%M:%S]</tt>", strtotime($time));
    }
}

if(!function_exists("ircnick")) {
    function ircnick($nick) {
        static $colors;
        if(!isset($colors)) $colors = array();
        if(!isset($colors[$nick])) {
            $colors[$nick] = sprintf("%02X%02X%02X", (mt_rand(4, 50)*10), (mt_rand(4, 50)*10), (mt_rand(4, 50)*10));
        }
        return "<tt><font color=\"#".$colors[$nick]."\">".addslashes($nick)."</font></tt>";
    }
}

if(!function_exists("htmlline")) {
    function htmlline($str) {
        $str = mb_convert_encoding($str, "utf-8", "iso-8859-1");
        $str = htmlspecialchars($str);
        $str = eregi_replace( "([[:alnum:]]+)://([^[:space:]]*)([[:alnum:]#?/&=])", "<a href=\"\\1://\\2\\3\" target=\"_blank\">\\1://\\2\\3</a>", $str);
        $str = eregi_replace( "(([a-z0-9_]|\\-|\\.)+@([^[:space:]]*)([[:alnum:]-]))", "<a href=\"mailto:\\1%s\" >\\1</a>", $str);
        return $str;
    }
}

if($_SESSION['captcha_ok'] != true) {
    $_SESSION['captcha_return'] = $_SERVER['REQUEST_URI'];
    Header("Location: HumanityCheck");
    $content .= '<a href="HumanityCheck">Siirry ihmisyytesi tarkistukseen</a>';
} else {
    $ircDB =& DB::Connect($settings['irc']['db']);
    if(DB::IsError($ircDB)) {
        $content .= "Virhe yhdistettäessä tietokantaan, ".$ircDB->getMessage();
    } else {
        if(isset($_GET['all'])) {
            $limit = "";
        } else {
            $rowsres = $ircDB->query("SELECT COUNT(`msg`) FROM ircmsg");
            list($maxrows) = $rowsres->fetchRow();
            $rowsres->free();
            $limit = "LIMIT ".($maxrows-250).",".$maxrows;
            $content .= "<a href=\"".$_SERVER['REQUEST_URI']."?all\">Näytä koko historia</a>\n";
        }
        $sql =& $ircDB->query("
            SELECT
                `time`, `nick`, `msg`
            FROM
                ircmsg
            ORDER BY
                `time` ASC
            {$limit}");
        $content .= "<table border=\"0\">";
        $lmtime = 0;
        while(list($time, $nick, $msg) = $sql->fetchRow()) {
            $content .= "<tr>";
            $content .= "<td title=\"".lctime($time)."\"><a name=\"".$time."\"></a>".irctime($time)."</td>";
            $content .= "<td align=\"right\">&lt;".ircnick($nick)."&gt;</td>";
            $content .= "<td>".htmlline($msg)."</td>";
            $content .= "</tr>";
            if($lmtime<$time) $lmtime = $time;
        }
        $content .= "</table>";
        if($limit != "") $content .= "<a href=\"".$_SERVER['REQUEST_URI']."?all\">Näytä koko historia</a>\n";
        $lmtime = gmstrftime( "%a, %d %b %Y %T %Z", strtotime($time));
    }
}
