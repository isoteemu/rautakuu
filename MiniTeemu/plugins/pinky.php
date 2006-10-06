<?php
static $mathstack;

if($init == true) {
    $plugin->addRule('code', "PRIVMSG");
	$mathstack = array(
		'players' => array(),
		'games' => array(),
		'status' => array(),
		'stats' => array(),
	);
	define('PINKY_GAMESIZE', 20);
	return true;
}


$operatus = array(
	'+',
	'-',
	'*'
);

/*
$mathstack = array(
	'players' => array(
		'IsoTeemu' => array(
			'game' => 0,
			'math' => 4,
			'last' => time(),
		),
		'JanOks' => array(
			'game' => 0,
			'math' => 3,
			'last' => time(),
		),
	),
	'games' => array(
		0 => array(
			0 => '$math',
			1 => '$math',
			'[..]'
		),
	),
	'status' => array(
		0 => array(
			'owner' => 'IsoTeemu',
			'began' => time(),
			'channel' => '#rautakuu',
		),
	),
	'stats' => array(
		0 => array(
			'IsoTeemu' => 18,
			'JanOks' => 24,
		),
	),
);

*/
// Valmistelu. Poista tosi-tosi-tosi tyhmät pelaajat.
$ongoing = array();
if(count($mathstack['players'])) {
	foreach($mathstack['players'] as $player => $_null) {
		if($mathstack['players'][$player]['last']+60 < time()) {
			irc::trace('Poistetaan typerys '.$player.' ajassa '.($mathstack['players'][$player]['last']+60).' vs '.time());
			if(isset($mathstack['games'][$mathstack['players'][$player]['game']])) {
				$plugin->message('Öy nöy, olit liian tyhmä! Pelisuorituksesi keskeytettiin.', $player);
			}
			unset($mathstack['players'][$player]);
		} elseif(isset($mathstack['games'][$mathstack['players'][$player]['game']])) {
			$ongoing[$mathstack['players'][$player]['game']] = true;
		}
	}
}
// Poista pelit, joilla ei ole ole ollut pelaajia toviin
if(count($mathstack['games'])) {
	foreach($mathstack['games'] as $game => $_null) {
		if(!isset($ongoing[$game]) && $mathstack['status'][$game]['began']+180 < time()) {
			irc::trace('Poistetaan peli #'.$game);
			unset($mathstack['games'][$game]);
			unset($mathstack['status'][$game]);
		}
	}
}

// Toimintakomennot
if(preg_match('/^pinky ([^\s]+)(.*)$/', $plugin->line->msg, $act)) {
	switch($act[1]) {
		case 'uusi' :

			/*
			if($mathstack['game'][$mathstack['players'][$plugin->line->nick]['game']] &&
			  $mathstack['players'][$plugin->line->nick]['math'] < PINKY_GAMESIZE ) {
				$plugin->message('Olet jo pelissä mukana.');
				break;
			}
			*/
			if($mathstack['players'][$plugin->line->nick]['game']) {
				$plugin->message('Olet jo pelissä mukana.');
				break;
			}

			if($plugin->line->channel == $plugin->irc->botNick) {
				$plugin->message('Peli tulee aloittaa julkisella kanavalla.');
				return;
			}

			$gameon = false;
			foreach($mathstack['status'] as $game => $status) {
				if($status['channel'] == $plugin->line->channel) {
					// Tarkasta, onko pelaajia
					foreach($mathstack['players'] as $playerid => $player) {
						if($player['game'] == $game) {
							$gameon = true;
							break;
						}
					}
					if($gameon) {
						$plugin->message('Kanavalla on keskeneräinen peli menossa. Odota viikko.');
						return;
					}
				}
			}

			$game = count($mathstack['games'])+1;
			while(isset($mathstack['games'][$game])) {
				$game++;
			}

			// Populoi lasku-array.
			$i = 0;
			while($i < 20) {
				$mathstack['games'][$game][$i] = (string) rand(0,9).$operatus[array_rand($operatus)].rand(0,9);
				$i++;
			}

			// Lisää pelin luoja peliin.
			$mathstack['players'][$plugin->line->nick] = array(
				'game' => $game,
				'math' => 0,
				'last' => time()
			);

			$mathstack['status'][$game] = array(
				'owner' => $plugin->line->nick,
				'began' => time(),
				'channel' => $plugin->line->channel
			);

			$mathstack['stats'][$plugin->line->channel] = array();
			$plugin->message('Uusi pinky peli alkamassa. Liity peliin komennolla: pinky liity');
			$plugin->irc->_send('PRIVMSG '.$plugin->line->nick.' :Olet aloittanut pinky pelin. Aloita sanomalla "Teemu on paras".');

			return;
		case 'liity' :
			if(isset($mathstack['players'][$plugin->line->nick])) {
				$plugin->message('Olet jo pelissä mukana.');
			}
			$game = false;
			foreach($mathstack['status'] as $gameid => $status) {
				if($status['channel'] == $plugin->line->channel) {
					$game = $gameid;
					break;
				}
			}

			if(!$game) {
				$plugin->message('Kanavalla '.$plugin->line->channel.' ei ole peliä meneillään. Aloita peli sanomalla: pinky uusi');
				return;
			}

			$mathstack['players'][$plugin->line->nick] = array(
				'game' => $gameid,
				'last' => time(),
				'math' => 0,
			);
			$plugin->message('Olet liittynyt pinky peliin. Aloita sanomalla "'.$mathstack['status'][$game]['owner'].' on X".', $plugin->line->nick);
			return;

		case 'abort' :
			if(!$mathstack['players'][$plugin->line->nick]) {
				$plugin->message('Et ole missään pelissä mukana.');
			}

			foreach($mathstack['status'] as $gameid => $status) {
				if($status['owner'] == $plugin->line->nick) {
					$abort = true;
					foreach($mathstack['players'] as $playerid => $player) {
						if($player['game'] == $gameid) {
							$abort = false;
							break;
						}
					}
					if(!$abort) {
						$plugin->message('Et voi erota aloittamastasi pelistä.');
						return;
					}
					break;
				}
			}

			unset($mathstack['players'][$plugin->line->nick]);
			$plugin->message('Erosit pelistä.',$plugin->line->nick);
			return;

		case 'tulos' :
			// Käy läpi onko pelaajat valmiita.
			$ongoing = false;

			if($act[2]) $channel = trim($act[2]);
			elseif($plugin->line->channel != $plugin->irc->botNick) $channel = $plugin->line->channel;
			elseif(isset($mathstack['players'][$plugin->line->nick])) $channel = $mathstack['status'][$mathstack['players'][$plugin->line->nick]['game']]['channel'];
			else {
				$plugin->message('Minkähän kanavan peliä mahtaa herra tarkoittaa?');
				return;
			}

			if($mathstack['stats'][$channel]) {
				asort($mathstack['stats'][$channel]);
				$i = 1;
				$to = $plugin->line->channel != $plugin->irc->botNick ? $plugin->line->channel : $act[2];
				$plugin->irc->_send(sprintf('PRIVMSG %s :Sija | Pelaaja      | Aika', $top));
				foreach($mathstack['stats'][$channel] as $who => $time) {
					$plugin->irc->_send(sprintf('PRIVMSG %s :[ %2d ] %-12s | %ds.', $to, $i, $who, $time));
					$i++;
				}
			} else {
				$plugin->message('Kanavalla '.$channel.' ei ole pelattu pinky-pelejä');
			}

			break;
		default :
			// Me no know.
			$plugin->message($plugin->line->nick.': Komennot ovat: pinky uusi, pinky liity, pinky abort, pinky tulos');
			return;
	}
}

if($plugin->line->channel == $plugin->irc->botNick && $plugin->line->nick != $plugin->irc->botNick) {
	if(!isset($mathstack['players'][$plugin->line->nick])) return;
	if($mathstack['players'][$plugin->line->nick]['math'] == PINKY_GAMESIZE) return;

	// Ensin leimat
	$mathstack['players'][$plugin->line->nick]['last'] = time();
	if(!isset($mathstack['players'][$plugin->line->nick]['began'])) $mathstack['players'][$plugin->line->nick]['began'] = $mathstack['players'][$plugin->line->nick]['last'];

	$game =& $mathstack['players'][$plugin->line->nick]['game'];
	$math =& $mathstack['players'][$plugin->line->nick]['math'];

	$val = 0;
	$_math = $mathstack['games'][$game][$math];
	irc::trace("Calculus: $_math");
	eval("\$val = $_math;");
	if($plugin->line->msg == $val) {
		// Oikein. Seuraava tehtävä.

		$math++;
	}
	
	if($math == PINKY_GAMESIZE) {
		// Tädää, tehty!
		// Kauanko kesti?
		$time = $mathstack['players'][$plugin->line->nick]['last'] - $mathstack['players'][$plugin->line->nick]['began'];
		$channel =& $mathstack['status'][$game]['channel'];
		$mathstack['stats'][$channel][$plugin->line->nick] = $time;
		$plugin->irc->_send('PRIVMSG '.$plugin->line->nick.' :Suoritettu ajassa '.$time.'s.');

		// Onko tulokset tiedossa?
		$_ongoing = false;
		foreach($mathstack['players'] as $playerid => $player) {
			if($player['game'] == $game && $player['math'] == PINKY_GAMESIZE) {
				continue;
			} else {
				$_ongoing = true;
				break;
			}
		}

		unset($mathstack['players'][$plugin->line->nick]);
		// Xiit, pyydetään itseltämme tuloksia.
		$plugin->irc->_send('PRIVMSG '.$plugin->line->nick.' :Peli suoritettu!');
		if($_ongoing == false) $plugin->irc->_send('PRIVMSG '.$plugin->irc->botNick.' :pinky tulos '.$channel);

	} else {
		// Näytä lasku
		$plugin->irc->_send('PRIVMSG '.$plugin->line->nick.' :'.$mathstack['games'][$game][$math]);
	}
}

?>