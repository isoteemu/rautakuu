<?php

if( $init==true ) {
    $plugin->addRule('code' => 'PRIVMSG');
    return;
}

$teinix = array(
    '/[^\d\w]omg[^\d\w]/i',
    '/[^\d\w]imac[^\d\w]/i',
    '/[^\d\w]vinq[^\d\w]/i',
    '/[^\d\w]parq[^\d\w]/i',
    '/[^\d\w]lol[^\d\w]/i',
    '/[^\d\w]itq[^\d\w]/i',
    '/[^\d\w]stfu[^\d\w]/i',
);

$tulitikut = array();

if( preg_match_all( $teinix, $plugin->line->msg, $tulitikut )) {
    for ($i=0; $i< count($tulitikut[0]); $i++) {
        $sanat .= " {$matches[0][$i]}";
    }
    irc::trace("Teinixiä havaittu:{$sanat}");
    $plugin->send("KICK {$plugin->line->nick}: Teinixiä havaittu. Terveiset Chevyltä.");
}

?>