<?php
/**
 * myStore.php - Tallettaa viestit tietokantaan
 */

static $db;

if( $init==true ) {
    include_once("DB.php");
    $db = DB::Connect("mysql://miniteemu:a24cdcf4903642@localhost/rautakuuirc");
    if(DB::IsError($db)) {
        $plugin->message("Virhe yhdistettäessä tietokantaan, ".$db->getMessage());
        unset($plugin->irc->irc_data->triggers->plugins['myStore']);
        return false;
    }

    return;
}

if(!isset($db)) {
    irc::trace("Yhdistetään tietokantaan");
    $db = DB::Connect("mysql://miniteemu:a24cdcf4903642@localhost/rautakuuirc");
    if(DB::IsError($db)) {
        $plugin->message("Virhe yhdistettÃ¤essÃ¤ tietokantaan, ".$db->getMessage());
        unset($plugin->irc->irc_data->triggers->plugins['myStore']);
        return false;
    }
}

$wanhat = array(
    "Wanha, Sanottu jo %d kertaa",
    "Keksi jotain uutta. urli pastettu jo %d kertaa",
    "Hohhoi, pastettu täällä jo %d:n ootteeseen"
);

$firsjoinmsg = "{$irc->channel} kanavan säännöt: http://rautakuu.org/drupal/RautakuuIrc";

$msg = $plugin->line->msg;

switch( $plugin->line->code ) {
    case "PRIVMSG" :
        // Don't store my own private messages.
        if(empty($plugin->line->channel)) {
            irc::trace("Privaati viesti? {$plugin->irc->channel} - {$plugin->line->channel}");
            return;
        }
        /*
        $match = array();
        if(preg_match('%(ftp|http|https)://([^[\s]*)%si',$msg,$match)) {
            if(!strstr($match[2],"rautakuu.org")) {
                $res = $db->query("SELECT COUNT(*) FROM `ircmsg` WHERE `msg` LIKE '%".addslashes($match[2])."%' AND `channel` = '{$plugin->line->channel}'");
                if(DB::IsError($res)) {
                    irc::trace("Ei voitu hakea jo sanottuja urleja: ".$res->getMessage());
                } elseif($res->numRows() > 0) {
                    list($oldies) = $res->fetchRow();
                    if($oldies >= 1)
                        $plugin->message(sprintf("{$plugin->line->nick}: ".$wanhat[array_rand($wanhat)],$oldies));
                    $res->free();
                }
            }
        }
        */
        break;
    case "JOIN" :
        /*
        $res = $db->query("SELECT COUNT( * ) FROM `ircmsg` WHERE `action` = 'JOIN' AND `nick` LIKE '{$plugin->line->nick}'");
        list($joincount) = $res->fetchRow();
        if($joincount < 1) {
            $plugin->message($firsjoinmsg, $plugin->line->nick);
        }
        $res->free();
        */
        $msg = "";
        break;
    case "NICK" :
    case "PART" :
    case "QUIT" :
    case "KICK" :
    case "TOPIC" :
    case "MODE" :
        break;
    default :
        irc::trace("Ei tallenneta toimitoa: {$plugin->line->code}");
        return;
}

$msg = mb_convert_encoding($msg, "UTF-8","UTF-8,Windows-1252,ISO-8859-15,ISO-8859-1");

$res = $db->query("
INSERT INTO `ircmsg` (`time`, `channel`, `user`, `action`, `nick`, `msg`)
VALUES (
    NOW(),
    '".addslashes($plugin->line->channel)."',
    '".addslashes($plugin->line->from)."',
    '".addslashes($plugin->line->code)."',
    '".addslashes($plugin->line->nick)."',
    '".addslashes($msg)."'
)");
if(DB::IsError($res)) {
    $plugin->message("Virhe kirjoitettassa tietokantaan, ".$res->getMessage().". Plugin poistetaan.");
    unset($plugin->irc->irc_data->triggers->plugins['myStore']);
}
?>
