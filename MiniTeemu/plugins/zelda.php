<?php

static $last;

if($init == true) {
	// Mitä tahansa.
	$plugin->addRule('expire', 1166306399); // 2006-12-16 23:59:59
	$last = 0;
}

// HAX
if($plugin->line->code == 332 || $plugin->line->code == 333) {
	$data = explode(" ", $plugin->line->data);
	$plugin->line->channel = $data[3];
}

$now = strtotime(date('Y-M-d'));
if($now > $last && $plugin->line->code != 332) {
	$plugin->irc->send('topic #rautakuu');
} elseif($plugin->line->code == 332 && $plugin->line->channel == '#rautakuu' && $now > $last) {

	$last = $now;

	$zelda = strtotime('2006-12-08');
	$diff = $zelda-$now;

	if($diff > 0) {
		$zelda = sprintf('Zeldaan %d päivää aikaa',date('d', $diff));
		$topic = preg_replace('/Zeldaan \d+ päivää aikaa/', $zelda, $plugin->line->msg);
		if($topic != $plugin->line->msg) {
			irc::trace('Korvataan Zelda topic: '.$topic);
			$plugin->irc->send('topic #rautakuu :'.$topic);
		} else {
			irc::trace('Asetetaan Zelda topic: '.$topic);
			$plugin->irc->send('topic #rautakuu :'.$plugin->line->msg.' || '.$zelda);
		}
	} elseif($now < 1166220000) {
		$last = 1166220000-1;
		$zelda = 'Zeldaa!';
		$topic = preg_replace('/Zeldaan \d+ päivää aikaa/', $zelda, $plugin->line->msg);
		if($topic != $plugin->line->msg) {
			irc::trace('Korvataan Zelda topic: '.$topic);
			$plugin->irc->send('topic #rautakuu :'.$zelda);
		} else {
			irc::trace('Asetetaan Zelda topic: '.$topic);
			$plugin->irc->send('topic #rautakuu :'.$plugin->line->msg.' || '.$zelda);
		}
	} elseif($now = 1166220000) {
		$zelda = 'Teemun synttärit!';
		$topic = preg_replace('/Zeldaa!/', $zelda, $plugin->line->msg);
		if($topic != $plugin->line->msg) {
			irc::trace('Korvataan Zelda topic: '.$topic);
			$plugin->irc->send('topic #rautakuu :'.$topic);
		} else {
			irc::trace('Asetetaan Zelda topic: '.$topic);
			$plugin->irc->send('topic #rautakuu :'.$plugin->line->msg.' || '.$zelda);
		}
	} else {
		irc::trace('Olisi pitänyt jo expiroitua!');
	}
}
?>