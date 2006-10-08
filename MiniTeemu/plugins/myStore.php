<?php
// kate: space-indent on; indent-width 4;

/**
 * myStore.php - Tallettaa viestit tietokantaan
 */

static $db, $nicks, $mychannels;

if( $init==true ) {
    include_once("DB.php");
    $db = DB::Connect("mysql://miniteemu:s3cr3t@localhost/rautakuuirc");
    if(DB::IsError($db)) {
        $plugin->message("Virhe yhdistettäessä tietokantaan, ".$db->getMessage());
        unset($plugin->irc->irc_data->triggers->plugins['myStore']);
        return false;
    }

    $db->query('SET NAMES "utf8"');
    
    // Join to invited channels.
    $res =& $db->query('SELECT `channel` FROM `invites`');
    if($res->numRows() >= 1) {
		while(list($chan) = $res->fetchRow()) {
			irc::trace('Joining to invited channel: '.$chan);
			$plugin->irc->join($chan);
    	}
   	}
    return;
}

if(!isset($db)) {
    irc::trace("Yhdistetään tietokantaan");
    $db = DB::Connect("mysql://miniteemu:a24cdcf4903642@localhost/rautakuuirc");
    if(DB::IsError($db)) {
        $plugin->message("Virhe yhdistettäessä tietokantaan, ".$db->getMessage());
        unset($plugin->irc->irc_data->triggers->plugins['myStore']);
        return false;
    }
}

$wanhat = array(
    "Wanha, Sanottu jo %d kertaa",
    "Keksi jotain uutta. urli pastettu jo %d kertaa",
    "Hohhoi, pastettu täällä jo %d:n ootteeseen"
);

//$firsjoinmsg = "{$irc->channel} kanavan säännöt: http://rautakuu.org/drupal/RautakuuIrc";

$msg     = $plugin->line->msg;
$channel = $plugin->line->channel;
$nick    = $plugin->line->nick;
$from    = $plugin->line->from;
$code    = $plugin->line->code;

switch( $plugin->line->code ) {
    case "PRIVMSG" :
        // Don't store my own private messages.
        if($plugin->line->channel == $plugin->irc->botNick) {
            if(preg_match('/^log (([^\s]+) (\d+)|([^\s]+))$/', $plugin->line->msg, $reg)) {
                if(empty($reg[3])) {
                    $reg[3] = 10;
                    $reg[2] = $reg[4];
                }
                $sql = "SELECT COUNT(*) FROM `ircmsg` WHERE `action` = 'PRIVMSG' AND `channel` = ".$db->quote(trim($reg[2]));
                $res = $db->query($sql);
                list($num) = $res->fetchRow();
                if($num <= 0) {
                    irc::trace($sql);
                    $plugin->message('Ei logia kanavalta '.$reg[2].' :(');
                    $res->free();
                    return;
                }
                if($reg[3] > $num) $reg[3] = 0;
                else $num = $num-$reg[3];
                $res->free();
                $res = $db->query("SELECT time, nick, msg FROM `ircmsg` WHERE `action` = 'PRIVMSG' AND `channel` = ".$db->quote(trim($reg[2]))." LIMIT ".$num.", ".$reg[3]);
                while(list($_time, $_nick, $_msg) = $res->fetchRow()) {
                    $msg = sprintf('[ %s ] <%s> %s',strftime('%T',strtotime($_time)), $_nick, $_msg);
                    $plugin->message($msg);
                }
            }
            return;
        }
        if(true == false) {
            $match = array();
            if(preg_match('%(ftp|http|https)://([^[\s]*)%si',$msg,$match)) {
                if(!strstr($match[2],"rautakuu.org")) {
                    $res = $db->query("SELECT COUNT(*) FROM `ircmsg` WHERE `msg` LIKE '%".addslashes($match[2])."%' AND `channel` = '{$plugin->line->channel}'");
                    if(DB::IsError($res)) {
                        irc::trace("Ei voitu hakea jo sanottuja urleja: ".$res->getMessage());
                    } elseif($res->numRows() > 0) {
                        list($oldies) = $res->fetchRow();
                        if($oldies >= 1)
                            $plugin->message(sprintf("{$plugin->line->nick}: ".$wanhat[array_rand($wanhat)],$oldies));
                        $res->free();
                    }
                }
            }
        }
        break;
    case "JOIN" :
        /*
        $res = $db->query("SELECT COUNT( * ) FROM `ircmsg` WHERE `action` = 'JOIN' AND `nick` LIKE '{$plugin->line->nick}'");
        list($joincount) = $res->fetchRow();
        if($joincount < 1) {
            $plugin->message($firsjoinmsg, $plugin->line->nick);
        }
        $res->free();
        */
        // Kysellään hyypiön kanavat, jotta tiedetään miltä poistaa.
        $plugin->irc->send("WHOIS ".$plugin->line->nick);
        $msg = "";
        break;
    case "NICK" :
        // Kysellään, millä kanavilla hyypiö ircaa
        $nicks[$plugin->line->msg] = array(
            'time'  => time(),
            'old'   => $plugin->line->nick,
            'act'   => "NICK",
        );
        $plugin->irc->send("WHOIS ".$plugin->irc->msg);
        return; // We are done for now...
        break;
    case "QUIT" :
        $channel = $nicks[$plugin->line->nick]['channel'];
        $nick    = $plugin->line->nick;
        $msg     = $plugin->line->msg;
        $code    = "QUIT";

        unset($nicks[$plugin->line->nick]);
        break;
    case "319" : // channel list

        /*
        if($plugin->line->nick == $plugin->irc->botNick) {
            // Yeah, my channel list
            $mychannels = explode(" ", $plugin->line->msg);
            if(count($nicks) > 0) {
                foreach($nicks as $key => $val) {
                    $plugin->irc->send("WHOIS ".$key);
                }
            }
            return;
        }
        */
        $nicks[$plugin->line->nick]['channel'] = explode(" ", $plugin->line->msg);
        $channel = $nicks[$plugin->line->nick]['channel'];

        if($nicks[$plugin->line->nick]['act'] == "NICK") {
            unset($nicks[$plugin->line->nick]['act']);
            // If request was made over 30s then, don't handle.
            if($nicks[$plugin->line->nick]['time'] > (time()-30)) {
                $nick = $nicks[$plugin->line->nick]['old'];
                $msg  = $plugin->line->nick;
                $code = "NICK";
            } else {
                //unset($nicks[$plugin->line->nick]);
                return;
            }
        } else {
            // We dont need channel listing only.
            return;
        }

        break;
    case "INVITE" :
    	$_channelparts = explode(" ", $plugin->line->data);
    	$msg = $_channelparts[2];
		$channel = $_channelparts[3];
		break;
    case "KICK" :
       	$_whom = explode(" ", $plugin->line->data);
		$msg = $_whom[3];
    case "PART" :
        // Päivitetään kanavalistaus
        $plugin->irc->send("WHOIS ".$plugin->line->nick);
    case "TOPIC" :
    case "MODE" :
        break;
    default :
        irc::trace("Ei tallenneta toimitoa: {$plugin->line->code}");
        return;
}

$nums = 1;
$i    = 0;

if(!isset($channel) && isset($nicks[$nick]['channel'])) $channel = $nicks[$nick]['channel'];

if(is_array($channel) && count($channel) > $nums) $nums = count($channel);
if(is_array($from) && count($from) > $nums) $nums = count($from);
if(is_array($code) && count($code) > $nums) $nums = count($code);
if(is_array($nick) && count($nick) > $nums) $nums = count($nick);
if(is_array($msg) && count($msg) > $nums) $nums = count($msg);

while($i < $nums) {
    if(is_array($channel) && isset($channel[$i])) $_channel = $channel[$i]; elseif(is_array($channel)) $_channel = $channel[count($channel)]; else $_channel = $channel;
    if(is_array($from) && isset($from[$i])) $_from = $from[$i]; elseif(is_array($from)) $_from = $from[count($from)]; else $_from = $from;
    if(is_array($code) && isset($code[$i])) $_code = $code[$i]; elseif(is_array($code)) $_code = $code[count($code)]; else $_code = $code;
    if(is_array($nick) && isset($nick[$i])) $_nick = $nick[$i]; elseif(is_array($nick)) $_nick = $nick[count($nick)]; else $_nick = $nick;
    if(is_array($msg) && isset($msg[$i])) $_msg = $msg[$i]; elseif(is_array($msg)) $_msg = $msg[count($msg)]; else $_msg = $msg;

    $_msg = mb_convert_encoding($_msg, "UTF-8","UTF-8,Windows-1252,ISO-8859-15,ISO-8859-1");

    $sql = "
    INSERT INTO `ircmsg` (`time`, `channel`, `user`, `action`, `nick`, `msg`)
    VALUES (
        NOW(),
        '".addslashes($_channel)."',
        '".addslashes($_from)."',
        '".addslashes($_code)."',
        '".addslashes($_nick)."',
        '".addslashes($_msg)."'
    )";
    $res = $db->query($sql);
    //irc::trace($sql);
    if(DB::IsError($res)) {
        $plugin->message("Virhe kirjoitettassa tietokantaan, ".$res->getMessage());
    }
    //$res->free();
    $i++;
}
?>
