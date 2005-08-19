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
	
	
	// Clan Rankings
	
	$db->query("SELECT name FROM hlstats_Games WHERE code='$game'");
	if ($db->num_rows() < 1) error("No such game '$game'.");
	
	list($gamename) = $db->fetch_row();
	$db->free_result();
	
	if (isset($HTTP_GET_VARS["minmembers"]))
	{
		$minmembers = intval($HTTP_GET_VARS["minmembers"]);
	}
	else
	{
		$minmembers = 2;
	}
	
	pageHeader(
		array($gamename, "Clan Rankings"),
		array($gamename=>"%s?game=$game", "Clan Rankings"=>"")
	);
?>

<form method="GET" action="<?php echo $g_options["scripturl"]; ?>">
<input type="hidden" name="mode" value="search">
<input type="hidden" name="game" value="<?php echo $game; ?>">
<input type="hidden" name="st" value="clan">

<table width="90%" align="center" border=0 cellspacing=0 cellpadding=2>

<tr valign="bottom">
	<td width="75%"><?php echo $g_options["font_normal"]; ?><b>&#149;</b> Find a clan: <input type="text" name="q" size=20 maxlength=64 class="textbox"> <input type="submit" value="Search" class="smallsubmit"><?php echo $g_options["fontend_normal"]; ?></td>
	<td width="25%" align="right" nowrap><?php echo $g_options["font_normal"]; ?>Go to <a href="<?php echo $g_options["scripturl"] . "?mode=players&amp;game=$game"; ?>"><img src="<?php echo $g_options["imgdir"]; ?>/player.gif" width=16 height=16 hspace=3 border=0 align="middle" alt="player.gif">Player Rankings</a><?php echo $g_options["fontend_normal"]; ?></td>
</tr>

</table><p>
</form>

<?php
	$table = new Table(
		array(
			new TableColumn(
				"name",
				"Name",
				"width=30&icon=clan&link=" . urlencode("mode=claninfo&amp;clan=%k")
			),
			new TableColumn(
				"tag",
				"Tag",
				"width=15"
			),
			new TableColumn(
				"skill",
				"Points",
				"width=10&align=right"
			),
			new TableColumn(
				"nummembers",
				"Members",
				"width=10&align=right"
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
			)
		),
		"clanId",
		"skill",
		"kpd",
		true
	);

	$result = $db->query("
		SELECT
			hlstats_Clans.clanId,
			hlstats_Clans.name,
			hlstats_Clans.tag,
			COUNT(hlstats_Players.playerId) AS nummembers,
			SUM(hlstats_Players.kills) AS kills,
			SUM(hlstats_Players.deaths) AS deaths,
			ROUND(AVG(hlstats_Players.skill)) AS skill,
			IFNULL(SUM(hlstats_Players.kills)/SUM(hlstats_Players.deaths), '-') AS kpd
		FROM
			hlstats_Clans
		LEFT JOIN hlstats_Players ON
			hlstats_Players.clan=hlstats_Clans.clanId
		WHERE
			hlstats_Clans.game='$game'
		GROUP BY
			hlstats_Clans.clanId
		HAVING
			nummembers >= $minmembers
		ORDER BY
			$table->sort $table->sortorder,
			$table->sort2 $table->sortorder,
			name ASC
		LIMIT $table->startitem,$table->numperpage
	");
	
	$resultCount = $db->query("
		SELECT
			hlstats_Clans.clanId
		FROM
			hlstats_Clans
		LEFT JOIN hlstats_Players ON
			hlstats_Players.clan=hlstats_Clans.clanId
		WHERE
			hlstats_Clans.game='$game'
		GROUP BY
			hlstats_Clans.clanId
		HAVING
			COUNT(hlstats_Players.playerId) >= $minmembers
	");
	
	$table->draw($result, $db->num_rows($resultCount), 90);
?><p>

<form method="GET" action="<?php echo $g_options["scripturl"]; ?>"> 
<table width="90%" align="center" border=0 cellspacing=0 cellpadding=2>

<tr valign="bottom">
	<td width="75%"><?php echo $g_options["font_normal"]; ?>
<?php
	foreach ($HTTP_GET_VARS as $k=>$v)
	{
		if ($k != "minmembers")
		{
			echo "<input type=\"hidden\" name=\"$k\" value=\"" . htmlspecialchars($v) . "\">\n";
		}
	}
?>
		<b>&#149;</b> Only show clans with <input type="text" name="minmembers" size=4 maxlength=2 value="<?php echo $minmembers; ?>" class="textbox"> or more members. <input type="submit" value="Apply" class="smallsubmit"><?php echo $g_options["fontend_normal"]; ?></td>
</tr>

</table>
</form>
