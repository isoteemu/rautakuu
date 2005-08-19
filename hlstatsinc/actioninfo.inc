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
	
	// Action Details

	// Addon created by Rufus (rufus@nonstuff.de)
	
	$action = $HTTP_GET_VARS["action"]
		or error("No action ID specified.");
	
	$db->query("
		SELECT
			description
		FROM
			hlstats_Actions
		WHERE
			code='$action'
			AND game='$game'
	");
	
	if ($db->num_rows() != 1)
	{
		$act_name = ucfirst($action);
	}
	else
	{
		$actiondata = $db->fetch_array();
		$db->free_result();
		$act_name = $actiondata["description"];
	}
	
	$db->query("SELECT name FROM hlstats_Games WHERE code='$game'");
	if ($db->num_rows() != 1)
		error("Invalid or no game specified.");
	else
		list($gamename) = $db->fetch_row();
		
	pageHeader(
		array($gamename, "Action Details", $act_name),
		array(
			$gamename=>$g_options["scripturl"] . "?game=$game",
			"Action Statistics"=>$g_options["scripturl"] . "?mode=actions&amp;game=$game",
			"Action Details"=>""
		),
		$act_name
	);



	$table = new Table(
		array(
			new TableColumn(
				"playerName",
				"Player",
				"width=45&align=left&icon=player&link=" . urlencode("mode=playerinfo&amp;player=%k") 
			),
			new TableColumn(
				"obj_count",
				"Achieved",
				"width=25&align=right"
			),
			new TableColumn(
				"obj_bonus",
				"Skill Bonus Total",
				"width=25&align=right&sort=no"
			)
		),
		"playerId",
		"obj_count",
		"playerName",
		true,
		50
	);
	
	$result = $db->query("
		SELECT
			hlstats_Events_PlayerActions.playerId,
			hlstats_Players.lastName AS playerName,
			COUNT(hlstats_Events_PlayerActions.id) AS obj_count,
			COUNT(hlstats_Events_PlayerActions.id) * hlstats_Actions.reward_player AS obj_bonus
		FROM
			hlstats_Events_PlayerActions, hlstats_Players, hlstats_Actions
		WHERE
			hlstats_Actions.code = '$action' AND
			hlstats_Players.game = '$game' AND
			hlstats_Players.playerId = hlstats_Events_PlayerActions.playerId AND
			hlstats_Events_PlayerActions.actionId = hlstats_Actions.id AND
			hlstats_Players.hideranking<>'1'
		GROUP BY
			hlstats_Events_PlayerActions.playerId
		ORDER BY
			$table->sort $table->sortorder,
			$table->sort2 $table->sortorder
		LIMIT $table->startitem,$table->numperpage
	");
	
	$resultCount = $db->query("
		SELECT
			COUNT(DISTINCT hlstats_Events_PlayerActions.playerId),
			COUNT(hlstats_Events_PlayerActions.Id)
		FROM
			hlstats_Events_PlayerActions, hlstats_Players, hlstats_Actions
		WHERE
			hlstats_Actions.code = '$action' AND
			hlstats_Players.game = '$game' AND
			hlstats_Players.playerId = hlstats_Events_PlayerActions.playerId AND
			hlstats_Events_PlayerActions.actionId = hlstats_Actions.id
	");
	
	list($numitems, $totalact) = $db->fetch_row($resultCount);
?>
<table width="90%" align="center" border=0 cellspacing=0 cellpadding=0>

<tr>
	<td width="50%"><?php echo $g_options["font_normal"]; ?>From a total of <b><?php echo intval($totalact); ?></b> achievements (Last <?php echo DELETEDAYS; ?> Days)<?php echo $g_options["fontend_normal"]; ?></td>
	<td width="50%" align="right"><?php echo $g_options["font_normal"]; ?>Back to <a href="<?php echo $g_options["scripturl"] . "?mode=actions&amp;game=$game"; ?>">Action Statistics</a><?php echo $g_options["fontend_normal"]; ?></td>
</tr>

</table><p>
<?
	$table->draw($result, $numitems, 70, "center");
?>
