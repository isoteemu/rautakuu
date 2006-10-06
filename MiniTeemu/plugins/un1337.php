<?php

static $regex;

if( $init==true ) {
    $plugin->addRule('code', 'PRIVMSG');
    return;
}

$teinix = array(
    'omg',
//    'imac',
    '(^|\s)1o1(\s|$)',
    '(^|\s)lål(\s|$)',
//    'l[\w\d]l\b',
    '(^|\s)l[\w\d]l(\s|$)',
    '(^|\s)(s)evo(\s|$|tatte|t{1,2}a{1,})',
    'stfu',
    'rofl',
    'noob',
    'n00b',
    '(^|\s)xD($|\s)',
//    'munq',
//    'ihq',
//    'vinq',
//    'parq',
//    'itq',
    '[^\s]+Q(\s|$)',
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
    irc::trace("Teinixiä havaittu: ".$matches[0]." in {$plugin->line->msg}");
    $plugin->irc->send("KICK {$plugin->line->channel} {$plugin->line->nick} :Teinixiä, $bye ({$matches[0]})");
}

?>