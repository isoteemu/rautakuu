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

	// Map Details

	$map = $HTTP_GET_VARS["map"]
		or error("No map specified.");

	$db->query("SELECT name FROM hlstats_Games WHERE code='$game'");
	if ($db->num_rows() != 1)
		error("Invalid or no game specified.");
	else
		list($gamename) = $db->fetch_row();

	pageHeader(
		array($gamename, _("Map Details"), $map),
		array(
			$gamename=>$g_options["scripturl"] . "?game=$game",
			_("Map Statistics")=>$g_options["scripturl"] . "?mode=maps&amp;game=$game",
			_("Map Details")=>""
		),
		$map
	);



	$table = new Table(
		array(
			new TableColumn(
				"killerName",
				_("Player"),
				"width=60&align=left&flag=1&link=" . urlencode("mode=playerinfo&amp;player=%k")
			),
			new TableColumn(
				"frags",
				"Kills on $map",
				"width=35&align=right"
			)
		),
		"killerId", // keycol
		"frags", // sort_default
		"killerName", // sort_default2
		true, // showranking
		50 // numperpage
	);

	$result = $db->query("
		SELECT
			hlstats_Events_Frags.killerId,
			hlstats_Players.lastName AS killerName,
			COUNT(hlstats_Events_Frags.map) AS frags,
            hlstats_Events_Connects.ipAddress AS ip
		FROM
			hlstats_Events_Frags
		INNER JOIN hlstats_Players ON
			hlstats_Players.playerId = hlstats_Events_Frags.killerId
        INNER JOIN hlstats_Events_Connects ON
            hlstats_Events_Connects.playerId=hlstats_Events_Frags.killerId
		WHERE
			hlstats_Events_Frags.map='$map'
			AND hlstats_Players.game='$game'
			AND hlstats_Players.hideranking<>'1'
		GROUP BY
			hlstats_Events_Frags.killerId,
            hlstats_Events_Connects.playerId
		ORDER BY
			$table->sort $table->sortorder,
			$table->sort2 $table->sortorder,
            hlstats_Events_Connects.eventTime DESC
		LIMIT $table->startitem,$table->numperpage
	");

	$resultCount = $db->query("
		SELECT
			COUNT(DISTINCT hlstats_Events_Frags.killerId),
			SUM(hlstats_Events_Frags.map='$map')
		FROM
			hlstats_Events_Frags
		LEFT JOIN hlstats_Players ON
			hlstats_Players.playerId = hlstats_Events_Frags.killerId
		WHERE
			hlstats_Events_Frags.map='$map'
			AND hlstats_Players.game='$game'
	");

	list($numitems, $totalkills) = $db->fetch_row($resultCount);
?>
<table width="90%" align="center" border=0 cellspacing=0 cellpadding=0>

<tr>
	<td width="50%"><?php echo $g_options["font_normal"]; ?>From a total of <b><?php echo intval($totalkills); ?></b> kills (Last <?php echo DELETEDAYS; ?> Days)<?php echo $g_options["fontend_normal"]; ?></td>
	<td width="50%" align="right"><?php echo $g_options["font_normal"]; ?>Back to <a href="<?php echo $g_options["scripturl"] . "?mode=maps&amp;game=$game"; ?>">Map Statistics</a><?php echo $g_options["fontend_normal"]; ?></td>
</tr>

</table><p>
<?php // figure out URL and absolute path of image
	$imgurl = $g_options["imgdir"] . "/maps/$game/"
		. "$map";

	$image = getImage("/maps/$game/$map");

	if ($image || $g_options["map_dlurl"])
	{
?>
<table width="90%" align="center" border=0 cellspacing=0 cellpadding=0>

<tr valign="top">
	<td width="90%"><?php
		$table->draw($result, $numitems, 100, "center");
?></td>
	<td width=10>&nbsp;&nbsp;&nbsp;&nbsp;</td>
	<td width="10%" align="right" nowrap><?php
		echo $g_options["font_normal"];

		if ($image)
		{
			echo "<img src=\"" . $image["url"] . "\" " . $image["size"] . " border=1 alt=\"$map\">";
		}
		if ($g_options["map_dlurl"])
		{
			$map_dlurl = str_replace("%MAP%", $map, $g_options["map_dlurl"]);
			$map_dlurl = str_replace("%GAME%", $game, $map_dlurl);
			echo "<p><a href=\"$map_dlurl\">Download this map...</a></p>";
		}

		echo $g_options["fontend_normal"];
?></td>
</tr>

</table>
<?php
	}
	else
	{
		$table->draw($result, $numitems, 70, "center");
	}
?>
