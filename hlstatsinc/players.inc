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

	// Player Rankings

	$db->query("SELECT name FROM hlstats_Games WHERE code='$game'");
	if ($db->num_rows() < 1) error("No such game '$game'.");

	list($gamename) = $db->fetch_row();
	$db->free_result();

	if (isset($HTTP_GET_VARS["minkills"]))
	{
		$minkills = intval($HTTP_GET_VARS["minkills"]);
	}
	else
	{
		$minkills = 1;
	}

	pageHeader(
		array($gamename, "Pelaajien sijoitukset"),
		array($gamename=>"%s?game=$game", "Player Rankings"=>"")
	);
?>

<form method="GET" action="<?php echo $g_options["scripturl"]; ?>" name="f">
<input type="hidden" name="mode" value="search">
<input type="hidden" name="game" value="<?php echo $game; ?>">
<input type="hidden" name="st" value="player">

<table width="90%" align="center" border=0 cellspacing=0 cellpadding=2>

<tr valign="bottom">
	<td width="75%"><?php echo $g_options["font_normal"]; ?><b>&#149;</b> Etsi pelaajaa: <input autocomplete="off" name="q" size="20" maxlength="64" class="textbox"> <input type="submit" value="Search" class="smallsubmit" name="laheta"><?php echo $g_options["fontend_normal"]; ?></td>
	<td width="25%" align="right" nowrap><?php echo $g_options["font_normal"]; ?>Siirry <a href="<?php echo $g_options["scripturl"] . "?mode=clans&amp;game=$game"; ?>"><img src="<?php echo $g_options["imgdir"]; ?>/clan.gif" width=16 height=16 hspace=3 border=0 align="middle" alt="clan.gif">Claanien sijoituksiin</a><?php echo $g_options["fontend_normal"]; ?></td>
</tr>
</table><p>
<script>
   InstallAC(document.f,document.f.q,document.f.laheta,"search","en");
</script>
</form>

<?php
	$table = new Table(
		array(
			new TableColumn(
				"lastName",
				"Nimi",
				"width=31&icon=player&link=" . urlencode("mode=playerinfo&amp;player=%k")
			),
			new TableColumn(
				"skill",
				"Pisteet",
				"width=11&align=right"
			),
			new TableColumn(
					"activity",
					"Aktiivisuus",
					"width=10&sort=no&type=bargraph"
					),
		        new TableColumn(
				      "oldrank",
				      "<span title=\"Viikon takainen sijoitus\">lr</span>",
				      "width=5&align=right"
				      ),
			new TableColumn(
				"kills",
				"Tapot",
				"width=11&align=right"
			),
			new TableColumn(
				"deaths",
				"Kuolemat",
				"width=11&align=right"
			),
			new TableColumn(
				"kpd",
				"<span title=\"Tappoa kuolemaa kohden\">KpD</span>",
				"width=11&align=right"
			),
			new TableColumn(
				"playerId",
				"<span title=\"Rautakuun HLStats ID\">ID</span>",
				"width=5&align=right&sort=no"
			)
		),
		"playerId",
		"skill",
		"kpd",
		true
	);

	$result = $db->query("
		SELECT
			hlstats_Players.playerId,
			hlstats_Players.lastName,
			hlstats_Players.skill,
			hlstats_Players.kills,
			hlstats_Players.deaths,
			IFNULL(kills/deaths, '-') AS kpd,
                        IF(".MINACTIVITY." > (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(MAX(hlstats_Events_PlayerActions.eventTime))), ((100/".MINACTIVITY.") * (".MINACTIVITY." - (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(MAX(hlstats_Events_PlayerActions.eventTime))))), 0) as activity,
                        hlstats_Old_Rank.rank AS oldrank
		FROM
			hlstats_Players
                RIGHT JOIN hlstats_Events_PlayerActions ON
                        hlstats_Events_PlayerActions.playerId = hlstats_Players.playerId
                LEFT JOIN hlstats_Old_Rank ON
                        hlstats_Old_Rank.playerId = hlstats_Players.playerId
		WHERE
			hlstats_Players.game='$game'
			AND hlstats_Players.hideranking=0
			AND hlstats_Players.kills >= $minkills
                GROUP BY
                        hlstats_Players.playerId
		ORDER BY
			$table->sort $table->sortorder,
			$table->sort2 $table->sortorder,
			lastName ASC
		LIMIT $table->startitem,$table->numperpage
	");

	$resultCount = $db->query("
		SELECT
			hlstats_Players.playerId
		FROM
			hlstats_Players
        RIGHT JOIN hlstats_Events_PlayerActions USING (playerId)
		WHERE
			game='$game'
			AND hlstats_Players.hideranking=0
			AND hlstats_Players.kills >= $minkills
            AND hlstats_Events_PlayerActions.eventTime IS NOT NULL
        GROUP BY
            hlstats_Players.playerId
    ");

	$numitems = $db->num_rows($resultCount);

	$table->draw($result, $numitems, 90);
?><p>

<form method="GET" action="<?php echo $g_options["scripturl"]; ?>">
<table width="90%" align="center" border=0 cellspacing=0 cellpadding=2>

<tr valign="bottom">
	<td width="75%"><?php echo $g_options["font_normal"]; ?>
<?php
	foreach ($HTTP_GET_VARS as $k=>$v)
	{
		if ($k != "minkills")
		{
			echo "		<input type=\"hidden\" name=\"$k\" value=\"" . htmlspecialchars($v) . "\">\n";
		}
	}
?>
		<b>&#149;</b> Only show players with <input type="text" name="minkills" size=4 maxlength=2 value="<?php echo $minkills; ?>" class="textbox"> or more kills. <input type="submit" value="Apply" class="smallsubmit"><?php echo $g_options["fontend_normal"]; ?> </td>
</tr>

</table>
</form>
