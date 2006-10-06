<?php
/**
 * vote.php - Käynnistää voten jostain
 */

static $vote;

if( $init==true ) {
    $plugin->addRule('code',   "PRIVMSG");
    $plugin->addRule('prefix', "vote ");

    if(!isset($vote)) $vote = array();
    $vote[$plugin->line->channel]['ongoing'] = false;
    return;
}

$voteCmd = trim(substr($plugin->line->msg, 5));

/* Onko vanha vote jo umpeutunut? */
switch( $voteCmd ) {

    case "result" :
    case "tulos"  :
        if ($vote[$plugin->line->channel]['ongoing'] == true && $vote[$plugin->line->channel]['time'] > time()) {
            $plugin->message("{$plugin->line->nick}: Äänestys ei ole vielä päättynyt.");
            break;
        }
        if( $vote[$plugin->line->channel]['ongoing'] == true ) $vote[$plugin->line->channel]['ongoing'] = false;
        $plugin->message("{$plugin->line->nick}: Äänestys oli: \"".$vote[$plugin->line->channel]['topic']."\"");
        if(($yes = count($vote[$plugin->line->channel]['votes']['yes'])) > ($no = count($vote[$plugin->line->channel]['votes']['no']))) {
            $plugin->message("Äänestyksen tulos: Kyllä voitti äänin ".$yes."/".($yes+$no).".");
        } else {
            $plugin->message("Äänestyksen tulos: Ei voitti äänin ".$no."/".($yes+$no).".");
        }
        break;

    case "no" :
    case "ei" :
    case "0"  :
    case "n"  :
    case "N"  :
    case "e"  :
    case "E"  :
        if($vote[$plugin->line->channel]['ongoing'] != true) {
            $plugin->message("{$plugin->line->nick}: Äänestyksiä ei ole menossa.");
            break;
        }
        if(isset($vote[$plugin->line->channel]['votes']['no'][$plugin->line->nick]) || isset($vote[$plugin->line->channel]['votes']['yes'][$plugin->line->nick])) {
            $plugin->message("{$plugin->line->nick}: Olet jo äänestänyt.");
            break;
        }
        $vote[$plugin->line->channel]['votes']['no'][$plugin->line->nick] = true;
        break;

    case "yes"   :
    case "kyllä" :
    case "1"     :
    case "Y"     :
    case "K"     :
    case "k"     :
        if($vote[$plugin->line->channel]['ongoing'] != true) {
            $plugin->message("{$plugin->line->nick}: Äänestyksiä ei ole menossa.");
            break;
        }
        if(isset($vote[$plugin->line->channel]['votes']['no'][$plugin->line->nick]) || isset($vote[$plugin->line->channel]['votes']['yes'][$plugin->line->nick])) {
            $plugin->message("{$plugin->line->nick}: Olet jo äänestänyt.");
            break;
        }
        $vote[$plugin->line->channel]['votes']['yes'][$plugin->line->nick] = true;
        break;

    default :
        if($vote[$plugin->line->channel]['ongoing'] == true) {
            $plugin->message("{$plugin->line->nick}: Tuntematon äänestys komento. Äänestä joko kyllä tai ei.");
            break;
        }
        if(substr($voteCmd, -1) == "?") {
            $vote[$plugin->line->channel]['ongoing']      = true;
            $vote[$plugin->line->channel]['topic']        = $voteCmd;
            $vote[$plugin->line->channel]['votes']        = array();
            $vote[$plugin->line->channel]['votes']['yes'] = array();
            $vote[$plugin->line->channel]['votes']['no']  = array();
            $vote[$plugin->line->channel]['time']         = (time()+180);
            $plugin->message("Uusi äänestys: {$voteCmd}");
        } else {
            $plugin->message("{$plugin->line->nick}: Tuntematon komento.");
        }
        break;
}

if($vote[$plugin->line->channel]['ongoing'] == true && $vote[$plugin->line->channel]['time'] < time()) {
    $plugin->message("{$plugin->line->nick}: Vote on umpeutunut. Kiitoksia osallistuneille.");
    if(($yes = count($vote[$plugin->line->channel]['votes']['yes'])) > ($no = count($vote[$plugin->line->channel]['votes']['no']))) {
        $plugin->message("Äänestyksen tulos: Kyllä voitti äänin ".$yes."/".($yes+$no).".");
    } else {
        $plugin->message("Äänestyksen tulos: Ei voitti äänin ".$no."/".($yes+$no).".");
    }
    $vote[$plugin->line->channel]['ongoing'] = false;
}

$plugin->irc->flushBuffer();

?>