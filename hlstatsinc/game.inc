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
	 
	$db->query("SELECT name FROM hlstats_Games WHERE code='$game'");
	if ($db->num_rows() < 1) error("No such game '$game'.");
	
	list($gamename) = $db->fetch_row();
	$db->free_result();

	pageHeader(array($gamename), array($gamename=>""));
?>

<table width="90%" align="center" border=0 cellspacing=0 cellpadding=0>

<tr>
	<td><?php echo $g_options["font_normal"]; ?>&nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/downarrow.gif" width=9 height=6 border=0 align="middle" alt="downarrow.gif"><b>&nbsp;Sections</b><?php echo $g_options["fontend_normal"];?><p>
		
		<table width="75%" align="center" border=0 cellspacing=0 cellpadding=0>
		
		<tr valign="top">
			<td nowrap><?php echo $g_options["font_normal"]; ?><a href="<?php echo $g_options["scripturl"] . "?mode=players&amp;game=$game"; ?>"><img src="<?php echo $g_options["imgdir"]; ?>/player.gif" width=16 height=16 hspace=4 border=0 align="middle" alt="player.gif"><b>Player Rankings...</b></a><br>
		<img src="<?php echo $g_options["imgdir"]; ?>/spacer.gif" width=1 height=5 border=0 alt="spacer.gif"><br>
		<a href="<?php echo $g_options["scripturl"] . "?mode=clans&amp;game=$game"; ?>"><img src="<?php echo $g_options["imgdir"]; ?>/clan.gif" width=16 height=16 hspace=4 border=0 align="middle" alt="clan.gif"><b>Clan Rankings...</b></a><br>
		<img src="<?php echo $g_options["imgdir"]; ?>/spacer.gif" width=1 height=5 border=0 alt=spacer.gif><br>
		<img src="<?php echo $g_options["imgdir"]; ?>/spacer.gif" width=16 height=16 hspace=4 border=0 align="middle" alt="spacer.gif"><a href="<?php echo $g_options["scripturl"] . "?mode=weapons&amp;game=$game"; ?>"><b>Weapon Statistics...</b></a><br>
		<img src="<?php echo $g_options["imgdir"]; ?>/spacer.gif" width=1 height=5 border=0 alt=spacer.gif><br>
                <img src="<?php echo $g_options["imgdir"]; ?>/spacer.gif" width=16 height=16 hspace=4 border=0 align="middle" alt="spacer.gif"><a href="<?php echo $g_options["scripturl"] . "?mode=actions&amp;game=$game"; ?>"><b>Action Statistics...</b></a><br>
		<img src="<?php echo $g_options["imgdir"]; ?>/spacer.gif" width=1 height=5 border=0 alt="spacer.gif"><br>
		<img src="<?php echo $g_options["imgdir"]; ?>/spacer.gif" width=16 height=16 hspace=4 border=0 align="middle" alt="spacer.gif"><a href="<?php echo $g_options["scripturl"] . "?mode=maps&amp;game=$game"; ?>"><b>Map Statistics...</b></a><?php echo $g_options["fontend_normal"]; ?></td>
<?php if (defined("SHOW_STATSDISCLAIMER")) { ?>
			<td width="50%">
				<table width="100%" border=0 cellspacing=0 cellpadding=5>
				
				<tr>
					<td bgcolor="<?php echo $g_options["table_border"]; ?>"><?php echo $g_options["font_normal"]; ?><b>NOTICE</b><br>
<img src="<?php echo $g_options["imgdir"]; ?>/spacer.gif" width=1 height=5 border=0><br>
These rankings and statistics are intended to present only a very simplistic measure of player performance.<br>
<img src="<?php echo $g_options["imgdir"]; ?>/spacer.gif" width=1 height=5 border=0><br>
These statistics should not be taken as a realistic measure of individual player skill.<br>

<?php echo $g_options["fontend_normal"]; ?></td>
				</tr>
				
				</table></td>
<?php } ?>
		</tr>
		
		</table></td>
</tr>

</table><p>
<br>

<?php
	$resultAwards = $db->query("
		SELECT
			hlstats_Awards.awardType,
			hlstats_Awards.code,
			hlstats_Awards.name,
			hlstats_Awards.verb,
			hlstats_Awards.d_winner_id,
			hlstats_Awards.d_winner_count,
			hlstats_Players.lastName AS d_winner_name
		FROM
			hlstats_Awards
		LEFT JOIN hlstats_Players ON
			hlstats_Players.playerId = hlstats_Awards.d_winner_id
		WHERE
			hlstats_Awards.game='$game'
		ORDER BY
			hlstats_Awards.awardType DESC,
			hlstats_Awards.name ASC
	");
	
	$result = $db->query("
		SELECT
			IFNULL(value, 1)
		FROM
			hlstats_Options
		WHERE
			keyname='awards_numdays'
	");

	if ($db->num_rows($result) == 1)
		list($awards_numdays) = $db->fetch_row($result);
	else
		$awards_numdays = 1;

	$result = $db->query("
		SELECT
			DATE_FORMAT(value, '%W %e %b'),
			DATE_FORMAT( DATE_SUB( value, INTERVAL $awards_numdays DAY ) , '%W %e %b' )
		FROM
			hlstats_Options
		WHERE
			keyname='awards_d_date'
	");
	list($awards_d_date, $awards_s_date) = $db->fetch_row($result);

	if ($db->num_rows($resultAwards) > 0 && $awards_d_date)
	{
?>
<table width="90%" align="center" border=0 cellspacing=0 cellpadding=0>

<tr>
	<td><?php echo $g_options["font_normal"]; ?>&nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/downarrow.gif" width=9 height=6 border=0 align="middle" alt="downarrow.gif"><b>&nbsp; <?php if ($awards_numdays == 1) {echo "Daily";} else { echo "$awards_numdays Day";}; ?> Awards (<?php echo "$awards_s_date to $awards_d_date."; ?>)</b><?php echo $g_options["fontend_normal"];?><p>

		<table width="75%" align="center" border=0 cellspacing=0 cellpadding=0 bgcolor="<?php echo $g_options["table_border"]; ?>">
		<tr>
			<td>
				<table width="100%" border=0 cellspacing=1 cellpadding=4>

<?php
		$c = 0;
		while ($awarddata = $db->fetch_array($resultAwards))
		{
			$colour = ($c % 2) + 1;
			$c++;
?>

<tr bgcolor="<?php echo $g_options["table_bgcolor$colour"]; ?>">
	<td width="30%"><?php
			echo $g_options["font_normal"];
			echo htmlspecialchars($awarddata["name"]);
			echo $g_options["fontend_normal"];
		?></td>
	<td width="70%"><?php
			echo $g_options["font_normal"];
			
			if ($awarddata["d_winner_id"])
			{
				echo "<a href=\"" . $g_options["scripturl"] . "?mode=playerinfo&amp;player="
					. $awarddata["d_winner_id"] . "\"><img src=\""
					. $g_options["imgdir"] . "/player.gif\" width=16 height=16 "
					. "hspace=4 border=0 align=\"middle\" alt=\"player.gif\"><b>"
					. htmlspecialchars($awarddata["d_winner_name"]) . "</b></a> ("
					. $awarddata["d_winner_count"] . " " . htmlspecialchars($awarddata["verb"]) . ")";
			}
			else
			{
				echo "&nbsp;&nbsp;(Nobody)";
			}
			
			echo $g_options["fontend_normal"];
		?></td>
</tr>

<?php
		}
?></table></td>
		</tr>
		
		</table></td>
</tr>

</table><p>
<br>

<?php
	}
?>

<table width="90%" align="center" border=0 cellspacing=0 cellpadding=0>

<tr>
	<td><?php echo $g_options["font_normal"]; ?>&nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/downarrow.gif" width=9 height=6 border=0 align="middle" alt="downarrow.gif"><b>&nbsp;Participating Servers</b><?php echo $g_options["fontend_normal"];?><p>
		
		<table width="75%" align="center" border=0 cellspacing=0 cellpadding=0 bgcolor="<?php echo $g_options["table_border"]; ?>">
		
		<tr>
			<td><table width="100%" border=0 cellspacing=1 cellpadding=4>
			
				<tr valign="bottom" bgcolor="<?php echo $g_options["table_head_bgcolor"]; ?>">
					<td width="60%"><?php echo $g_options["font_small"]; ?><font color="<?php echo $g_options["table_head_text"]; ?>">&nbsp;Name</font><?php echo $g_options["fontend_small"]; ?></td>
					<td width="40%"><?php echo $g_options["font_small"]; ?><font color="<?php echo $g_options["table_head_text"]; ?>">&nbsp;Address</font><?php echo $g_options["fontend_small"]; ?></td>
					<td width="20%"><?php echo $g_options["font_small"]; ?><font color="<?php echo $g_options["table_head_text"]; ?>">&nbsp;Statistics</font><?php echo $g_options["fontend_small"]; ?></td>
				</tr>
		
<?php
		$db->query("
			SELECT
				serverId,
				name,
				IF(publicaddress != '',
					publicaddress,
					concat(address, ':', port)
				) AS addr,
				statusurl
			FROM
				hlstats_Servers
			WHERE
				game='$game'
			ORDER BY
				name ASC,
				addr ASC
		");
		
		$i=0;
		while ($rowdata = $db->fetch_array())
		{
			$c = ($i % 2) + 1;
			
			if ($rowdata["statusurl"])
			{
				$addr = "<a href=\"" . $rowdata["statusurl"] . "\">"
					. $rowdata["addr"] . "</a>";
			}
			else
			{
				$addr = $rowdata["addr"];
			}
?>

				<tr valign="middle" bgcolor="<?php echo $g_options["table_bgcolor$c"]; ?>">
					<td align="left"><?php echo $g_options["font_normal"]; ?>&nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/server.gif" width=16 height=16 hspace=3 border=0 align="middle" alt="server.gif"><?php
						echo $rowdata["name"];
						echo $g_options["fontend_normal"]; ?></td>
					<td align="left"><?php
						echo $g_options["font_normal"];
						echo $addr;
						echo $g_options["fontend_normal"];
					?></td>
					<td align="center"><?php
						echo $g_options["font_normal"];
						echo "<a href=\"$g_options[scripturl]?mode=live_stats&amp;server=$rowdata[serverId]\">View</a>";
						echo $g_options["fontend_normal"];
					?></td>
				</tr>
<?php $i++;
		}
?>
		
				</table></td>
		</tr>
		
		</table></td>
</tr>

</table><p>
<br>

<table width="90%" align="center" border=0 cellspacing=0 cellpadding=0>

<tr>
	<td><?php echo $g_options["font_normal"]; ?>&nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/downarrow.gif" width=9 height=6 border=0 align="middle" alt="downarrow.gif"><b>&nbsp;<?php echo $gamename; ?> Statistics</b><?php echo $g_options["fontend_normal"];?><p>

		<?php
			$result = $db->query("SELECT COUNT(*) FROM hlstats_Players WHERE game='$game'");
			list($num_players) = $db->fetch_row($result);
			$num_players = intval($num_players);
			
			$result = $db->query("SELECT COUNT(*) FROM hlstats_Clans WHERE game='$game'");
			list($num_clans) = $db->fetch_row($result);
			$num_clans = intval($num_clans);
			
			$result = $db->query("SELECT COUNT(*) FROM hlstats_Servers WHERE game='$game'");
			list($num_servers) = $db->fetch_row($result);
			$num_servers = intval($num_servers);
			
			$result = $db->query("
				SELECT
					DATE_FORMAT(MAX(eventTime), '%r, %a. %e %b.')
				FROM
					hlstats_Events_Frags
				LEFT JOIN hlstats_Players ON
					hlstats_Players.playerId = hlstats_Events_Frags.serverId
				WHERE
					hlstats_Players.game='$game'
			");
			list($lastevent) = $db->fetch_row($result);
?>

		<table width="75%" align="center" border=0 cellspacing=0 cellpadding=3>
		
		<tr valign="top">
			<td width=10><?php echo $g_options["font_normal"]; ?><b>&#149;&nbsp;</b><?php echo $g_options["fontend_normal"]; ?></td>
			<td width="100%"><?php
				echo $g_options["font_normal"];
				
				echo "<b>$num_players</b> players and <b>$num_clans</b> clans "
					. "ranked on <b>$num_servers</b> servers.";
					
				echo $g_options["fontend_normal"];
			?></td>
		</tr>

<?php
			if ($lastevent)
			{
?>
		<tr valign="top">
			<td width=10><?php echo $g_options["font_normal"]; ?><b>&#149;&nbsp;</b><?php echo $g_options["fontend_normal"]; ?></td>
			<td width="100%"><?php
				echo $g_options["font_normal"];
				
				echo "Last kill <b>$lastevent</b>";
				
				echo $g_options["fontend_normal"];
			?></td>
		</tr>
<?php
			}
?>
		
		<tr valign="top">
			<td width=10><?php echo $g_options["font_normal"]; ?><b>&#149;&nbsp;</b><?php echo $g_options["fontend_normal"]; ?></td>
			<td width="100%"><?php
				echo $g_options["font_normal"];
				
				echo "All statistics are generated in real-time. Event history data expires after <b>" . DELETEDAYS . "</b> days.";
					
				echo $g_options["fontend_normal"];
			?></td>
		</tr>
		
		</table></td>
</tr>

</table><p>
<br>
