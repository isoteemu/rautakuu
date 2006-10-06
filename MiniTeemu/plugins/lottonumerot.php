<?php
if( $init==true ) {
    $plugin->addRule('code', 'PRIVMSG');
    $plugin->addRule('msg',   "MiniTeemu: lotto");
    return;
}

$numero = array();
while(count($numerot)<7) {
        $num = rand(1,42);
        if(!@in_array($num,$numerot)) $numerot[] = $num;
}
sort($numerot);
$msg = "Lottonumerot ovat: ";
foreach($numerot as $oikea) {
        $msg .= $oikea." ";
}

$plugin->message($msg);
?>