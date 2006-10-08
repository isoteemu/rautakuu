<?php
/**
 * join.php - Liittyy kanavalle welcomen saatuaan.
 * joined.php hoitaa loppuosan.
 */
if( $init==true ) {
    $plugin->addRule('code', "001");
    $plugin->addRule('break', true);
    return;
}

foreach($plugin->irc->_channels as $channel => $status) {
	if($status == false) {
		$plugin->irc->join($channel);
	}
}

?>