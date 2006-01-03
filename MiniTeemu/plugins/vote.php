<?php
/**
 * vote.php - Käynnistää voten jostain
 */

static $vote;

if( $init==true ) {
    $plugin->addRule('code',   "PRIVMSG");
    $plugin->addRule('prefix', "vote ");

    if(!isset($vote)) $vote = array();
    $vote['ongoing'] = false;
    return;
}

$voteCmd = trim(substr($plugin->line->msg, 5));

/* Onko vanha vote jo umpeutunut? */
switch( $voteCmd ) {

    case "result" :
    case "tulos"  :
        if ($vote['ongoing'] == true && $vote['time'] > time()) {
            $plugin->message("{$plugin->line->nick}: Äänestys ei ole vielä päättynyt.");
            break;
        }
        if( $vote['ongoing'] == true ) $vote['ongoing'] = false;
        $plugin->message("{$plugin->line->nick}: Äänestys oli: \"".$vote['topic']."\"");
        if(($yes = count($vote['votes']['yes'])) > ($no = count($vote['votes']['no']))) {
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
        if($vote['ongoing'] != true) {
            $plugin->message("{$plugin->line->nick}: Äänestyksiä ei ole menossa.");
            break;
        }
        if(isset($vote['votes']['no'][$plugin->line->nick]) || isset($vote['votes']['yes'][$plugin->line->nick])) {
            $plugin->message("{$plugin->line->nick}: Olet jo äänestänyt.");
            break;
        }
        $vote['votes']['no'][$plugin->line->nick] = true;
        break;

    case "yes"   :
    case "kyllä" :
    case "1"     :
    case "Y"     :
    case "K"     :
    case "k"     :
        if($vote['ongoing'] != true) {
            $plugin->message("{$plugin->line->nick}: Äänestyksiä ei ole menossa.");
            break;
        }
        if(isset($vote['votes']['no'][$plugin->line->nick]) || isset($vote['votes']['yes'][$plugin->line->nick])) {
            $plugin->message("{$plugin->line->nick}: Olet jo äänestänyt.");
            break;
        }
        $vote['votes']['yes'][$plugin->line->nick] = true;
        break;

    default :
        if($vote['ongoing'] == true) {
            $plugin->message("{$plugin->line->nick}: Tuntematon äänestys komento. Äänestä joko kyllä tai ei.");
            break;
        }
        if(substr($voteCmd, -1) == "?") {
            $vote['ongoing']      = true;
            $vote['topic']        = $voteCmd;
            $vote['votes']        = array();
            $vote['votes']['yes'] = array();
            $vote['votes']['no']  = array();
            $vote['time']         = (time()+180);
            $plugin->message("Uusi äänestys: {$voteCmd}");
        } else {
            $plugin->message("{$plugin->line->nick}: Tuntematon komento.");
        }
        break;
}

if($vote['ongoing'] == true && $vote['time'] < time()) {
    $plugin->message("{$plugin->line->nick}: Vote on umpeutunut. Kiitoksia osallistuneille.");
    if(($yes = count($vote['votes']['yes'])) > ($no = count($vote['votes']['no']))) {
        $plugin->message("Äänestyksen tulos: Kyllä voitti äänin ".$yes."/".($yes+$no).".");
    } else {
        $plugin->message("Äänestyksen tulos: Ei voitti äänin ".$no."/".($yes+$no).".");
    }
    $vote['ongoing'] = false;
}

?>