<?php
	/*
	 * HLstats - Real-time player and clan rankings and statistics for Half-Life
	 * http://sourceforge.net/projects/hlstats/
	 *
	 * Copyright (C) 2001  Simon Garner
	 *
	 * This program is free software; you can redistribute it and/or
	 * modify it under the terms of the GNU General Public License
	 * as published by the Free Software Foundation; either version 2
	 * of the License, or (at your option) any later version.
	 *
	 * This program is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 * GNU General Public License for more details.
	 *
	 * You should have received a copy of the GNU General Public License
	 * along with this program; if not, write to the Free Software
	 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
	 */
	
	if(!defined("_HLSTATS")) die("Direct access denied.");
	
    include_once(INCLUDE_PATH."/geoip.inc");
	
	// Player Details
	
	$player = intval($HTTP_GET_VARS["player"]);
	$uniqueid  = strval($HTTP_GET_VARS["uniqueid"]);
	$game = strval($HTTP_GET_VARS["game"]);
	
	if (!$player && $uniqueid)
	{
		if (!$game)
		{
			header("Location: " . $g_options["scripturl"] . "?mode=search&st=uniqueid&q=$uniqueid");
			exit;
		}
		
		$db->query("
			SELECT
				playerId
			FROM
				hlstats_PlayerUniqueIds
			WHERE
				uniqueId='$uniqueid'
				AND game='$game'
		");
		
		if ($db->num_rows() > 1)
		{
			header("Location: " . $g_options["scripturl"] . "?mode=search&st=uniqueid&q=$uniqueid&game=$game");
			exit;
		}
		elseif ($db->num_rows() < 1)
		{
			error("No players found matching uniqueId '$uniqueid'");
		}
		else
		{
			list($player) = $db->fetch_row();
			$player = intval($player);
		}
	}
	elseif (!$player && !$uniqueid)
	{
		error("No player ID specified.");
	}
	
	$db->query("
		SELECT

			hlstats_Players.lastName,
			hlstats_Players.clan,
			hlstats_Players.fullName,
			hlstats_Players.email,
			hlstats_Players.homepage,
			hlstats_Players.icq,
            hlstats_Players.jabber,
            hlstats_Players.msn,
			hlstats_Players.game,
            hlstats_Players.hideranking,
			hlstats_Players.skill,
			hlstats_Players.kills,
			hlstats_Players.deaths,
			IFNULL(kills/deaths, '-') AS kpd,
			hlstats_Players.suicides,
			CONCAT(hlstats_Clans.tag, ' ', hlstats_Clans.name) AS clan_name
		FROM
			hlstats_Players
		LEFT JOIN hlstats_Clans ON
			hlstats_Clans.clanId = hlstats_Players.clan
		WHERE
            hlstats_Players.playerId='$player'
                LIMIT 0, 1
	");
	if ($db->num_rows() != 1)
		error("No such player '$player'.");
	
	$playerdata = $db->fetch_array();

	$db->free_result();
	
	$pl_name = $playerdata["lastName"];
	if (strlen($pl_name) > 10)
	{
		$pl_shortname = substr($pl_name, 0, 8) . "...";
	}
	else
	{
		$pl_shortname = $pl_name;
	}
	$pl_name = ereg_replace(" ", "&nbsp;", htmlspecialchars($pl_name));
	$pl_shortname = ereg_replace(" ", "&nbsp;", htmlspecialchars($pl_shortname));
	$pl_urlname = urlencode($playerdata["lastName"]);
	
	
	$game = $playerdata["game"];
	$db->query("SELECT name FROM hlstats_Games WHERE code='$game'");
	if ($db->num_rows() != 1)
		$gamename = ucfirst($game);
	else
		list($gamename) = $db->fetch_row();
	
	pageHeader(
		array($gamename, _("Player Details"), $pl_name),
		array(
			$gamename=>$g_options["scripturl"] . "?game=$game",
			_("Player Rankings")=>$g_options["scripturl"] . "?mode=players&game=$game",
			_("Player Details")=>""
		),
		$pl_name
	);

?>


<table width="90%" align="center" border=0 cellspacing=0 cellpadding=0>

<tr>
	<td width="60%" colspan=2><?php echo $g_options["font_normal"]; ?>&nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/downarrow.gif" width=9 height=6 border=0 align="middle" alt="downarrow.gif"><b>&nbsp;Player Profile</b><?php echo $g_options["fontend_normal"];?></td>
	<td width="40%" colspan=2><?php echo $g_options["font_normal"]; ?>&nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/downarrow.gif" width=9 height=6 border=0 align="middle" alt="downarrow.gif"><b>&nbsp;Statistics Summary</b><?php echo $g_options["fontend_normal"];?></td>
</tr>

<tr valign="top">
	<td width="5%">&nbsp;</td>
	<td width="50%">&nbsp;<br>
		<table width="95%" border=0 cellspacing=0 cellpadding=0 bgcolor="<?php echo $g_options["table_border"]; ?>">
		<tr>
			<td>
				<table width="100%" border=0 cellspacing=1 cellpadding=4>
			
				<tr bgcolor="<?php echo $g_options["table_bgcolor1"]; ?>">
					<td><?php
						echo $g_options["font_normal"];
						echo _("Member of Clan:");
						echo $g_options["fontend_normal"];
					?></td>
					<td><?php
						echo $g_options["font_normal"];
						if ($playerdata["clan"])
						{
							echo "&nbsp;<a href=\"" . $g_options["scripturl"]
								. "?mode=claninfo&clan=" . $playerdata["clan"]
								. "\"><img src=\"" . $g_options["imgdir"]
								. "/clan.gif\" width=16 height=16 hspace=4 "
								. "border=0 align=\"middle\" alt=\"clan.gif\">"
								. htmlspecialchars($playerdata["clan_name"]) . "</a>";
						}
						else
						{
							echo "(None.)";
						}
						echo $g_options["fontend_normal"];
					?></td>
				</tr>
	
				<tr bgcolor="<?php echo $g_options["table_bgcolor2"]; ?>">
					<td><?php
						echo $g_options["font_normal"];
						echo "Nimi:";
						echo $g_options["fontend_normal"];
					?></td>
					<td><?php
						echo $g_options["font_normal"];
						if ($playerdata["fullName"])
						{
							echo "<b>" . htmlspecialchars($playerdata["fullName"]) . "</b>";
						}
						else
						{
							echo "(Unknown.)";
						}
						echo $g_options["fontend_normal"]; 
					?></td>
				</tr>
		
				<tr bgcolor="<?php echo $g_options["table_bgcolor1"]; ?>">
					<td><?php
						echo $g_options["font_normal"];
						echo _("Email").":";
						echo $g_options["fontend_normal"];
					?></td>
					<td><?php
						echo $g_options["font_normal"];
						if ($email = getEmailLink($playerdata["email"]))
						{
							echo $email;
						}
						else
						{
							echo _("(Unknown.)");
						}
						echo $g_options["fontend_normal"];
					?></td>
				</tr>
				
				<tr bgcolor="<?php echo $g_options["table_bgcolor2"]; ?>">
					<td><?php
						echo $g_options["font_normal"];
						echo "Kotisivu:";
						echo $g_options["fontend_normal"];
					?></td>
					<td><?php
						echo $g_options["font_normal"];
						if ($url = getLink($playerdata["homepage"]))
						{
							echo $url;
						}
						else
						{
                            $db->query("
                                SELECT `uid` FROM `drupal_steamids`
                                INNER JOIN hlstats_PlayerUniqueIds ON
                                    hlstats_PlayerUniqueIds.uniqueId=drupal_steamids.steamid
                                WHERE
                                    hlstats_PlayerUniqueIds.playerId='$player'");
                            list($dui) = $db->fetch_row();
                            if ($dui) {
                                echo getLink("http://tao.rautakuu.org/user/$dui");
                            } else {
							    echo _("(Unknown.)");
                            }
						}
						echo $g_options["fontend_normal"];
					?></td>
				</tr>
				
				<tr bgcolor="<?php echo $g_options["table_bgcolor1"]; ?>">
					<td><?php
						echo $g_options["font_normal"];
						echo "ICQ Number:";
						echo $g_options["fontend_normal"];
					?></td>
					<td><?php
						echo $g_options["font_normal"];
						if ($playerdata["icq"])
						{
							echo "<a href=\"http://wwp.icq.com/"
								. urlencode($playerdata["icq"]) . "\" target=\"_blank\">"
								. htmlspecialchars($playerdata["icq"]) . "</a>";
                        } else {
                            echo _("(Not specified.)");
						}
						echo $g_options["fontend_normal"];
					?></td>
				</tr>

                <tr bgcolor="<?php echo $g_options["table_bgcolor2"]; ?>">
                    <td><?php
                        echo $g_options["font_normal"];
                        echo _("Jabber").":";
                        echo $g_options["fontend_normal"];
                    ?></td>
                    <td><?php
                        echo $g_options["font_normal"];
                        if ($jabber = getEmailLink($playerdata["jabber"], 50, "xmpp"))
						{
                            echo $jabber;
                        } else {
                            echo _("(Not specified.)");
                        }
                        echo $g_options["fontend_normal"];
                    ?></td>
                </tr>

                <tr bgcolor="<?php echo $g_options["table_bgcolor1"]; ?>">
                    <td><?php
                        echo $g_options["font_normal"];
                        echo _("MSN").":";
                        echo $g_options["fontend_normal"];
                    ?></td>
                    <td><?php
                        echo $g_options["font_normal"];
                        if ($playerdata["msn"])
                        {
                            echo "<a href=\"http://members.msn.com/default.msnw?mem="
                                . urlencode($playerdata["msn"]) . "\" target=\"_blank\">"
                                . htmlspecialchars($playerdata["msn"]) . "</a>";
                        } else {
                            echo _("(Not specified.)");
						}
						echo $g_options["fontend_normal"];
					?></td>
				</tr>
				
				<tr bgcolor="<?php echo $g_options["table_bgcolor2"]; ?>">
					<td><?php
						echo $g_options["font_normal"];
						echo _("Country").":";
						echo $g_options["fontend_normal"];
					?></td>
					<td><?php

					$db->query("
                        SELECT ipAddress
                        FROM hlstats_Events_Connects
                        WHERE `playerId` = '$player'
                        ORDER BY `eventTime` DESC
                        LIMIT 0,1");
                    list($ip) = $db->fetch_row();
					if(!$ip) {
					   $maanimi = _("(Unknown.)");
					} else {

					  $gi = geoip_open(GEOIPDAT, GEOIP_STANDARD);
					  $maanimi = geoip_country_name_by_addr($gi, $ip);
					  $flagimg = strtolower(geoip_country_code_by_addr($gi, $ip));
					  geoip_close($gi);

					  echo flag($flagimg);
					}


						echo $g_options["font_normal"];
						echo "&nbsp;".ucfirst(strtolower(_($maanimi)));
						echo $g_options["fontend_normal"];
					?></td>
				</tr>

				<tr bgcolor="<?php echo $g_options["table_bgcolor2"]; ?>">
					<td><?php
						echo $g_options["font_normal"];
						echo _("Player ID:");
						echo $g_options["fontend_normal"];
					?></td>
					<td><?php
						echo $g_options["font_normal"];
						echo $player;
						echo $g_options["fontend_normal"];
					?></td>
				</tr>

				<tr bgcolor="<?php echo $g_options["table_bgcolor1"]; ?>">
					<td><?php
						echo $g_options["font_normal"];
						if (MODE == "LAN")
						{
							echo "IP Addresses:";
						}
						else
						{
							echo "STEAM ID:";
						}
						echo $g_options["fontend_normal"];
					?></td>
					<td><?php
						echo $g_options["font_normal"];
						if (MODE == "NameTrack")
						{
							echo "(Unknown.)";
						}
						else
						{
							$db->query("
								SELECT
									uniqueId
								FROM
									hlstats_PlayerUniqueIds
								WHERE
									playerId='$player'
							");
							
							$i=0;
							while (list($uqid) = $db->fetch_row())
							{
								if ($i > 0) echo ", ";
								if (!$steamid) $steamid = $uqid;
								echo $uqid;
								$i++;
							}
						}
						echo $g_options["fontend_normal"];
					?></td>
				</tr>
				
				<tr bgcolor="<?php echo $g_options["table_bgcolor2"]; ?>">
					<td><?php
						echo $g_options["font_normal"];
						echo _("Last Connect").":*";
						echo $g_options["fontend_normal"];
					?></td>
					<td><?php
						echo $g_options["font_normal"];
						$db->query("
							SELECT
								DATE_FORMAT(MAX(eventTime), '%r, %a. %D %b.')
							FROM
								hlstats_Events_Connects
							LEFT JOIN hlstats_Servers ON
								hlstats_Servers.serverId=hlstats_Events_Connects.serverId
							WHERE
								hlstats_Servers.game='$game' AND playerId='$player'
						");
						list($lastevent) = $db->fetch_row();
						
						if ($lastevent)
						{
							echo $lastevent;
						}
						else
						{
							echo "(Unknown.)";
						}
				?><?php echo $g_options["fontend_normal"]; ?></td>
				</tr>

				<tr bgcolor="<?php echo $g_options["table_bgcolor1"]; ?>">
					<td><?php
						echo $g_options["font_normal"];
						echo _("Total Connection Time").":*";
						echo $g_options["fontend_normal"];
					?></td>
					<td><?php
						echo $g_options["font_normal"];
						$db->query("
							SELECT  
								SEC_TO_TIME(SUM(TIME_TO_SEC(time))) AS tTime
							FROM
								hlstats_Events_StatsmeTime
							LEFT JOIN hlstats_Servers ON
								hlstats_Servers.serverId=hlstats_Events_StatsmeTime.serverId
							WHERE   
								hlstats_Servers.game='$game' AND playerId='$player'
						");
						list($tTime) = $db->fetch_row();

						if ($tTime)
						{
							echo $tTime;
						}
						else
						{
							echo "(Unknown.)";
						}
				?><?php echo $g_options["fontend_normal"]; ?></td>
				</tr>

				<tr bgcolor="<?php echo $g_options["table_bgcolor2"]; ?>">
					<td><?php
						echo $g_options["font_normal"];
						echo _("Average Ping:")."*";
						echo $g_options["fontend_normal"];
					?></td>
					<td><?php
						echo $g_options["font_normal"];
						$db->query("
							SELECT
								ROUND(SUM(ping) / COUNT(ping), 0) AS av_ping
							FROM
								hlstats_Events_StatsmeLatency
							LEFT JOIN hlstats_Servers ON
								hlstats_Servers.serverId=hlstats_Events_StatsmeLatency.serverId
							WHERE   
								hlstats_Servers.game='$game' AND playerId='$player'
						");
						list($av_ping, $av_latency) = $db->fetch_row();

						if ($av_ping)
						{
						  echo "$av_ping ms";
						}
						else
						{  
							echo _("(Unknown.)");
						}
				?><?php echo $g_options["fontend_normal"]; ?></td>
				</tr>

				</table></td>
		</tr>
		
		</table></td>
	<td width="5%">&nbsp;</td>
	<td width="40%">&nbsp;<br>
		<table width="100%" border=0 cellspacing=0 cellpadding=0 bgcolor="<?php echo $g_options["table_border"]; ?>">

		<tr>
			<td>
				<table width="100%" border=0 cellspacing=1 cellpadding="4">
			
				<tr bgcolor="<?php echo $g_options["table_bgcolor1"]; ?>">
					<td width="45%"><?php
						echo $g_options["font_normal"];
                                               echo _("Ranking:");
						echo $g_options["fontend_normal"];
					?></td>
                                        <td width="55%" colspan="2"><?php

					       $db->query("
                                                    SELECT
                                                        ban_reason
                                                    FROM
                                                        `amx_bans`
                                                    WHERE
                                                        `player_id` = '$steamid'
                                                    AND (
                                                        `ban_length` LIKE '0'
                                                         OR `ban_length` LIKE '0.0'
                                                    )");

						echo $g_options["font_normal"];
							  if($db->num_rows() >= 1) {
							    list($ban_reason) = $db->fetch_row();
							    echo "<b><span style=\"color:red\" title=\"".htmlspecialchars($ban_reason)."\">"._("Banned")."</span> ";
							  } else {

							    $db->query("
                                                                        SELECT
                                                                             IF ( ".MINACTIVITY." > ( UNIX_TIMESTAMP( ) - UNIX_TIMESTAMP( MAX( eventTime ) ) ) , (( 100 /".MINACTIVITY." ) * ( ".MINACTIVITY." - ( UNIX_TIMESTAMP( ) - UNIX_TIMESTAMP( MAX( eventTime ) ) ) ) ) , 0) AS activity
                                                                        FROM
                                                                            `hlstats_Events_PlayerActions`
                                                                        WHERE
                                                                            `playerId`='$player'");
							    list($activity) = $db->fetch_row();

							    if($playerdata['hideranking'] == 0 && $activity > 0) {
							      $res =& $db->query("
                                                                          SELECT
                                                                              skill
                                                                          FROM
                                                                              `hlstats_Players`
                                                                          RIGHT JOIN hlstats_Events_PlayerActions USING (playerId)
                                                                          WHERE
                                                                              `game`='$game'
                                                                              AND `skill`>'".$playerdata['skill']."'
                                                                              AND `kills` >= 1
AND eventTime IS NOT NULL
                                                                              AND `hideranking` = 0 GROUP BY hlstats_Events_PlayerActions.playerId");
                                                              $rank = $db->num_rows($res);
							      $db->query("
                                                                          SELECT
           			                                              playerId,
              			                                              IFNULL(kills / deaths, '-') AS kpd
                                                                          FROM
                                                                              `hlstats_Players`
                                                                          WHERE
                                                                              `game`='$game'
                                                                               AND `skill`='".$playerdata['skill']."'
                                                                               AND `kills` >= 1
                                                                               AND `hideranking` = 0
                                                                          ORDER BY kpd DESC, lastName ASC");
							      while(list($rowpid) = $db->fetch_row()) {
								if($rowpid ==  $playerdata['playerId']) break;
								$rank++;
							      }
							      echo "<b>".$rank."</b>";
							      $db->query("
                                                                          SELECT
                                                                              `rank`
                                                                          FROM
                                                                              `hlstats_Old_Rank`
                                                                          WHERE
                                                                              `game`='$game'
                                                                               AND `playerId`='$player'
                                                                          ");
                                                            list($oldRank) = $db->fetch_row();
                                                            if($oldRank) {
							      $rankDiff = $oldRank-$rank;
							      echo "<sup title=\"Muutos heijastaa viikon takaiseen sijoitukseen\">";
							      if( $rankDiff < 0 ) {
								echo " <font color=\"#ff0000\">($rankDiff sijaa)</font>";
							      } elseif( $rankDiff > 0) {
								echo  "<font color=\"#00ff00\">(+$rankDiff sijaa)</font>";
							      } else {
								echo " (Ei muutosta)";
							      }
							      echo "</sup>";
							    }
							    } else {
							      echo "(piilotettu)";
							    }
							  }
						echo $g_options["fontend_normal"];
                                        ?><script language="Javascript1.2" src="<?= $g_options['scripturl'];?>?mode=adminsfi&amp;js"></script>
                                          <?=$g_options["fontend_normal"]?><span id="<?=$player;?>"></span><script language="Javascript1.2">
                                          var uri='<?= $g_options['scripturl'];?>?mode=adminsfi&game=<?=urlencode($game)?>';
                                          addPlayer('<?=$player;?>');
                                          uasf();
                                          </script><?=$g_options["fontend_normal"];?>
                                        </td>
				</tr>
				

				<tr bgcolor="<?php echo $g_options["table_bgcolor2"]; ?>">
					<td width="45%"><?php
						echo $g_options["font_normal"];
						echo _("Skill Points").":";
						echo $g_options["fontend_normal"];
					?></td>
					<td width="55%" colspan="2"><?php
						echo $g_options["font_normal"];
						echo $playerdata["skill"];
						echo $g_options["fontend_normal"];
					?></td>
				</tr>

                                <tr bgcolor="<?php echo $g_options["table_bgcolor1"]; ?>">
                                        <td width="45%"><?php
					echo $g_options["font_normal"];
                                        echo "Aktiivisuus:*";
                                        echo $g_options["fontend_normal"];
                                        ?></td><td nowrap="nowrap"><?php

					$width = sprintf("%d%%", $activity + 0.5);
                                        $bar_type = 1;

                                        if ($activity > 40) $bar_type = "6";
                                        elseif ($activity > 30) $bar_type = "5";
                                        elseif ($activity > 20) $bar_type = "4";
                                        elseif ($activity > 10) $bar_type = "3";
                                        elseif ($activity > 5) $bar_type = "2";

                                        echo "<img src=\"" . $g_options["imgdir"] . "/bar".$bar_type.".gif\" width=\"".$width."\" height=10 border=0 alt=\"".$playerdata['activity']."%\" align=\"left\">";

                                        ?></td><td width="15%"><?php

					echo $g_options["font_normal"];
                                        echo "&nbsp;".round($activity)."%";
                                        echo $g_options["fontend_normal"];
                                        ?></td>
                                </tr>

				<tr bgcolor="<?php echo $g_options["table_bgcolor2"]; ?>">
					<td width="45%"><?php
						echo "<span align=\"right\">".$g_options["font_normal"];
						echo "Kills:";
						echo $g_options["fontend_normal"]."</span>";;
					?></td>
					<td width="55%" colspan="2"><?php
						echo $g_options["font_normal"];
						echo $playerdata["kills"];
						$db->query("
							SELECT
								COUNT(*)
							FROM
								hlstats_Events_Frags
							LEFT JOIN hlstats_Servers ON
								hlstats_Servers.serverId=hlstats_Events_Frags.serverId
							WHERE
								hlstats_Servers.game='$game' AND killerId='$player'
						");
						list($realkills) = $db->fetch_row();
						echo " <span title=\"Viimeisen ".DELETEDAYS." pÃ¤ivÃ¤n aikana\">($realkills*)</span>";
						echo $g_options["fontend_normal"];
					?></td>
				</tr>
				
				<tr bgcolor="<?php echo $g_options["table_bgcolor1"]; ?>">
					<td width="45%"><?php
						echo $g_options["font_normal"];
						echo "Deaths:";
						echo $g_options["fontend_normal"];
					?></td>
					<td width="55%" colspan="2"><?php
						echo $g_options["font_normal"];
						echo $playerdata["deaths"];
						echo $g_options["fontend_normal"];
					?></td>
				</tr>
				
				<tr bgcolor="<?php echo $g_options["table_bgcolor2"]; ?>">
					<td width="45%"><?php
						echo $g_options["font_normal"];
						echo "Suicides:";
						echo $g_options["fontend_normal"];
					?></td>
					<td width="55%" colspan="2"><?php
						echo $g_options["font_normal"];
						echo $playerdata["suicides"];
						echo $g_options["fontend_normal"];
					?></td>
				</tr>
				
				<tr bgcolor="<?php echo $g_options["table_bgcolor1"]; ?>">
					<td width="45%"><?php
						echo $g_options["font_normal"];
						echo "Kills per Death:";
						echo $g_options["fontend_normal"];
					?></td>
					<td width="55%" colspan="2"><?php
						echo $g_options["font_normal"];
						echo $playerdata["kpd"];
						echo $g_options["fontend_normal"];
					?></td>
				</tr>
				
				<tr bgcolor="<?php echo $g_options["table_bgcolor2"]; ?>">
					<td width="45%"><?php
						echo $g_options["font_normal"];
						echo "Teammate Kills:*";
						echo $g_options["fontend_normal"];
					?></td>
					<td width="55%" colspan="2"><?php
						echo $g_options["font_normal"];
						
						$db->query("
							SELECT
								COUNT(*)
							FROM
								hlstats_Events_Teamkills
							LEFT JOIN hlstats_Servers ON
								hlstats_Servers.serverId=hlstats_Events_Teamkills.serverId
							WHERE
								hlstats_Servers.game='$game' AND killerId='$player'
						");
						list($playerdata["teamkills"]) = $db->fetch_row();
						
						echo $playerdata["teamkills"];
						echo $g_options["fontend_normal"];
					?></td>
				</tr>

				<tr bgcolor="<?php echo $g_options["table_bgcolor1"]; ?>">
					<td width="45%"><?php
						echo $g_options["font_normal"];
						echo "Osumatarkkuus:";
						echo $g_options["fontend_normal"];
					?></td>
					<td width="55%" colspan="2"><?php
						echo $g_options["font_normal"];

						$db->query("
							SELECT
								IFNULL(ROUND((SUM(hlstats_Events_Statsme.hits)
									/ SUM(hlstats_Events_Statsme.shots) * 100), 1), 0.0) AS accuracy
							FROM
								hlstats_Events_Statsme
							LEFT JOIN hlstats_Servers ON
								hlstats_Servers.serverId=hlstats_Events_Statsme.serverId
							WHERE
								hlstats_Servers.game='$game' AND playerId='$player'
						");
						list($playerdata["accuracy"]) = $db->fetch_row();

						if ($playerdata["accuracy"] == 0)
						{
							echo "(Unknown.)";
						}
						else
						{
							echo $playerdata["accuracy"] . "%";
						}
						echo $g_options["fontend_normal"];
					?></td>
				</tr>

                                <tr bgcolor="<?php echo $g_options["table_bgcolor2"]; ?>">
                                        <td width="45%"><?php
                                                echo $g_options["font_normal"];
                                                echo "<a href=\"https://tao.rautakuu.org/cs-pankki\">Rahaa pankissa:</a>";
                                                echo $g_options["fontend_normal"];
                                        ?></td>
                                        <td width="55%" colspan="2"><?php
                                                echo $g_options["font_normal"];
                                                $db->query("
                                                        SELECT
                                                                IFNULL(amount,'0') AS money
                                                        FROM
                                                                bank
                         				                WHERE
                                                                sid='$steamid'
                                                ");
                                                list($playerdata["money"]) = $db->fetch_row();
                                                if(empty($playerdata["money"])) $playerdata["money"] = 0;
                                                echo number_format($playerdata["money"])."<sup>&yen;&euro;$</sup>";
                                                echo $g_options["fontend_normal"];
                                        ?></td>
				</table></td>
		</tr>
		
		</table><br>
		
		
		<?php
			echo $g_options["font_normal"];
			
			echo "&nbsp;<a href=\"" . $g_options["scripturl"]
				. "?mode=playerhistory&player=$player\"><img "
				. "src=\"" . $g_options["imgdir"]
				. "/history.gif\" width=16 height=16 border=0 "
				. "hspace=3 align=\"middle\" alt=\"history.gif\">"
				. htmlspecialchars($playerdata["lastName"]) . "'s Event&nbsp;History</a><br>\n";
			
		?>&nbsp;<a href="<?php echo $g_options["scripturl"]; ?>?mode=search&st=player&q=<?php echo $pl_urlname; ?>"><img src="<?php echo $g_options["imgdir"]; ?>/search.gif" width=16 height=16 hspace=3 border=0 align="middle" alt="search.gif"  rel="nofollow"><?= _("Find other players with the same name");?></a><?php echo $g_options["fontend_normal"]; ?></td>
</tr>

</table><p>

<?php
	
	$tblAliases = new Table(
		array(
			new TableColumn(
				"name",
				"Name",
				"width=25"
			),
			new TableColumn(
				"numuses",
				"Used",
				"width=10&align=right&append=+times"
			),
			new TableColumn(
				"lastuse",
				"Last Use",
				"width=20"
			),
			new TableColumn(
				"kills",
				"Kills",
				"width=10&align=right"
			),
			new TableColumn(
				"deaths",
				"Deaths",
				"width=10&align=right"
			),
			new TableColumn(
				"kpd",
				"Kills per Death",
				"width=10&align=right"
			),
			new TableColumn(
				"suicides",
				"Suicides",
				"width=10&align=right"
			)
		),
		"name",
		"lastuse",
		"name",
		true,
		20,
		"aliases_page",
		"aliases_sort",
		"aliases_sortorder",
		"aliases"
	);

	$result = $db->query("
		SELECT
			name,
			lastuse,
			numuses,
			kills,
			deaths,
			IFNULL(
				kills / deaths,
				'-'
			) AS kpd,
			suicides
		FROM
			hlstats_PlayerNames
		WHERE
			playerId=$player
		ORDER BY
			$tblAliases->sort $tblAliases->sortorder
		LIMIT $tblAliases->startitem,$tblAliases->numperpage
	");
	
	$resultCount = $db->query("
		SELECT
			COUNT(*)
		FROM
			hlstats_PlayerNames
		WHERE
			playerId=$player
	");
	
	list($numitems) = $db->fetch_row($resultCount);

	if ($numitems > 1)
	{
?>

<table width="90%" align="center" border=0 cellspacing=0 cellpadding=0>

<tr>
	<td width="100%" colspan=3><a name="aliases"></a>
<?php echo $g_options["font_normal"]; ?>&nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/downarrow.gif" width=9 height=6 border=0 align="middle" alt="downarrow.gif"><b>&nbsp;Aliases</b><?php echo $g_options["fontend_normal"];?></td>
</tr>

<tr>
	<td width="5%">&nbsp;</td>
	<td width="95%" colspan=2>&nbsp;<br>
	<?php
		$tblAliases->draw($result, $numitems, 100);
	?></td>
</tr>

</table><p>
<?php
	}

	$tblPlayerActions = new Table(
		array(
			new TableColumn(
				"description",
				"Action",
				"width=45"
			),
			new TableColumn(
				"obj_count",
				"Achieved",
				"width=25&align=right&append=+times"
			),
			new TableColumn(
				"obj_bonus",
				"Points Bonus",
				"width=25&align=right"
			)
		),
		"id",
		"obj_count",
		"description",
		true,
		9999,
		"obj_page",
		"obj_sort",
		"obj_sortorder",
		"playeractions"
	);

	$result = $db->query("
		SELECT
			hlstats_Actions.description,
			COUNT(hlstats_Events_PlayerActions.id) AS obj_count,
			COUNT(hlstats_Events_PlayerActions.id) * hlstats_Actions.reward_player AS obj_bonus
		FROM
			hlstats_Actions
		LEFT JOIN hlstats_Events_PlayerActions ON
			hlstats_Events_PlayerActions.actionId = hlstats_Actions.id
		LEFT JOIN hlstats_Servers ON
			hlstats_Servers.serverId=hlstats_Events_PlayerActions.serverId
		WHERE
			hlstats_Servers.game='$game' AND hlstats_Events_PlayerActions.playerId=$player
		GROUP BY
			hlstats_Actions.id
		ORDER BY
			$tblPlayerActions->sort $tblPlayerActions->sortorder,
			$tblPlayerActions->sort2 $tblPlayerActions->sortorder
	");
	
	$numitems = $db->num_rows($result);
	
	if ($numitems > 0)
	{
?>
<table width="90%" align="center" border=0 cellspacing=0 cellpadding=0>

<tr>
	<td width="50%" colspan=2><a name="playeractions"></a>
<?php echo $g_options["font_normal"]; ?>&nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/downarrow.gif" width=9 height=6 border=0 align="middle" alt="downarrow.gif"><b>&nbsp;Player Actions</b><?php echo $g_options["fontend_normal"];?></td>
	<td width="50%" align="right"><?php echo $g_options["font_normal"]; ?>(Last <?php echo DELETEDAYS; ?> Days)<?php echo $g_options["fontend_normal"];?></td>
</tr>

<tr>
	<td width="5%">&nbsp;</td>
	<td width="95%" colspan=2>&nbsp;<br>
	<?php
		$tblPlayerActions->draw($result, $numitems, 100);
	?></td>
</tr>

</table><p>
<?php
	}
	
	
	$tblPlayerPlayerActions = new Table(
		array(
			new TableColumn(
				"description",
				"Action",
				"width=45"
			),
			new TableColumn(
				"obj_count",
				"Achieved",
				"width=25&align=right&append=+times"
			),
			new TableColumn(
				"obj_bonus",
				"Points Bonus",
				"width=25&align=right"
			)
		),
		"id",
		"obj_count",
		"description",
		true,
		9999,
		"ppa_page",
		"ppa_sort",
		"ppa_sortorder",
		"playerplayeractions"
	);

	$result = $db->query("
		SELECT
			hlstats_Actions.description,
			COUNT(hlstats_Events_PlayerPlayerActions.id) AS obj_count,
			COUNT(hlstats_Events_PlayerPlayerActions.id) * hlstats_Actions.reward_player AS obj_bonus
		FROM
			hlstats_Actions
		LEFT JOIN hlstats_Events_PlayerPlayerActions ON
			hlstats_Events_PlayerPlayerActions.actionId = hlstats_Actions.id
		LEFT JOIN hlstats_Servers ON
			hlstats_Servers.serverId=hlstats_Events_PlayerPlayerActions.serverId
		WHERE
			hlstats_Servers.game='$game' AND hlstats_Events_PlayerPlayerActions.playerId=$player
		GROUP BY
			hlstats_Actions.id
		ORDER BY
			$tblPlayerPlayerActions->sort $tblPlayerPlayerActions->sortorder,
			$tblPlayerPlayerActions->sort2 $tblPlayerPlayerActions->sortorder
	");
	
	$numitems = $db->num_rows($result);
	
	if ($numitems > 0)
	{
?>
<table width="90%" align="center" border=0 cellspacing=0 cellpadding=0>

<tr>
	<td width="50%" colspan=2><a name="playerplayeractions"></a>
<?php echo $g_options["font_normal"]; ?>&nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/downarrow.gif" width=9 height=6 border=0 align="middle" alt="downarrow.gif"><b>&nbsp;Player-Player Actions</b><?php echo $g_options["fontend_normal"];?></td>
	<td width="50%" align="right"><?php echo $g_options["font_normal"]; ?>(Last <?php echo DELETEDAYS; ?> Days)<?php echo $g_options["fontend_normal"];?></td>
</tr>

<tr>
	<td width="5%">&nbsp;</td>
	<td width="95%" colspan=2>&nbsp;<br>
	<?php
		$tblPlayerPlayerActions->draw($result, $numitems, 100);
	?></td>
</tr>

</table><p>
<?php
	}
	
	$tblTeams = new Table(
		array(
			new TableColumn(
				"name",
				"Team",
				"width=35"
			),
			new TableColumn(
				"teamcount",
				"Joined",
				"width=10&align=right&append=+"._("times")
			),
			new TableColumn(
				"percent",
				"Percentage of Times",
				"width=40&sort=no&type=bargraph"
			),
			new TableColumn(
				"percent",
				"%",
				"width=10&sort=no&align=right&append=" . urlencode("%")
			)
		),
		"name",
		"teamcount",
		"name",
		true,
		9999,
		"teams_page",
		"teams_sort",
		"teams_sortorder",
		"teams"
	);
	
	$db->query("
		SELECT
			COUNT(*)
		FROM
			hlstats_Events_ChangeTeam
		WHERE
			playerId=$player
	");
	list($numteamjoins) = $db->fetch_row();

	$result = $db->query("
		SELECT
			IFNULL(hlstats_Teams.name, hlstats_Events_ChangeTeam.team) AS name,
			COUNT(hlstats_Events_ChangeTeam.id) AS teamcount,
			COUNT(hlstats_Events_ChangeTeam.id) / $numteamjoins * 100 AS percent
		FROM
			hlstats_Events_ChangeTeam
		LEFT JOIN hlstats_Teams ON
			hlstats_Events_ChangeTeam.team=hlstats_Teams.code
		LEFT JOIN hlstats_Servers ON
			hlstats_Servers.serverId=hlstats_Events_ChangeTeam.serverId
		WHERE
			hlstats_Servers.game='$game' AND hlstats_Events_ChangeTeam.playerId=$player AND (hidden <>'1' OR hidden IS NULL)
		GROUP BY
			hlstats_Events_ChangeTeam.team
		ORDER BY
			$tblTeams->sort $tblTeams->sortorder,
			$tblTeams->sort2 $tblTeams->sortorder
	");
	
	$numitems = $db->num_rows($result);
	
	if ($numitems > 0)
	{
?>
<table width="90%" align="center" border=0 cellspacing=0 cellpadding=0>

<tr>
	<td width="50%" colspan=2><a name="teams"></a>
<?php echo $g_options["font_normal"]; ?>&nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/downarrow.gif" width=9 height=6 border=0 align="middle" alt="downarrow.gif"><b>&nbsp;Team Selection</b><?php echo $g_options["fontend_normal"];?></td>
	<td width="50%" align="right"><?php echo $g_options["font_normal"]; ?>(Last <?php echo DELETEDAYS; ?> Days)<?php echo $g_options["fontend_normal"];?></td>
</tr>

<tr>
	<td width="5%">&nbsp;</td>
	<td width="95%" colspan=2>&nbsp;<br>
	<?php
		$tblTeams->draw($result, $numitems, 100);
	?></td>
</tr>

</table><p>
<?php
	}
	
	$tblRoles = new Table(
		array(
			new TableColumn(
				"name",
				"Role",
				"width=35"
			),
			new TableColumn(
				"rolecount",
				"Joined",
				"width=10&align=right&append=+"._("times")
			),
			new TableColumn(
				"percent",
				"Percentage of Times",
				"width=40&sort=no&type=bargraph"
			),
			new TableColumn(
				"percent",
				"%",
				"width=10&sort=no&align=right&append=" . urlencode("%")
			)
		),
		"name",
		"rolecount",
		"name",
		true,
		9999,
		"roles_page",
		"roles_sort",
		"roles_sortorder",
		"roles"
	);
	
	$db->query("
		SELECT
			COUNT(*)
		FROM
			hlstats_Events_ChangeRole
		WHERE
			playerId=$player
	");
	list($numrolejoins) = $db->fetch_row();

	$result = $db->query("
		SELECT
			IFNULL(hlstats_Roles.name, hlstats_Events_ChangeRole.role) AS name,
			COUNT(hlstats_Events_ChangeRole.id) AS rolecount,
			COUNT(hlstats_Events_ChangeRole.id) / $numrolejoins * 100 AS percent
		FROM
			hlstats_Events_ChangeRole
		LEFT JOIN hlstats_Roles ON
			hlstats_Events_ChangeRole.role=hlstats_Roles.code
		LEFT JOIN hlstats_Servers ON
			hlstats_Servers.serverId=hlstats_Events_ChangeRole.serverId
		WHERE
			hlstats_Servers.game='$game' AND hlstats_Events_ChangeRole.playerId=$player AND (hidden <>'1' OR hidden IS NULL)
		GROUP BY
			hlstats_Events_ChangeRole.role
		ORDER BY
			$tblRoles->sort $tblRoles->sortorder,
			$tblRoles->sort2 $tblRoles->sortorder
	");
	
	$numitems = $db->num_rows($result);
	
	if ($numitems > 0)
	{
?>
<table width="90%" align="center" border="0" cellspacing="0" cellpadding="0">

<tr>
	<td width="50%" colspan=2><a name="roles"></a>
<?php echo $g_options["font_normal"]; ?>&nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/downarrow.gif" width=9 height=6 border=0 align="middle" alt="downarrow.gif"><b>&nbsp;Role Selection</b><?php echo $g_options["fontend_normal"];?></td>
	<td width="50%" align="right"><?php echo $g_options["font_normal"]; ?>(Last <?php echo DELETEDAYS; ?> Days)<?php echo $g_options["fontend_normal"];?></td>
</tr>

<tr>
	<td width="5%">&nbsp;</td>
	<td width="95%" colspan=2>&nbsp;<br>
	<?php
		$tblRoles->draw($result, $numitems, 100);
	?></td>
</tr>

</table><p>

<?php
	}
	
	$tblWeapons = new Table(
		array(
			new TableColumn(
				"weapon",
				"Weapon",
				"width=21&type=weaponimg&align=center&link=" . urlencode("mode=weaponinfo&weapon=%k&game=$game")
			),
			new TableColumn(
				"modifier",
				"Points Modifier",
				"width=10&align=right"
			),
			new TableColumn(
				"kills",
				"Kills",
				"width=12&align=right"
			),
			new TableColumn(
				"percent",
				"Percentage of Kills",
				"width=40&sort=no&type=bargraph"
			),
			new TableColumn(
				"percent",
				"%",
				"width=12&sort=no&align=right&append=" . urlencode("%")
			)
		),
		"weapon",
		"kills",
		"weapon",
		true,
		9999,
		"weap_page",
		"weap_sort",
		"weap_sortorder",
		"weapons"
	);
	
	$result = $db->query("
		SELECT
			hlstats_Events_Frags.weapon,
			IFNULL(hlstats_Weapons.modifier, 1.00) AS modifier,
			COUNT(hlstats_Events_Frags.weapon) AS kills,
			COUNT(hlstats_Events_Frags.weapon) / $realkills * 100 AS percent
		FROM
			hlstats_Events_Frags
		LEFT JOIN hlstats_Weapons ON
			hlstats_Weapons.code = hlstats_Events_Frags.weapon
		LEFT JOIN hlstats_Servers ON
			hlstats_Servers.serverId=hlstats_Events_Frags.serverId
		WHERE
			hlstats_Servers.game='$game' AND hlstats_Events_Frags.killerId=$player
			AND (hlstats_Weapons.game='$game' OR hlstats_Weapons.weaponId IS NULL)
		GROUP BY
			hlstats_Events_Frags.weapon
		ORDER BY
			$tblWeapons->sort $tblWeapons->sortorder,
			$tblWeapons->sort2 $tblWeapons->sortorder
	");
?>
<table width="90%" align="center" border=0 cellspacing=0 cellpadding=0>

<tr>
	<td width="50%" colspan=2><a name="weapons"></a>
<?php echo $g_options["font_normal"]; ?>&nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/downarrow.gif" width=9 height=6 border=0 align="middle" alt="downarrow.gif"><b>&nbsp;Weapon Usage</b><?php echo $g_options["fontend_normal"];?></td>
	<td width="50%" align="right"><?php echo $g_options["font_normal"]; ?>(Last <?php echo DELETEDAYS; ?> Days)<?php echo $g_options["fontend_normal"];?></td>
</tr>

<tr>
	<td width="5%">&nbsp;</td>
	<td width="95%" colspan=2>&nbsp;<br>
	<?php
		$tblWeapons->draw($result, $db->num_rows($result), 100);
	?></td>
</tr>

</table><p>
<!-- Begin StatsMe Addon 1.0 by JustinHoMi@aol.com -->
<?php
	
	$tblWeaponstats = new Table(
		array(
			new TableColumn(
				"smweapon",
				"Weapon",
				"width=21&type=weaponimg&align=center&link=" . urlencode("mode=weaponinfo&weapon=%k&game=$game")
			),
			new TableColumn(
				"smshots",
				"Shots",
				"width=7&align=right"
			),
			new TableColumn(
				"smhits",
				"Hits",
				"width=7&align=right"
			),
			new TableColumn(
				"smdamage",
				"Damage",
				"width=8&align=right"
			),
			new TableColumn(
				"smheadshots",
				"Head Shots",
				"width=6&align=right"
			),
			new TableColumn(
				"smkills",
				"Kills",
				"width=7&align=right"
			),
			new TableColumn(
				"smdeaths",
				"Deaths",
				"width=7&align=right"
			),
			new TableColumn(
				"smkdr",
				"Kills per Death",
				"width=8&align=right"
			),
			new TableColumn(
				"smaccuracy",
				"Accuracy",
				"width=9&align=right&append=" . urlencode("%")
			),
			new TableColumn(
				"smdhr",
				"Damage per Hit",
				"width=7&align=right"
			),
			new TableColumn(
				"smspk",
				"Shots per Kill",
				"width=8&align=right"
			)
		),
		"smweapon",
		"smkdr",
		"smweapon",
		true,
		9999,
		"weap_page",
		"weap_sort",
		"weap_sortorder",
		"weaponstats"
	);
	
	$result = $db->query("
		SELECT
			hlstats_Events_Statsme.weapon AS smweapon,
			SUM(hlstats_Events_Statsme.kills) AS smkills,
			SUM(hlstats_Events_Statsme.hits) AS smhits,
			SUM(hlstats_Events_Statsme.shots) AS smshots,
			SUM(hlstats_Events_Statsme.headshots) AS smheadshots,
			SUM(hlstats_Events_Statsme.deaths) AS smdeaths,
			SUM(hlstats_Events_Statsme.damage) AS smdamage,
			IFNULL((ROUND((SUM(hlstats_Events_Statsme.damage) / SUM(hlstats_Events_Statsme.hits)), 1)), '-') as smdhr,
			SUM(hlstats_Events_Statsme.kills) / IF((SUM(hlstats_Events_Statsme.deaths)=0), 1, (SUM(hlstats_Events_Statsme.deaths))) as smkdr,
			ROUND((SUM(hlstats_Events_Statsme.hits) / SUM(hlstats_Events_Statsme.shots) * 100), 1) as smaccuracy,
			IFNULL((ROUND((SUM(hlstats_Events_Statsme.shots) / SUM(hlstats_Events_Statsme.kills)), 1)), '-') as smspk
		FROM
			hlstats_Events_Statsme
		LEFT JOIN hlstats_Servers ON
			hlstats_Servers.serverId=hlstats_Events_Statsme.serverId
		WHERE
			hlstats_Servers.game='$game' AND hlstats_Events_Statsme.PlayerId=$player
		GROUP BY
			hlstats_Events_Statsme.weapon
		ORDER BY
			$tblWeaponstats->sort $tblWeaponstats->sortorder,
			$tblWeaponstats->sort2 $tblWeaponstats->sortorder
	");	

if ($db->num_rows($result) != 0)
{
?>
<table width="90%" align="center" border=0 cellspacing=0 cellpadding=0>

<tr>
	<td width="50%" colspan=2><a name="weaponstats"></a>
<?php echo $g_options["font_normal"]; ?>&nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/downarrow.gif" width=9 height=6 border=0 align="middle" alt="downarrow.gif"><b>&nbsp;Weapon Stats</b><?php echo $g_options["fontend_normal"];?></td>
	<td width="50%" align="right"><?php echo $g_options["font_normal"]; ?>(Last <?php echo DELETEDAYS; ?> Days)<?php echo $g_options["fontend_normal"];?></td>
</tr>

<tr>
	<td width="5%">&nbsp;</td>
	<td width="95%" colspan=2>&nbsp;<br>
	<?php
		$tblWeaponstats->draw($result, $db->num_rows($result), 100);
	?></td>
</tr>

</table><p>
<!-- End StatsMe Addon 1.0 by JustinHoMi@aol.com -->
<?php
}
	
	$tblWeaponstats2 = new Table(
		array(
			new TableColumn(
				"smweapon",
				"Weapon",
				"width=21&type=weaponimg&align=center&link=" . urlencode("mode=weaponinfo&weapon=%k&game=$game")
			),
			new TableColumn(
				"smhead",
				"Head",
				"width=7&align=right"
			),
			new TableColumn(
				"smchest",
				"Chest",
				"width=7&align=right"
			),
			new TableColumn(
				"smstomach",
				"Stomach",
				"width=7&align=right"
			),
			new TableColumn(
				"smleftarm",
				"Left Arm",
				"width=7&align=right"
			),
			new TableColumn(
				"smrightarm",
				"Right Arm",
				"width=7&align=right"
			),
			new TableColumn(
				"smleftleg",
				"Left Leg",
				"width=7&align=right"
			),
			new TableColumn(
				"smrightleg",
				"Right Leg",
				"width=7&align=right"
			),
			new TableColumn(
				"smleft",
				"Left",
				"width=8&align=right&append=" . urlencode("%")
			),
			new TableColumn(
				"smmiddle",
				"Middle",
				"width=9&align=right&append=" . urlencode("%")
			),
			new TableColumn(
				"smright",
				"Right",
				"width=8&align=right&append=" . urlencode("%")
			)
		),
		"smweapon",
		"smhead",
		"smweapon",
		true,
		9999,
		"weap_page",
		"weap_sort",
		"weap_sortorder",
		"weaponstats2"
	);

	$query = "
		SELECT
			hlstats_Events_Statsme2.weapon AS smweapon,
			SUM(hlstats_Events_Statsme2.head) AS smhead,
			SUM(hlstats_Events_Statsme2.chest) AS smchest,
			SUM(hlstats_Events_Statsme2.stomach) AS smstomach,
			SUM(hlstats_Events_Statsme2.leftarm) AS smleftarm,
			SUM(hlstats_Events_Statsme2.rightarm) AS smrightarm,
			SUM(hlstats_Events_Statsme2.leftleg) AS smleftleg,
			SUM(hlstats_Events_Statsme2.rightleg) AS smrightleg,
			IFNULL(ROUND((SUM(hlstats_Events_Statsme2.leftarm) + SUM(hlstats_Events_Statsme2.leftleg)) / (SUM(hlstats_Events_Statsme2.head) + SUM(hlstats_Events_Statsme2.chest) + SUM(hlstats_Events_Statsme2.stomach) + SUM(hlstats_Events_Statsme2.leftarm ) + SUM(hlstats_Events_Statsme2.rightarm) + SUM(hlstats_Events_Statsme2.leftleg) + SUM(hlstats_Events_Statsme2.rightleg)) * 100, 1), 0.0) AS smleft,
			IFNULL(ROUND((SUM(hlstats_Events_Statsme2.rightarm) + SUM(hlstats_Events_Statsme2.rightleg)) / (SUM(hlstats_Events_Statsme2.head) + SUM(hlstats_Events_Statsme2.chest) + SUM(hlstats_Events_Statsme2.stomach) + SUM(hlstats_Events_Statsme2.leftarm ) + SUM(hlstats_Events_Statsme2.rightarm) + SUM(hlstats_Events_Statsme2.leftleg) + SUM(hlstats_Events_Statsme2.rightleg)) * 100, 1), 0.0) AS smright,
			IFNULL(ROUND((SUM(hlstats_Events_Statsme2.head) + SUM(hlstats_Events_Statsme2.chest) + SUM(hlstats_Events_Statsme2.stomach)) / (SUM(hlstats_Events_Statsme2.head) + SUM(hlstats_Events_Statsme2.chest) + SUM(hlstats_Events_Statsme2.stomach) + SUM(hlstats_Events_Statsme2.leftarm ) + SUM(hlstats_Events_Statsme2.rightarm) + SUM(hlstats_Events_Statsme2.leftleg) + SUM(hlstats_Events_Statsme2.rightleg)) * 100, 1), 0.0) AS smmiddle
		FROM
			hlstats_Events_Statsme2
		LEFT JOIN hlstats_Servers ON
			hlstats_Servers.serverId=hlstats_Events_Statsme2.serverId
		WHERE
			hlstats_Servers.game='$game' AND hlstats_Events_Statsme2.PlayerId=$player
		GROUP BY
			hlstats_Events_Statsme2.weapon
		ORDER BY
			$tblWeaponstats2->sort $tblWeaponstats2->sortorder,
			$tblWeaponstats2->sort2 $tblWeaponstats2->sortorder
	";
        $result = $db->query($query);

if ($db->num_rows($result) != 0)
{
?>
<table width="90%" align="center" border=0 cellspacing=0 cellpadding=0>

<tr>
	<td width="50%" colspan="3"><a name="weaponstats2"></a>
<?php echo $g_options["font_normal"]; ?>&nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/downarrow.gif" width=9 height=6 border=0 align="middle" alt="downarrow.gif"><b>&nbsp;Weapon Target</b><?php echo $g_options["fontend_normal"];?></td>
	<td width="50%" align="right"><?php echo $g_options["font_normal"]; ?>(Last <?php echo DELETEDAYS; ?> Days)<?php echo $g_options["fontend_normal"];?></td>
</tr>

<tr>
	<td width="5%">&nbsp;</td>
	<td width="80%" style="padding-top:20px;padding-left:25px;">
	<?php
		$tblWeaponstats2->draw($result, $db->num_rows($result), 100);
	?></td><td colspan="2" align="left" valign="top" style="padding-top:20px;">

            <table width="100%" border=0 cellspacing=1 cellpadding=4>
	      <tr bgcolor="<?php echo $g_options["table_head_bgcolor"]; ?>"><td align="center"><?php echo $g_options["font_small"]; ?>Targets<?php echo $g_options["fontend_small"]; ?><img src="<?php echo $g_options["imgdir"]; ?>/spacer.gif"width=7 height=7 hspace=4 border=0 align="middle" alt="spacer.gif"></td></tr>
        <tr bgcolor="<?php echo $g_options["table_bgcolor1"]; ?>"><td>
	     <?php
																							  $db->query($query);

              $weapon_data = array();
	      while ($rowdata = $db->fetch_array()) {
		$weapon_data['total']['head']                 += $rowdata["smhead"];
		$weapon_data['total']['leftarm']              += $rowdata["smleftarm"];
		$weapon_data['total']['rightarm']             += $rowdata["smrightarm"];
		$weapon_data['total']['chest']                += $rowdata["smchest"];
		$weapon_data['total']['stomach']              += $rowdata["smstomach"];
		$weapon_data['total']['leftleg']              += $rowdata["smleftleg"];
		$weapon_data['total']['rightleg']             += $rowdata["smrightleg"];
		$weapon_data[$rowdata["smweapon"]]['head']     = $rowdata["smhead"];
		$weapon_data[$rowdata["smweapon"]]['leftarm']  = $rowdata["smleftarm"];
		$weapon_data[$rowdata["smweapon"]]['rightarm'] = $rowdata["smrightarm"];
		$weapon_data[$rowdata["smweapon"]]['chest']    = $rowdata["smchest"];
		$weapon_data[$rowdata["smweapon"]]['stomach']  = $rowdata["smstomach"];
		$weapon_data[$rowdata["smweapon"]]['leftleg']  = $rowdata["smleftleg"];
		$weapon_data[$rowdata["smweapon"]]['rightleg'] = $rowdata["smrightleg"];
	      }

		  $moviesrc = "solttu.swf?paa=".$weapon_data['total']['head']."&amp;okasi=".$weapon_data['total']['rightarm']."&amp;vkasi=".$weapon_data['total']['leftarm']."&amp;rinta=".$weapon_data['total']['chest']."&amp;vatsa=".$weapon_data['total']['stomach']."&amp;ojalka=".$weapon_data['total']['rightleg']."&amp;vjalka=".$weapon_data['total']['leftleg'];
             ?>

               <object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="299" height="372" id="solttu" align="middle">
                 <param name="allowScriptAccess" value="sameDomain" />
                 <param name="movie" value="<?= $moviesrc; ?>" />
		 <param name="quality" value="high" />
		 <param name="bgcolor" value="<?= $g_options["table_bgcolor1"]; ?>" />

                 <embed src="<?= $moviesrc ?>" quality="high" bgcolor="#000000" width="299" height="372" name="solttu" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
               </object>

             </td></tr>
          </table>
        </td>


</tr>

</table><p>
<?php
}
	$tblMaps = new Table(
		array(
			new TableColumn(
				"map",
				"Map Name",
				"width=25&align=center&link=" . urlencode("mode=mapinfo&map=%k&game=$game")
			),
			new TableColumn(
				"kpd",
				"Kills per Death",
				"width=10&align=right"
			),
			new TableColumn(
				"kills",
				"Kills",
				"width=10&align=right"
			),
			new TableColumn(
				"percentage",
				"Percentage of Kills",
				"width=30&sort=no&type=bargraph"
			),
			new TableColumn(
				"percentage",
				"%",
				"width=10&sort=no&align=right&append=" . urlencode("%")
			),
			new TableColumn(
				"deaths",
				"Deaths",
				"width=10&align=right"
			)
		),
		"map",
		"kpd",
		"kills",
		true,
		9999,
		"maps_page",
		"maps_sort",
		"maps_sortorder",
		"maps"
	);

	$result = $db->query("
		SELECT
			IF(map='', '(Unaccounted)', map) AS map,
			SUM(killerId=$player) AS kills,
			SUM(victimId=$player) AS deaths,
			IFNULL(SUM(killerId=$player) / SUM(victimId=$player), '-') AS kpd,
			ROUND(CONCAT(SUM(killerId=$player)) / $realkills * 100, 2) AS percentage # workaround weird mysql bug
		FROM
			hlstats_Events_Frags
		LEFT JOIN hlstats_Servers ON
			hlstats_Servers.serverId=hlstats_Events_Frags.serverId
		WHERE
			hlstats_Servers.game='$game' AND killerId='$player'
			OR victimId='$player'
		GROUP BY
			map
		ORDER BY
			$tblMaps->sort $tblMaps->sortorder,
			$tblMaps->sort2 $tblMaps->sortorder
	");
?>
<table width="90%" align="center" border=0 cellspacing=0 cellpadding=0>

<tr>
	<td width="50%" colspan=2><a name="maps"></a>
<?php echo $g_options["font_normal"]; ?>&nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/downarrow.gif" width=9 height=6 border=0 align="middle" alt="downarrow.gif"><b>&nbsp;Map Performance</b><?php echo $g_options["fontend_normal"];?></td>
	<td width="50%" align="right"><?php echo $g_options["font_normal"]; ?>(Last <?php echo DELETEDAYS; ?> Days)<?php echo $g_options["fontend_normal"];?></td>
</tr>

<tr>
	<td width="5%">&nbsp;</td>
	<td width="95%" colspan=2>&nbsp;<br>
	<?php
		$tblMaps->draw($result, $db->num_rows($result), 100);
	?></td>
</tr>

</table><p>

<?php

	$tblPlayerKillStats = new Table(
		array(
			new TableColumn(
				"name",
				"Victim",
        "width=35&icon=player&link=" . urlencode("mode=playerinfo&player=%k")
			),
			new TableColumn(
				"kills",
				"Times Killed",
        "width=20&align=right"
			),
			new TableColumn(
				"deaths",
				"Deaths by",
        "width=20&align=right"
			),
			new TableColumn(
				"kpd",
				"Kills per Death",
        "width=20&align=right"
			)
		),
		"victimId",
		"kills",
		"deaths",
		true,
		9999,
		"playerkills_page",
		"playerkills_sort",
		"playerkills_sortorder",
		"playerkills"
	);

	if(!isset($killLimit)) { $killLimit = 5; }

	//there might be a better way to do this, but I could not figure one out.

	 $db->query("DROP TABLE IF EXISTS hlstats_Frags_Kills");
	 $db->query("
		CREATE TEMPORARY TABLE hlstats_Frags_Kills
		(
			playerId INT(10),
			kills INT(10),
			deaths INT(10)
		)
	");
	 $db->query("
			INSERT INTO
				hlstats_Frags_Kills
				(
					playerId,
					kills
				)
					SELECT
						victimId,
						killerId
					FROM
						hlstats_Events_Frags
					LEFT JOIN hlstats_Servers ON
						hlstats_Servers.serverId=hlstats_Events_Frags.serverId
					WHERE
						hlstats_Servers.game='$game' AND killerId = $player
	");

	 $db->query("
			INSERT INTO
				hlstats_Frags_Kills
				(
					playerId,
					deaths
				)
					SELECT
						killerId,
						victimId
					FROM
						hlstats_Events_Frags
					LEFT JOIN hlstats_Servers ON
						hlstats_Servers.serverId=hlstats_Events_Frags.serverId
					WHERE
						hlstats_Servers.game='$game' AND victimId = $player
		");

		$result = $db->query("
				SELECT
					hlstats_Players.lastName AS name,
					Count(hlstats_Frags_Kills.kills) AS kills,
					Count(hlstats_Frags_Kills.deaths) AS deaths,
					hlstats_Frags_Kills.playerId as victimId,
					IFNULL(Count(hlstats_Frags_Kills.kills)/Count(hlstats_Frags_Kills.deaths),
						IFNULL(FORMAT(Count(hlstats_Frags_Kills.kills), 2), '-')) AS kpd
				FROM
					hlstats_Frags_Kills
				INNER JOIN
					hlstats_Players
				ON
					hlstats_Frags_Kills.playerId = hlstats_Players.playerId
				GROUP BY
					hlstats_Frags_Kills.playerId
				HAVING
					Count(hlstats_Frags_Kills.kills) >= $killLimit
				ORDER BY
	            $tblPlayerKillStats->sort $tblPlayerKillStats->sortorder,
	            $tblPlayerKillStats->sort2 $tblPlayerKillStats->sortorder
		");

	$numitems = $db->num_rows($result);
			
  	if ($numitems > 0)
		{
?>
<table width="90%" align="center" border=0 cellspacing=0 cellpadding=0>
<tr>
  <td width="50%" colspan=2><a name="playerkills"></a>
<?php echo $g_options["font_normal"]; ?>&nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/downarrow.gif" width=9 height=6 border=0 align="middle" alt="downarrow.gif"><b>&nbsp;Player Kill Statistics (<?php echo $killLimit ?> or more kills)</b><?php echo $g_options["fontend_normal"];?></td>
	<td width="50%" align="right"><?php echo $g_options["font_normal"]; ?>(Last <?php echo DELETEDAYS; ?> Days)<?php echo $g_options["fontend_normal"];?></td>
</tr>

<tr>
	<td width="5%">&nbsp;</td>
	<td width="95%" colspan=2>&nbsp;<br>
	<?php
    $tblPlayerKillStats->draw($result, $numitems, 100);
  ?></td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td colspan=2><form method="GET" action="<?php echo $g_options["scripturl"]; ?>">
	<?php echo $g_options["font_normal"]; ?><?= _("Show people this person has killed"); ?>
	<SELECT name="killLimit" onchange='changeLimit(this.options[this.selectedIndex].value)'>
<?php
  for($j = 0; $j < 16; $j++) {
		echo "<option value=$j";
		if($killLimit == $j) { echo " selected"; }
		echo ">$j</option>";
	}
?>
	</select>
	or more times in the last <?php echo DELETEDAYS; ?> days<?php echo $g_options["fontend_normal"];?>
	<script type="text/javascript" language="javascript">
	<!--
	function changeLimit(num) {
		location = "http://<?php echo $HTTP_HOST . $PHP_SELF ?>?mode=playerinfo&player=<?php echo $player ?>&killLimit=" + num + "#playerkills";
	}
	-->
	</script>
	</form></td>
</tr>
</table><p>
<?php
}
?>

<br>
<br>

<table width="90%" align="center" border=0 cellspacing=0 cellpadding=0>

<tr>
	<td width="100%"><?php echo $g_options["font_normal"]; ?><b>Note</b> Player event histories cover only the last <?php echo DELETEDAYS; ?> days. Items marked "Last <?php echo DELETEDAYS; ?> Days" or "*" above are generated from the player's Event History. Player kill, death and suicide totals and points ratings cover the entire recorded period.<?php echo $g_options["fontend_normal"];?></td>
</tr>
<?php
    $messages['admin'][] = '<a href="'.$g_options["scripturl"].'?mode=admin&task=tools_editdetails_player&id='.$player.'" rel="nofollow">'._("Edit Player Details").'</a>';
?>
</table><p>
