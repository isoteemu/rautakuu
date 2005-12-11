<?php

if( $init==true ) {
    $plugin->addRule('code', 'PRIVMSG');
    return;
}

$teinix = array(
    'omg',
    'imac',
    'vinq',
    'parq',
    'lol',
    'itq',
    'stfu',
    'rofl',
    'munq',
    'noob',
    'n00b',
    'ihq'
);

$byes = array(
    'Näkemiin',
    'Ixudzan',
    'Ciao',
    'Farewell',
    'Adiau^',
    'Hyvästi',
    'Au revoir',
    'Bonne journée',
    'Auf Wiedersehen',
    'Wiedersehen',
);

foreach($teinix as $str) {
    if (stristr($plugin->line->msg, $str)) {
        $bye=$byes[array_rand($byes)];
        irc::trace("Teinixiä havaittu:{$str} in {$plugin->line->msg}");
        $plugin->irc->send("KICK {$plugin->line->channel} {$plugin->line->nick} : Teinixiä. $bye");
    }
}

?>
