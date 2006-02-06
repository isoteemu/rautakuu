<?php

static $regex;

if( $init==true ) {
    $plugin->addRule('code', 'PRIVMSG');
    return;
}

$teinix = array(
    'omg',
//    'imac',
    '\b1o1\b',
//    'l[\w\d]l\b',
    '\bl[\w\d]l\b',
    '\bevo(\b|tatte|t{1,2}a{1,})',
    'stfu',
    'rofl',
    'noob',
    'n00b',
    'xD',
//    'munq',
//    'ihq',
//    'vinq',
//    'parq',
//    'itq',
    '[^\b]Q\b',
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
    $plugin->irc->send("KICK {$plugin->line->channel} {$plugin->line->nick} :Teinixiä, $bye ({$matches[2]})");
}

?>
