<?php
/**
 * myStore.php - Tallettaa viestit tietokantaan
 */

static $db;

if( $init==true ) {
    include_once("DB.php");
    $db = DB::Connect("mysql://miniteemu:a24cdcf4903642@localhost/rautakuuirc");
    if(DB::IsError($db)) {
        $plugin->irc->message("Virhe yhdistettäessä tietokantaan, ".$db->getMessage());
        unset($plugin->irc->irc_data->triggers->plugins['myStore']);
        return false;
    }

    return;
}

if(!isset($db)) {
    irc::trace("Yhdistetään tietokantaan");
    $db = DB::Connect("mysql://miniteemu:a24cdcf4903642@localhost/rautakuuirc");
    if(DB::IsError($db)) {
        $plugin->irc->message("Virhe yhdistettäessä tietokantaan, ".$db->getMessage());
        unset($plugin->irc->irc_data->triggers->plugins['myStore']);
        return false;
    }
}

$msg = $plugin->line->msg;

switch( $plugin->line->code ) {
    case "PRIVMSG" :
        // Don't store my own private messages.
        if($plugin->irc->channel != $plugin->line->channel) {
            irc::trace("Privaati viesti? {$plugin->irc->channel} - {$plugin->line->channel}");
            return;
        }
        break;
    case "JOIN" :
        $msg = "";
        break;
    case "NICK" :
    case "PART" :
    case "QUIT" :
    case "KICK" :
        break;
    default :
        irc::trace("Ei tallenneta toimitoa: {$plugin->line->code}");
        return;
}

$res = $db->query("
INSERT INTO `ircmsg` (`time`, `user`, `action`, `nick`, `msg`)
VALUES (
    NOW(),
    '".addslashes($plugin->line->from)."',
    '".addslashes($plugin->line->code)."',
    '".addslashes($plugin->line->nick)."',
    '".addslashes($msg)."'
)");
if(DB::IsError($res)) {
    $plugin->irc->message("Virhe kirjoitettassa tietokantaan, ".$res->getMessage().". Plugin poistetaan.");
    unset($plugin->irc->irc_data->triggers->plugins['myStore']);
}
?>