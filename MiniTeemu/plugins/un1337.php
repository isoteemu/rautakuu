<?php

static $regex;

if( $init==true ) {
    $plugin->addRule('code', 'PRIVMSG');
    return;
}

$teinix = array(
    'omg',
    'imac',
    'vinq',
    'parq',
    '\blol',
    'lol\b',
    '\blol\b',
    'l0l',
    'lÃ¥l',
    'itq',
    'stfu',
    'rofl',
    'munq',
    'noob',
    'n00b',
    'ihq',
    'xD'
);

$byes = array(
    'Näkemiin',
    'Ixudzan',
    'Ciao',
    'Farewell',
    'Adiau^',
    'olet heikoin lenkki. Hyvästi.',
    'Au revoir',
    'Bonne journë',
    'Auf Wiedersehen',
    'Wiedersehen',
);

if(!isset($regex)) {
    $regex = "/(";
    foreach($teinix as $str) {
        if(substr($regex,-1) != "(") $regex .= "|";
        $regex .= $str;
    }
    $regex .= ")/i";
    irc::trace("regex: ".$regex);
}

$matches = array();

if (preg_match($regex, $plugin->line->msg, $matches)) {
    $bye =& $byes[array_rand($byes)];
    irc::trace("Teinixiä havaittu:{$matches[2]} in {$plugin->line->msg}");
    $plugin->irc->send("KICK {$plugin->line->channel} {$plugin->line->nick} :Teinixiä, $bye");
}

?>
