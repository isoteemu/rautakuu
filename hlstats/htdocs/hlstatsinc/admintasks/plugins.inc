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
   
   // Plugin adminstration

	if ($auth->userdata["acclevel"] < 80) die ("Access denied!");

	$edlist = new EditList("rule", "hlstats_server_addons", "game", false);
	$edlist->columns[] = new EditListColumn("rule", "Rule", 25, true, "text", "", 64);
	$edlist->columns[] = new EditListColumn("addon", "Addon", 25, true, "text", "", 64);
	$edlist->columns[] = new EditListColumn("url", "URL", 40, true, "text", "", 255);
	
	
	if ($HTTP_POST_VARS)
	{
		if ($edlist->update())
			message("success", "Operation successful.");
		else
			message("warning", $edlist->error());
	}
	
?>

Here you can define a list of addons (plugins) the HLStats live statistics page will detect.<br>

When HLStats queries a server for the rules the server will return something like this.<br><br>
<table border="0" cellspacing="0" cellpadding="4">
	<tr bgcolor="<?php echo $g_options["table_head_bgcolor"]; ?>">
		<td><?php echo $g_options["font_small"]; ?>Rule<?php echo $g_options["fontend_small"]; ?></td>
		<td><?php echo $g_options["font_small"]; ?>Value<?php echo $g_options["fontend_small"]; ?></td>
	</tr>
	<tr>
		<td><?php echo $g_options["font_normal"]; ?>mp_footsteps</tt><?php echo $g_options["fontend_normal"]; ?></td>
		<td><?php echo $g_options["font_normal"]; ?>1<?php echo $g_options["fontend_normal"]; ?></td>
	</tr>
	<tr>
		<td><?php echo $g_options["font_normal"]; ?>sv_timelimit<?php echo $g_options["fontend_normal"]; ?></td>
		<td><?php echo $g_options["font_normal"]; ?>30<?php echo $g_options["fontend_normal"]; ?></td>
	</tr>
</table><br><br>

Addons usually create a cvar that is publicly available in the rules list. In most cases the cvar that shows the addons 
existance just shows the version of the addon. You can configure HLStats on this page to 
then show the proper name of the plugin and it's version on the live statistics page. For example:<br><br>
<table border="0" cellspacing="0" cellpadding="4">
	<tr bgcolor="<?php echo $g_options["table_head_bgcolor"]; ?>">
		<td><?php echo $g_options["font_small"]; ?>Rule<?php echo $g_options["fontend_small"]; ?></td>
		<td><?php echo $g_options["font_small"]; ?>Value<?php echo $g_options["fontend_small"]; ?></td>
		<td><?php echo $g_options["font_small"]; ?>Addon<?php echo $g_options["fontend_small"]; ?></td>
		<td><?php echo $g_options["font_small"]; ?>Version<?php echo $g_options["fontend_small"]; ?></td>
	</tr>
	<tr>
		<td><?php echo $g_options["font_normal"]; ?>cdversion</tt><?php echo $g_options["fontend_normal"]; ?></td>
		<td><?php echo $g_options["font_normal"]; ?>4.14<?php echo $g_options["fontend_normal"]; ?></td>
		<td><?php echo $g_options["font_normal"]; ?>Cheating Death</tt><?php echo $g_options["fontend_normal"]; ?></td>
		<td><?php echo $g_options["font_normal"]; ?>4.14<?php echo $g_options["fontend_normal"]; ?></td>
	</tr>
	<tr>
		<td><?php echo $g_options["font_normal"]; ?>hlguard_version</tt><?php echo $g_options["fontend_normal"]; ?></td>
		<td><?php echo $g_options["font_normal"]; ?>4.14<?php echo $g_options["fontend_normal"]; ?></td>
		<td><?php echo $g_options["font_normal"]; ?>HLGuard</tt><?php echo $g_options["fontend_normal"]; ?></td>
		<td><?php echo $g_options["font_normal"]; ?>4.14<?php echo $g_options["fontend_normal"]; ?></td>
	</tr>
</table><br><br>

The value in the table above shows the addon version. To include the version in your proper name of the addon you can use a <b>%</b>. 
If the addon happens to have a home page where more information can be found on the addon, you can put it in as the URL which will be 
linked to.<br>
These default addons should help make understanding this feature easier.<br><br>

<?php
	
	$result = $db->query("
		SELECT
			rule,
			addon,
			url
		FROM
			hlstats_server_addons
		ORDER BY
			rule
		ASC
	");
	
	$edlist->draw($result);
?>

<table width="75%" border="0" cellspacing="0" cellpadding="0">
<tr>
	<td align="center"><input type="submit" value="  Apply  " class="submit"></td>
</tr>
</table>

