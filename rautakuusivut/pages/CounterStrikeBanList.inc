<?php

if(!isset($hldsdb)) $hldsdb =& DB::Connect($settings['hlds']['db']);

// Bannien määrä ja viimeisin banni
$numbans =& $hldsdb->query("SELECT COUNT(bid), MAX(ban_created) FROM amx_bans");
if(DB::IsError($numbans)) {
    $content .= "<strong>500:</strong> Tietokantavirhe; ".$numbans->getMessage();
} else {
    list($numofbans, $lastedited) = $numbans->fetchRow();
    $lmtime = gmstrftime( "%a, %d %b %Y %T %Z", $lastedited);
    $content .= '</p><strong>Rautakuu [dot] org :: CS bannitut</strong> <sup>['.$numofbans.' bannia]</sup></p>';
}

$banlistres =& $hldsdb->query("SELECT player_nick, player_id, ban_length, ban_created, admin_nick FROM amx_bans ORDER BY bid DESC");

if(DB::IsError($banlistres)) {
    $content .= "<strong>500:</strong> Tietokantavirhe haettaessa banlistia; ".$banlistres->getMessage();
} else {
    $content .= "<p>";
    while(list($nick, $sid, $length, $created, $admin_nick) = $banlistres->fetchRow()) {
        if( $length == 0) {
            $length = "Ikuinen";
        } else {
            $leght .= " min";
        }

        $content .= "<br /><a href=\"http://rautakuu.org/hlstats/index.php?st=uniqueid&mode=search&q={$sid}\" >".htmlentities($nick)."</a> / <span title=\"{$admin_nick}: ".strftime("%c", $created)."\">{$length}</span>";
    }
    $content .= "</p>";
}
