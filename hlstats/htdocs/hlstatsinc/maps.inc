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

	// Map Statistics

	$db->query("SELECT name FROM hlstats_Games WHERE code='$game'");
	if ($db->num_rows() < 1) error("No such game '$game'.");

	list($gamename) = $db->fetch_row();
	$db->free_result();

	pageHeader(
		array($gamename, "Map Statistics"),
		array($gamename=>"%s?game=$game", "Map Statistics"=>"")
	);


	$tblMaps = new Table(
		array(
			new TableColumn(
				"map",
				"Map Name",
				"width=25&align=center&link=" . urlencode("mode=mapinfo&amp;map=%k&amp;game=$game")
			),
			new TableColumn(
				"kills",
				"Kills",
				"width=10&align=right"
			),
			new TableColumn(
				"percent",
				"Percentage of Kills",
				"width=50&sort=no&type=bargraph"
			),
			new TableColumn(
				"percent",
				"%",
				"width=10&sort=no&align=right&append=" . urlencode("%")
			)
		),
		"map",
		"kills",
		"map",
		true,
		9999,
		"maps_page",
		"maps_sort",
		"maps_sortorder",
		"maps"
	);

	$db->query("
		SELECT
			COUNT(*)
		FROM
			hlstats_Events_Frags
		LEFT JOIN hlstats_Players ON
			hlstats_Players.playerId = hlstats_Events_Frags.killerId
		WHERE
			hlstats_Players.game = '$game'
	");

	list($totalkills) = $db->fetch_row();

	$result = $db->query("
		SELECT
			IF(hlstats_Events_Frags.map='', '(Unaccounted)', hlstats_Events_Frags.map) AS map,
			COUNT(hlstats_Events_Frags.map) AS kills,
			COUNT(hlstats_Events_Frags.map) / $totalkills * 100 AS percent
		FROM
			hlstats_Events_Frags
		LEFT JOIN hlstats_Players ON
			hlstats_Players.playerId = hlstats_Events_Frags.killerId
		WHERE
			hlstats_Players.game='$game'
		GROUP BY
			hlstats_Events_Frags.map
		ORDER BY
			$tblMaps->sort $tblMaps->sortorder,
			$tblMaps->sort2 $tblMaps->sortorder
	");
?>
<table width="90%" align="center" border=0 cellspacing=0 cellpadding=0>

<tr>
	<td width="50%"><?php echo $g_options["font_normal"]; ?>From a total of <b><?php echo $totalkills; ?></b> kills (Last <?php echo DELETEDAYS; ?> Days)<?php echo $g_options["fontend_normal"]; ?></td>
	<td width="50%" align="right"><?php echo $g_options["font_normal"]; ?>Back to <a href="<?php echo $g_options["scripturl"] . "?game=$game"; ?>"><?php echo $gamename; ?></a><?php echo $g_options["fontend_normal"]; ?></td>
</tr>

</table><p>
<?php $tblMaps->draw($result, $db->num_rows($result), 90);
?>
