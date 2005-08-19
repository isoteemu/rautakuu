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
	
	
	// Search Class
	
	class Search
	{
		var $query;
		var $type;
		var $game;
		
		var $uniqueid_string = "Unique ID";
		var $uniqueid_string_plural = "Unique IDs";
		
		
		function Search ($query, $type, $game)
		{
			$this->query = $query;
			$this->type  = $type;
			$this->game  = $game;
			
			if (MODE == "LAN")
			{
				$this->uniqueid_string = "IP Address";
				$this->uniqueid_string_plural = "IP Addresses";
			}
		}
		
		function drawForm ($getvars=array(), $searchtypes=-1)
		{
			global $g_options, $db;
			
			if (!is_array($searchtypes))
			{
				$searchtypes = array(
					"player"=>"Player names",
					"uniqueid"=>"Player " . $this->uniqueid_string_plural,
					"clan"=>"Clan names"
				);
			}
?>

<table width="90%" align="center" border=0 cellspacing=0 cellpadding=0>

<tr valign="top">
	<td width="100%"><?php echo $g_options["font_normal"]; ?>&nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/downarrow.gif" width=9 height=6 border=0 align="middle" alt="downarrow.gif"><b>&nbsp;Find a Player or Clan</b><?php echo $g_options["fontend_normal"]; ?><p>
	
		<form method="GET" action="<?php echo $g_options["scripturl"]; ?>" name="f">
<?php
			foreach ($getvars as $var=>$value)
			{
				echo "<input type=\"hidden\" name=\"$var\" value=\"$value\">\n";
			}
?>
		
		<table width="100%" border=0 cellspacing=0 cellpadding=0>
		
		<tr>
			<td width="10%">&nbsp;</td>
			<td width="90%">
				<table width="40%" border=0 cellspacing=0 cellpadding=0>
		
				<tr valign="top" bgcolor="<?php echo $g_options["table_border"]; ?>">
					<td>
						<table width="100%" border=0 cellspacing=1 cellpadding=4>
					
						<tr valign="middle">
							<td nowrap width="45%" bgcolor="<?php echo $g_options["table_bgcolor1"]; ?>"><?php echo $g_options["font_normal"]; ?>Search For:<?php echo $g_options["fontend_normal"]; ?></td>
							<td width="55%" bgcolor="<?php echo $g_options["table_bgcolor1"]; ?>"><input autocomplete="off" name="q" size=20 maxlength=128 value="<?php echo stripslashes($this->query); ?>" class="textbox"></td>
<script>
   InstallAC(document.f,document.f.q,document.f.laheta,"search","en");
</script>
						</tr>
						
						<tr valign="middle">
							<td nowrap width="45%" bgcolor="<?php echo $g_options["table_bgcolor1"]; ?>"><?php echo $g_options["font_normal"]; ?>In:<?php echo $g_options["fontend_normal"]; ?></td>
							<td width="55%" bgcolor="<?php echo $g_options["table_bgcolor1"]; ?>">
								<?php
									echo getSelect("st",
										$searchtypes,
										$this->type
									);
								?></td>
						</tr>
						
						<tr valign="middle">
							<td nowrap width="45%" bgcolor="<?php echo $g_options["table_bgcolor1"]; ?>"><?php echo $g_options["font_normal"]; ?>Game:<?php echo $g_options["fontend_normal"]; ?></td>
							<td width="55%" bgcolor="<?php echo $g_options["table_bgcolor1"]; ?>">
								<?php
									$games[""] = "(All)";
									
									$db->query("SELECT code, name FROM hlstats_Games WHERE hidden='0' ORDER BY name");
									while ($db->fetch_row())
									{
										$games[$db->rowdata[0]] = $db->rowdata[1];
									}
									
									echo getSelect("game", $games, $this->game);
								?></td>
						</tr>
						
						</table></td>
					<td align="right">
						<table border=0 cellspacing=1 cellpadding=10 width="100%" height="100%">
						<tr bgcolor="<?php echo $g_options["table_bgcolor1"]; ?>">

							<td><input type="submit" name="laheta" value=" Find Now " class="submit"></td>
						</tr>
						</table></td>
				</tr>
				
				</table></td>
		</tr>
		
		</table>
		</form></td>
</tr>

</table><p>


<?php
		}
	
		function drawResults ($link_player=-1, $link_clan=-1)
		{
			global $g_options, $db;
			
			if ($link_player == -1) $link_player = "mode=playerinfo&amp;player=%k";
			if ($link_clan   == -1) $link_clan   = "mode=claninfo&amp;clan=%k";
?>
<table width="90%" align="center" border=0 cellspacing=0 cellpadding=0>

<tr valign="top">
	<td width="100%"><a name="results"></a>
		<?php echo $g_options["font_normal"]; ?>&nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/downarrow.gif" width=9 height=6 border=0 align="middle" alt="downarrow.gif"><b>&nbsp;Search Results</b><?php echo $g_options["fontend_normal"]; ?></td>
</tr>

</table><p>
<?php
			$sr_query = ereg_replace(" ", "%", $this->query);
			
			if ($this->type == "player")
			{
				$table = new Table(
					array(
						new TableColumn(
							"playerId",
							"ID",
							"width=5align=right"
						),
						new TableColumn(
							"name",
							"Name",
							"width=65&icon=player&link=" . urlencode($link_player)
						),
						new TableColumn(
							"gamename",
							"Game",
							"width=30"
						)
					),
					"playerId",
					"name",
					"playerId",
					false,
					50,
					"page",
					"sort",
					"sortorder",
					"results",
					"asc"
				);
				
				if ($this->game)
					$andgame = "AND hlstats_Players.game='" . $this->game . "'";
				else
					$andgame = "";
				
				$result = $db->query("
					SELECT
						hlstats_PlayerNames.playerId,
						hlstats_PlayerNames.name,
						hlstats_Games.name AS gamename
					FROM
						hlstats_PlayerNames
					LEFT JOIN hlstats_Players ON
						hlstats_Players.playerId = hlstats_PlayerNames.playerId
					LEFT JOIN hlstats_Games ON
						hlstats_Games.code = hlstats_Players.game
					WHERE
						hlstats_Games.hidden='0' AND
						hlstats_PlayerNames.name LIKE '%$sr_query%'
						$andgame
					GROUP BY
						hlstats_PlayerNames.playerId
					ORDER BY
						$table->sort $table->sortorder,
						$table->sort2 $table->sortorder
					LIMIT $table->startitem,$table->numperpage
				");
				
				$resultCount = $db->query("
					SELECT
						COUNT(*)
					FROM
						hlstats_PlayerNames
					LEFT JOIN hlstats_Players ON
						hlstats_Players.playerId = hlstats_PlayerNames.playerId
					WHERE
						hlstats_PlayerNames.name LIKE '%$sr_query%'
						$andgame
				");
	
				list($numitems) = $db->fetch_row($resultCount);
			
				$table->draw($result, $numitems, 90);
			}
			elseif ($this->type == "uniqueid")
			{
				$table = new Table(
					array(
						new TableColumn(
							"uniqueId",
							$this->uniqueid_string,
							"width=15&align=right"
						),
						new TableColumn(
							"lastName",
							"Name",
							"width=50&icon=player&link=" . urlencode($link_player)
						),
						new TableColumn(
							"gamename",
							"Game",
							"width=30"
						),
						new TableColumn(
							"playerId",
							"ID",
							"width=5&align=right"
						)
					),
					"playerId",
					"lastName",
					"uniqueId",
					false,
					50,
					"page",
					"sort",
					"sortorder",
					"results",
					"asc"
				);
				
				if ($this->game)
					$andgame = "AND hlstats_PlayerUniqueIds.game='" . $this->game . "'";
				else
					$andgame = "";
				
				$result = $db->query("
					SELECT
						hlstats_PlayerUniqueIds.uniqueId,
						hlstats_PlayerUniqueIds.playerId,
						hlstats_Players.lastName,
						hlstats_Games.name AS gamename
					FROM
						hlstats_PlayerUniqueIds
					LEFT JOIN hlstats_Players ON
						hlstats_Players.playerId = hlstats_PlayerUniqueIds.playerId
					LEFT JOIN hlstats_Games ON
						hlstats_Games.code = hlstats_PlayerUniqueIds.game
					WHERE
						hlstats_Games.hidden='0' AND
						hlstats_PlayerUniqueIds.uniqueId LIKE '%$sr_query%'
						$andgame
					ORDER BY
						$table->sort $table->sortorder,
						$table->sort2 $table->sortorder
					LIMIT $table->startitem,$table->numperpage
				");
				
				$resultCount = $db->query("
					SELECT
						COUNT(*)
					FROM
						hlstats_PlayerUniqueIds
					LEFT JOIN hlstats_Players ON
						hlstats_Players.playerId = hlstats_PlayerUniqueIds.playerId
					WHERE
						hlstats_PlayerUniqueIds.uniqueId LIKE '%$sr_query%'
						$andgame
				");
				
				list($numitems) = $db->fetch_row($resultCount);
			
				$table->draw($result, $numitems, 90);
			}
			elseif ($this->type == "clan")
			{
				$table = new Table(
					array(
						new TableColumn(
							"tag",
							"Tag",
							"width=15"
						),
						new TableColumn(
							"name",
							"Name",
							"width=50&icon=clan&link=" . urlencode($link_clan)
						),
						new TableColumn(
							"gamename",
							"Game",
							"width=30"
						),
						new TableColumn(
							"clanId",
							"ID",
							"width=5&align=right"
						)
					),
					"clanId",
					"name",
					"tag",
					false,
					50,
					"page",
					"sort",
					"sortorder",
					"results",
					"asc"
				);
				
				if ($this->game)
					$andgame = "AND hlstats_Clans.game='" . $this->game . "'";
				else
					$andgame = "";
				
				$result = $db->query("
					SELECT
						hlstats_Clans.clanId,
						hlstats_Clans.tag,
						hlstats_Clans.name,
						hlstats_Games.name AS gamename
					FROM
						hlstats_Clans
					LEFT JOIN hlstats_Games ON
						hlstats_Games.code = hlstats_Clans.game
					WHERE
						hlstats_Games.hidden='0' AND
						(
							hlstats_Clans.tag LIKE '%$sr_query%'
							OR hlstats_Clans.name LIKE '%$sr_query%'
						)
						$andgame
					ORDER BY
						$table->sort $table->sortorder,
						$table->sort2 $table->sortorder
					LIMIT $table->startitem,$table->numperpage
				");
				
				$resultCount = $db->query("
					SELECT
						COUNT(*)
					FROM
						hlstats_Clans
					WHERE
						(
							tag LIKE '%$sr_query%'
							OR name LIKE '%$sr_query%'
						)
						$andgame
				");
				
				list($numitems) = $db->fetch_row($resultCount);
				
				$table->draw($result, $numitems, 90);
			}
			
			echo "<p><center>"
				. $g_options["font_normal"]
				. "Search results: <b>$numitems</b> items matching \"" . htmlspecialchars($sr_query) . "\"."
				. $g_options["fontend_normal"]
				. "</center>";
		}
	}
?>
