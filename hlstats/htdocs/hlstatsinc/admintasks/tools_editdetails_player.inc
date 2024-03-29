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

	if ($auth->userdata["acclevel"] < 80) die ("Access denied!");
?>

&nbsp;&nbsp;&nbsp;&nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/downarrow.gif" width=9 height=6 border=0 align="middle" alt="downarrow.gif"><b>&nbsp;<a href="<?php echo $g_options["scripturl"]; ?>?mode=admin&task=tools_editdetails">Edit Player or Clan Details</a></b><br>

<img src="<?php echo $g_options["imgdir"]; ?>/spacer.gif" width=1 height=8 border=0><br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/downarrow.gif" width=9 height=6 border=0 align="middle" alt="downarrow.gif"><b>&nbsp;<?php echo "Edit Player #$id"; ?></b><p>

<form method="POST" action="<?php echo $g_options["scripturl"] . "?mode=admin&task=$selTask&id=$id&" . strip_tags(SID); ?>">
<?php
	$proppage = new PropertyPage("hlstats_Players", "playerId", $id, array(
		new PropertyPage_Group("Profile", array(
			new PropertyPage_Property("fullName", "Real Name", "text"),
			new PropertyPage_Property("email", "E-mail Address", "text"),
			new PropertyPage_Property("homepage", "Homepage URL", "text"),
			new PropertyPage_Property("icq", "ICQ Number", "text"),
            new PropertyPage_Property("jabber", "Jabber account", "text"),
			new PropertyPage_Property("hideranking", "Hide Ranking", "select", "0/No;1/Yes"),
		))
	));


	if ($HTTP_POST_VARS)
	{
		$proppage->update();
		message("success", "Profile updated successfully.");
	}



	$result = $db->query("
		SELECT
			*
		FROM
			hlstats_Players
		WHERE
			playerId='$id'
	");
	if ($db->num_rows() < 1) die("No player exists with ID #$id");

	$data = $db->fetch_array($result);

	echo $g_options["font_title"];
	echo $data["lastName"];
	echo $g_options["fontend_title"];

	echo $g_options["font_normal"];
	echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"
		. "<a href=\"" . $g_options["scripturl"] . "?mode=playerinfo&player=$id&" . strip_tags(SID) . "\">"
		. "(View Player Details)</a>";
	echo $g_options["fontend_normal"];
?><p>

<table width="60%" align="center" border=0 cellspacing=0 cellpadding=0>
<tr>
	<td><?php
		echo $g_options["font_normal"];
		$proppage->draw($data);
		echo $g_options["fontend_normal"];
?>
	<center><input type="submit" value="  Apply  " class="submit"></center></td>
</tr>
</table>
</form>

