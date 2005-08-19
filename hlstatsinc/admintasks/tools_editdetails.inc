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

&nbsp;&nbsp;&nbsp;&nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/downarrow.gif" width=9 height=6 border=0 align="middle" alt="downarrow.gif"><b>&nbsp;<?php echo $task->title; ?></b><p>

You can enter a player or clan ID number directly, or you can search for a player or clan.<p>

<table width="90%" align="center" border=0 cellspacing=0 cellpadding=0>

<tr valign="top">
	<td width="100%"><?php echo $g_options["font_normal"]; ?>&nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/downarrow.gif" width=9 height=6 border=0 align="middle" alt="downarrow.gif"><b>&nbsp;Jump Direct</b><?php echo $g_options["fontend_normal"]; ?><p>
	
		<form method="GET" action="<?php echo $g_options["scripturl"]; ?>">
		<input type="hidden" name="mode" value="admin">
		<table width="100%" border=0 cellspacing=0 cellpadding=0>
		
		<tr>
			<td width="10%">&nbsp;</td>
			<td width="90%">
				<table width="40%" border=0 cellspacing=0 cellpadding=0>
				
				<tr valign="top" bgcolor="<?php echo $g_options["table_border"]; ?>">
					<td>
						<table width="100%" border=0 cellspacing=1 cellpadding=4>
					
						<tr valign="middle">
							<td nowrap width="45%" bgcolor="<?php echo $g_options["table_bgcolor1"]; ?>"><?php echo $g_options["font_normal"]; ?>Type:<?php echo $g_options["fontend_normal"]; ?></td>
							<td width="55%" bgcolor="<?php echo $g_options["table_bgcolor1"]; ?>">
								<?php
									echo getSelect("task",
										array(
											"tools_editdetails_player"=>"Player",
											"tools_editdetails_clan"=>"Clan"
										),
										$this->type
									);
								?></td>
						</tr>
						
						<tr valign="middle">
							<td nowrap width="45%" bgcolor="<?php echo $g_options["table_bgcolor1"]; ?>"><?php echo $g_options["font_normal"]; ?>ID Number:<?php echo $g_options["fontend_normal"]; ?></td>
							<td width="55%" bgcolor="<?php echo $g_options["table_bgcolor1"]; ?>"><input type="text" name="id" size=15 maxlength=12 class="textbox"></td>
						</tr>
						
						</table></td>
					<td align="right">
						<table border=0 cellspacing=0 cellpadding=10>
						<tr>
							<td><input type="submit" value=" Edit &gt;&gt; " class="submit"></td>
						</tr>
						</table></td>
				</tr>
				
				</table></td>
		</tr>
		
		</table>
		
		</form><?php echo $g_options["fontend_normal"]; ?></td>
</tr>

</table><p>

<?php
	require(INCLUDE_PATH . "/search-class.inc");
	
	$sr_query = $HTTP_GET_VARS["q"];
	$sr_type  = $HTTP_GET_VARS["st"]
		or "player";
	$sr_game  = $HTTP_GET_VARS["game"];
	
	$search = new Search($sr_query, $sr_type, $sr_game);
	
	$search->drawForm(array(
		"mode"=>"admin",
		"task"=>$selTask
	));
	
	if ($sr_query)
	{
		$search->drawResults(
			"mode=admin&task=tools_editdetails_player&id=%k",
			"mode=admin&task=tools_editdetails_clan&id=%k"
		);
	}
?>
