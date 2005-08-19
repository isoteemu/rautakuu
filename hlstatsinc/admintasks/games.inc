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
	 
	if ($auth->userdata["acclevel"] < 100) die ("Access denied!");

	$edlist = new EditList("code", "hlstats_Games", "game", false);
	$edlist->columns[] = new EditListColumn("code", "Game Code", 10, true, "text", "", 16);
	$edlist->columns[] = new EditListColumn("name", "Game Name", 30, true, "text", "", 128);
	$edlist->columns[] = new EditListColumn("hidden", "Visibility", 0, true, "select", "0/Show;1/Hide");
	
	
	if ($HTTP_POST_VARS)
	{
		if ($edlist->update())
			message("success", "Operation successful.");
		else
			message("warning", $edlist->error());
	}
	
?>

Enter the codes and full names for all the games you want to collect statistics for. (Game codes should be the same as the mod folder name, e.g. "valve".)<p>

After creating a game, you will be able to configure servers, awards, etc. for that game under Game Settings.<p>

<?php
	
	$result = $db->query("
		SELECT
			code,
			name,
			hidden
		FROM
			hlstats_Games
		ORDER BY
			code ASC
	");
	
	$edlist->draw($result);
?>

<table width="75%" border=0 cellspacing=0 cellpadding=0>
<tr>
	<td align="center"><input type="submit" value="  Apply  " class="submit"></td>
</tr>
</table>

