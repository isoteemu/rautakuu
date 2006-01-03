<?php

if( $init==true ) {
    $plugin->addRule('code', 'PRIVMSG');
    return;
}

$key = "Mikä on kun ei taidot riitä, mikä on kun ei onnistu?";

$percent = 0;
similar_text($key, $plugin->line->msg, $percent);
if( $percent >= 93 ) {
    $plugin->message('"Heikoille voi olla liikaa kutsu metallin, muttei niille joita ohjaa vaisto soturin!"');
}

?>