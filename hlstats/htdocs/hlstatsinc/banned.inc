<?php
/*
 * HLstats - Real-time player and clan rankings and statistics for Half-Life
 * http://sourceforge.net/projects/hlstats/
 *
 * Copyright (C) 2005  Teemu Autto
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

if(!defined("_HLSTATS")) die("Direct access denied.");

$db->query("SELECT name FROM hlstats_Games WHERE code='$game'");
if ($db->num_rows() < 1) error(sprintf(_("No such game %s."),$game));
list($gamename) = $db->fetch_row();
$db->free_result();

if (isset($HTTP_GET_VARS["minkills"])) {
    $minkills = intval($HTTP_GET_VARS["minkills"]);
} else {
    $minkills = 0;
}
if($HTTP_GET_VARS['export'] != "true") {
    pageHeader(
        array($gamename, _("Banned Players")),
        array($gamename=>"%s?game=$game", _("Banned Players")=>"")
    );
}

$table = new Table(
    array(
        new TableColumn(
            "player_nick",
            _("Name"),
            "width=25&flag=1&link=" . urlencode("mode=playerinfo&amp;player=%k")."&append=.".rawurlencode("<span id=\"%k\"></span><script>addPlayer('%k');</script>")
        ),
        new TableColumn(
            "ban_reason",
            _("Ban reason"),
            "width=27"
        ),
        new TableColumn(
            "ban_created",
            _("Ban date"),
            "width=15&type=unixtime&align=center"
        ),
        new TableColumn(
            "ban_length",
            _("Ban leght"),
            "width=7&align=right"
        ),
        new TableColumn(
            "admin_nick",
            _("Banned by"),
            "width=15"
        ),
        new TableColumn(
            "previous_bans",
            _("Prev"),
            "width=3&align=right"
        ),
        new TableColumn(
            "skill",
            _("Points"),
            "width=8&align=right"
        )
    ),
    "playerId",
    "ban_created",
    "skill",
    false
);
if($HTTP_GET_VARS['export'] != "true") {
    $limit = "
        ORDER BY
            $table->sort $table->sortorder,
            $table->sort2 $table->sortorder,
            lastName ASC
        LIMIT $table->startitem,$table->numperpage";
}

$result = $db->query("
    SELECT
        amx_bans.player_nick,
        amx_bans.player_id,
        amx_bans.player_ip AS ip,
        amx_bans.admin_nick,
        amx_bans.ban_reason,
        IF(amx_bans.ban_length=0,'"._("Permanent")."',CONCAT(amx_bans.ban_length,' "._("min")."')) AS ban_length,
        amx_bans.ban_created AS ban_created,
        amx_bans.server_name,
        COUNT(amx_banhistory.player_id) AS previous_bans,
        hlstats_Players.skill,
        hlstats_Players.playerId
    FROM
        amx_bans
    LEFT JOIN amx_banhistory ON
        amx_banhistory.player_id=amx_bans.player_id
    INNER JOIN hlstats_PlayerUniqueIds ON
        hlstats_PlayerUniqueIds.uniqueId=amx_bans.player_id
    INNER JOIN hlstats_Players ON
        hlstats_Players.playerId=hlstats_PlayerUniqueIds.playerId
    WHERE
        hlstats_Players.game = '$game' AND
        hlstats_Players.kills >= $minkills
    GROUP BY
        amx_bans.bid
    $limit
");

if($HTTP_GET_VARS['export'] == "true") {
    // Nuke the shit
    ob_end_clean();

    $banned = "";
    while ($data = $db->fetch_array($result)) {
        // Export only permanent bans
        if($data['ban_length'] != _("Permanent")) continue;
        $banned .= "banid 0.0 ".$data['player_id']."\t// ".$data['admin_nick']." @ ".$data['server_name']." ~ ".$data['player_nick']." $ ".$data['ban_reason']."\n";
    }

    header("Content-type: text/text");
    header("Content-Length: ".strlen($banned));
    Header('Content-Disposition: attachment; filename="banned.cfg"');
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Content-Description: File Transfer");
    header("Content-Transfer-Encoding: ascii");

    die($banned);
}

$numresult = $db->query("
    SELECT
        COUNT(*)
    FROM
        amx_bans
    INNER JOIN hlstats_PlayerUniqueIds ON
        hlstats_PlayerUniqueIds.uniqueId=amx_bans.player_id
    INNER JOIN hlstats_Players ON
        hlstats_Players.playerId=hlstats_PlayerUniqueIds.playerId
    WHERE
        hlstats_Players.game = '$game' AND
        hlstats_Players.kills >= '$minkills'
    GROUP BY
        amx_bans.bid
");

$numitems = $db->num_rows($numresult);

?>
<form method="GET" action="<?php echo $g_options["scripturl"]; ?>" name="f">
<input type="hidden" name="mode" value="search">
<input type="hidden" name="game" value="<?php echo $game; ?>">
<input type="hidden" name="st" value="player">

<table width="90%" align="center" border=0 cellspacing=0 cellpadding=2>

<tr valign="bottom">
    <td width="75%"><?php echo $g_options["font_normal"]; ?><b>&#149;</b><?= _("Find Player"); ?>: <input autocomplete="off" name="q" size="20" maxlength="64" class="textbox"> <input type="submit" value="Search" class="smallsubmit" name="laheta"><?php echo $g_options["fontend_normal"]; ?></td>
</tr>
</table><p>
<script>
   InstallAC(document.f,document.f.q,document.f.laheta,"search","en");
</script>
<script language="Javascript1.2" src="<?= $g_options['scripturl'];?>?mode=adminsfi&amp;js"></script>
</form>

<?php
$table->draw($result, $numitems, 95);

?>
<!-- Game is set. Fetch bans -->
<script language="Javascript1.2">
<!--
var uri='<?= $g_options['scripturl'];?>?mode=adminsfi&game=<?=urlencode($game)?>';
uasf();
// -->
</script>

<form method="GET" action="<?php echo $g_options["scripturl"]; ?>">
<table width="90%" align="center" border=0 cellspacing=0 cellpadding=2>

<tr valign="bottom">
    <td width="75%"><?php echo $g_options["font_normal"]; ?>
    <p><a href="<?= $g_options["scripturl"]; ?>?mode=<?=$mode;?>&amp;game=<?=$game;?>&amp;export=true"><?=_("Download banned.cfg")?></a></p>
    <p>&nbsp;</p>

<?php
    foreach ($HTTP_GET_VARS as $k=>$v)
    {
        if ($k != "minkills")
        {
            echo "      <input type=\"hidden\" name=\"$k\" value=\"" . htmlspecialchars($v) . "\">\n";
        }
    }
?>
        <b>&#149;</b> Only show players with <input type="text" name="minkills" size=4 maxlength=2 value="<?php echo $minkills; ?>" class="textbox"> or more kills. <input type="submit" value="Apply" class="smallsubmit"><?php echo $g_options["fontend_normal"]; ?> </td>
</tr>

</table>
</form>
