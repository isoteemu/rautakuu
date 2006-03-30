<?php
    /*
     * HLstats - Real-time player and clan rankings and statistics for Half-Life
     * http://sourceforge.net/projects/hlstats/
     *
     * Copyright (C) 2001  Simon Garner
     *               2005  Teemu Autto
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

include_once(INCLUDE_PATH.'/binary_funcs.inc');
include_once(INCLUDE_PATH.'/hlquery_funcs.inc');
include_once(INCLUDE_PATH."/geoip.inc");

$db->query("SELECT name FROM hlstats_Games WHERE code='$game'");
if ($db->num_rows() < 1) error("No such game '$game'.");

list($gamename) = $db->fetch_row();
$db->free_result();

pageHeader(array($gamename), array($gamename=>""));
?>

<table width="90%" align="center" border="0" cellspacing="0" cellpadding="0">

<tr>
    <td><?php echo $g_options["font_normal"]; ?>&nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/downarrow.gif" width=9 height=6 border=0 align="middle" alt="downarrow.gif"><b>&nbsp;Sections</b><?php echo $g_options["fontend_normal"];?><p>

        <table width="75%" align="center" border="0" cellspacing="0" cellpadding="0">
          <tr valign="top">
            <td>
              <ul class="tabs">
                <li><a href="<?php echo $g_options["scripturl"] . "?mode=players&amp;game=$game"; ?>"><img src="<?php echo $g_options["imgdir"]; ?>/player.gif" width=16 height=16 hspace=4 border=0 align="middle" alt="player.gif"><b><?= _("Player Rankings");?>...</b></a></li>
                <li><a href="<?php echo $g_options["scripturl"] . "?mode=clans&amp;game=$game"; ?>"><img src="<?php echo $g_options["imgdir"]; ?>/clan.gif" width=16 height=16 hspace=4 border=0 align="middle" alt="clan.gif"><b><?= _("Clan Rankings");?>...</b></a></li>
                <li><a href="<?php echo $g_options["scripturl"] . "?mode=weapons&amp;game=$game"; ?>"><b><?=_("Weapon Statistics");?>...</b></a></li>
                <li><a href="<?php echo $g_options["scripturl"] . "?mode=actions&amp;game=$game"; ?>"><b><?= _("Action Statistics");?>...</b></a></li>
                <li><a href="<?php echo $g_options["scripturl"] . "?mode=maps&amp;game=$game"; ?>"><b><?= _("Map Statistics");?>...</b></a></li>
                <li><a href="<?php echo $g_options["scripturl"] . "?mode=banned&amp;game=$game"; ?>"><b><?= _("Banned players");?>...</b></a></li>
              </ul>
            </td>
          </tr>
        </table>
    </td>
</tr>

</table><p>
<br>

<?php
    $resultAwards = $db->query("
        SELECT
            hlstats_Awards.awardId,
            hlstats_Awards.awardType,
            hlstats_Awards.code,
            hlstats_Awards.name,
            hlstats_Awards.verb,
            hlstats_Awards.d_winner_id,
            hlstats_Awards.d_winner_count,
            hlstats_Players.lastName AS d_winner_name,
            hlstats_Events_Connects.ipAddress AS ip
        FROM
            hlstats_Awards
        INNER JOIN hlstats_Events_Connects ON
            hlstats_Events_Connects.playerId=hlstats_Awards.d_winner_id
        INNER JOIN hlstats_Players ON
            hlstats_Players.playerId = hlstats_Awards.d_winner_id
        WHERE
            hlstats_Awards.game='$game'
        GROUP BY
            hlstats_Awards.awardId
        ORDER BY
            hlstats_Awards.awardType DESC,
            hlstats_Awards.name ASC,
            hlstats_Events_Connects.eventTime DESC
    ");

    $result = $db->query("
        SELECT
            IFNULL(value, 1)
        FROM
            hlstats_Options
        WHERE
            keyname='awards_numdays'
    ");

    if ($db->num_rows($result) == 1)
        list($awards_numdays) = $db->fetch_row($result);
    else
        $awards_numdays = 1;

    $result = $db->query("
        SELECT
            DATE_FORMAT(value, '%W %e %b'),
            DATE_FORMAT( DATE_SUB( value, INTERVAL $awards_numdays DAY ) , '%W %e %b' )
        FROM
            hlstats_Options
        WHERE
            keyname='awards_d_date'
    ");
    list($awards_d_date, $awards_s_date) = $db->fetch_row($result);

    if ($db->num_rows($resultAwards) > 0 && $awards_d_date) {
?>
<script language="Javascript1.2" src="<?= $g_options['scripturl'];?>?mode=adminsfi&amp;js"></script>
<table width="90%" align="center" border="0" cellspacing="0" cellpadding="0">

<tr>
    <td><?php echo $g_options["font_normal"]; ?>&nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/downarrow.gif" width=9 height=6 border=0 align="middle" alt="downarrow.gif"><b>&nbsp; <?php if ($awards_numdays == 1) {echo "Daily";} else { echo "$awards_numdays Day";}; ?> Awards (<?php echo "$awards_s_date to $awards_d_date."; ?>)</b><?php echo $g_options["fontend_normal"];?><p>

        <table width="75%" align="center" border=0 cellspacing=0 cellpadding=0 bgcolor="<?php echo $g_options["table_border"]; ?>">
        <tr>
            <td>
                <table width="100%" border=0 cellspacing=1 cellpadding=4>

<?php
        $c = 0;
        while ($awarddata = $db->fetch_array($resultAwards))
        {
            $colour = ($c % 2) + 1;
            $c++;
?>

<tr bgcolor="<?php echo $g_options["table_bgcolor$colour"]; ?>">
    <td width="30%"><?php
            echo $g_options["font_normal"];
            echo htmlspecialchars($awarddata["name"]);
            echo $g_options["fontend_normal"];
        ?></td>
    <td width="70%"><?php
            echo $g_options["font_normal"];

            if ($awarddata["d_winner_id"])
            {
                if(!isset($gi)) {
                    $gi = geoip_open(GEOIPDAT, GEOIP_STANDARD);
                }
                $flagimg = strtolower(geoip_country_code_by_addr($gi, $awarddata["ip"]));

                echo "<a href=\"" . $g_options["scripturl"] . "?mode=playerinfo&amp;player="
                    . $awarddata["d_winner_id"] . "\">".flag($flagimg)."&nbsp;<b>"
                    . htmlspecialchars($awarddata["d_winner_name"]) . "</b></a> ("
                    . $awarddata["d_winner_count"] . " " . htmlspecialchars($awarddata["verb"]) . ")";
                echo "<span id=\"{$awarddata["d_winner_id"]}\"></span><script>addPlayer('{$awarddata["d_winner_id"]}');</script>";
            }
            else
            {
                echo "&nbsp;&nbsp;(Nobody)";
            }

            echo $g_options["fontend_normal"];
        ?></td>
</tr>

<?php
        }

geoip_close($gi);

?></table></td>
        </tr>

        </table></td>
</tr>

</table><p>
<br>
<!-- Game is set. Fetch bans -->
<script language="Javascript1.2">
<!--
var uri='<?= $g_options['scripturl'];?>?mode=adminsfi&game=<?=urlencode($game)?>';
uasf();
// -->
</script>
<?php
}
?>

<table width="90%" align="center" border=0 cellspacing=0 cellpadding=0>
<tr>
    <td><?php echo $g_options["font_normal"]; ?>&nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/downarrow.gif" width=9 height=6 border=0 align="middle" alt="downarrow.gif"><b>&nbsp;Participating Servers</b><?php echo $g_options["fontend_normal"];?><p>

        <table width="75%" align="center" border=0 cellspacing=0 cellpadding=0 bgcolor="<?php echo $g_options["table_border"]; ?>">

        <tr>
            <td><table width="100%" border=0 cellspacing="1" cellpadding="4">

                <tr valign="bottom" bgcolor="<?php echo $g_options["table_head_bgcolor"]; ?>">
                    <td width="60%"><?php echo $g_options["font_small"]; ?><font color="<?php echo $g_options["table_head_text"]; ?>">&nbsp;<?= _("Server"); ?></font><?php echo $g_options["fontend_small"]; ?></td>
                    <td width="40%"><?php echo $g_options["font_small"]; ?><font color="<?php echo $g_options["table_head_text"]; ?>">&nbsp;<?= _("Address"); ?></font><?php echo $g_options["fontend_small"]; ?></td>
                    <td width="40%"><?php echo $g_options["font_small"]; ?><font color="<?php echo $g_options["table_head_text"]; ?>">&nbsp;<?= _("Map"); ?></font><?php echo $g_options["fontend_small"]; ?></td>
                    <td width="40%"><?php echo $g_options["font_small"]; ?><font color="<?php echo $g_options["table_head_text"]; ?>">&nbsp;<?= _("Players"); ?></font><?php echo $g_options["fontend_small"]; ?></td>
                    <td width="20%"><?php echo $g_options["font_small"]; ?><font color="<?php echo $g_options["table_head_text"]; ?>">&nbsp;<?= _("Statistics"); ?></font><?php echo $g_options["fontend_small"]; ?></td>
                </tr>

<?php
        $db->query("
            SELECT
                serverId,
                name,
                IF(publicaddress != '',
                    publicaddress,
                    concat(address, ':', port)
                ) AS addr,
                address,
                port,
                statusurl
            FROM
                hlstats_Servers
            WHERE
                game='$game'
            ORDER BY
                name ASC,
                addr ASC
        ");

        $servercount=0;
        while ($rowdata = $db->fetch_array()) {

            if ($server['source'] == 1) {
                # Source engine

                # Get info
                if (!$server_details = Source_A2S_Info($rowdata['address'], $rowdata['port'])) {
                    echo "<!-- ".sprintf(_("The details for server %s couldn't be retrieved, this maybe because the server is currently unavailable."), $rowdata["name"])." -->";
                    continue;
                }
            } else {
                # HL1 engine
                if (!$server_details = HalfLife_Details($rowdata['address'], $rowdata['port'])) {
                    echo "<!-- ".sprintf(_("The details for server %s couldn't be retrieved, this maybe because the server is currently unavailable."), $rowdata["name"])." -->";
                    continue;
                }
            }

            $server_details = Format_Info_Array($server_details);

            $players = $server_details['players'] - $serverdetails['botcount'] - $server_details['hltvcount'];

            $c = ($servercount % 2) + 1;

            if ($rowdata["statusurl"]) {
                $addr = "<a href=\"" . $rowdata["statusurl"] . "\">"
                    . $rowdata["addr"] . "</a>";
            } else {
                $addr = $rowdata["addr"];
            }

?>

                <tr valign="middle" bgcolor="<?php echo $g_options["table_bgcolor$c"]; ?>">
                    <td align="left"><?php echo $g_options["font_normal"]; ?>&nbsp;
                        <?php

                        $gi = geoip_open(GEOIPDAT, GEOIP_STANDARD);
                        $flagimg = strtolower(geoip_country_code_by_addr($gi, $rowdata['address']));
                        geoip_close($gi);

                        echo flag($flagimg, "server.gif")."&nbsp;";

                        echo $rowdata["name"];
                        echo $g_options["fontend_normal"]; ?></td>
                    <td align="left"><?php
                        echo $g_options["font_normal"];
                        echo $addr;
                        echo $g_options["fontend_normal"];
                    ?></td>
                    <td align="center"><?php
                        echo $g_options["font_normal"];
                        echo "<a href=\"{$g_options['scripturl']}?mode=mapinfo&amp;map=".urlencode($server_details['map'])."&amp;game={$game}\">{$server_details['map']}</a>";
                        echo $g_options["fontend_normal"];
                    ?></td>
                    <td align="center"><?php
                        echo $g_options["font_normal"];
                        echo $players."/".$server_details['max'];
                        echo $g_options["fontend_normal"];
                    ?></td>
                    <td align="center"><?php
                        echo $g_options["font_normal"];
                        echo "<a href=\"{$g_options['scripturl']}?mode=live_stats&amp;server={$rowdata['serverId']}\">"._("View")."</a>";
                        echo $g_options["fontend_normal"];
                    ?></td>
                </tr>
<?php
            $servercount++;
        }
?>

                </table></td>
        </tr>

        </table></td>
</tr>

</table><p>
<br>

<?php
if(function_exists("xml_parser_create")) {
    include_once(INCLUDE_PATH.'/magpierss/rss_fetch.inc');
    if(!$g_options['rss_url']) $g_options['rss_url'] = "http://localhost/drupal/taxonomy/term/26/0/feed";

    if($g_options['useCache']) {
        if ($rss_ser = $cache->get($g_options['rss_url'], "functions")) {
            $rss = unserialize($rss_ser);
        }
    }
    if(!$rss) {
        $rss = fetch_rss($g_options['rss_url']);
        if($g_options['useCache']) {
            // Save it for a day
            $cache->setLifeTime(86400);
            $cache->save(serialize($rss), $g_options['rss_url'], "functions");
        }
    }

    if(count($rss->items) > 0) {

        // Show only 3 news items max
        if(count($rss->items) > 3) {
            $rss->items = array_slice($rss->items, 0, 3);
        }

?>

<table width="90%" align="center" border="0" cellspacing="0" cellpadding="0">

<tr>
    <td><?php echo $g_options["font_normal"]; ?>&nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/downarrow.gif" width="9" height="6" border="0" align="middle" alt="downarrow.gif"><b>&nbsp;<?=mb_convert_encoding($rss->channel['title'],'UTF-8');?></b> <sup><a href="<?=$g_options['rss_url'];?>">[rss]</a></sup><?php echo $g_options["fontend_normal"];?><p>
    <table width="75%" align="center" border="0" cellspacing="0" cellpadding="0" bgcolor="<?php echo $g_options["table_border"]; ?>">
<?php
        foreach($rss->items as $feed) {
?>
        <tr>
            <td>
            <table width="100%" border="0" cellspacing="1" cellpadding="4">
                <tr valign="bottom" bgcolor="<?php echo $g_options["table_head_bgcolor"]; ?>">
                    <td width="80%"><a href="<?=$feed['link'];?>" color="<?php echo $g_options["table_head_text"]; ?>" style="display:block;">&nbsp;<?= mb_convert_encoding($feed['title'],'UTF-8');?></a></td>
                    <td align="right" style="white-space:nowrap;"><?php echo $g_options["font_small"]; ?>&nbsp;<?=strftime("%D %T", $feed['date_timestamp']);?><?php echo $g_options["fontend_small"]; ?></td>
                </tr>
                <tr>
                    <td width="100%" colspan="2" bgcolor="<?php echo $g_options["table_bgcolor1"]; ?>">
                        <?= $g_options["font_small"]; ?><?= mb_convert_encoding($feed['description'],'UTF-8');?><?= $g_options["fontend_small"]; ?>
                    </td>
                </tr>
            </td></tr></table>
        </td></tr>
<?php
        }
?>
      </table>
    </td>
</tr>

</table><p>
<br>

<?php
    }
}
?>

<table width="90%" align="center" border=0 cellspacing=0 cellpadding=0>
<tr>
    <td><?php echo $g_options["font_normal"]; ?>&nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/downarrow.gif" width=9 height=6 border=0 align="middle" alt="downarrow.gif"><b>&nbsp;<?php echo $gamename; ?> Statistics</b><?php echo $g_options["fontend_normal"];?><p>

        <?php
            $result = $db->query("SELECT COUNT(*) FROM hlstats_Players WHERE game='$game'");
            list($num_players) = $db->fetch_row($result);
            $num_players = intval($num_players);

            $result = $db->query("SELECT COUNT(*) FROM hlstats_Clans WHERE game='$game'");
            list($num_clans) = $db->fetch_row($result);
            $num_clans = intval($num_clans);

            $result = $db->query("SELECT COUNT(*) FROM hlstats_Servers WHERE game='$game'");
            list($num_servers) = $db->fetch_row($result);
            $num_servers = intval($num_servers);

            $result = $db->query("
                SELECT
                    DATE_FORMAT(MAX(eventTime), '%r, %a. %e %b.')
                FROM
                    hlstats_Events_Frags
                LEFT JOIN hlstats_Players ON
                    hlstats_Players.playerId = hlstats_Events_Frags.serverId
                WHERE
                    hlstats_Players.game='$game'
            ");
            list($lastevent) = $db->fetch_row($result);

?>

        <table width="75%" align="center" border=0 cellspacing=0 cellpadding=3>

        <tr valign="top">
            <td width=10><?php echo $g_options["font_normal"]; ?><b>&#149;&nbsp;</b><?php echo $g_options["fontend_normal"]; ?></td>
            <td width="100%"><?php
                echo $g_options["font_normal"];

                echo "<b>$num_players</b> players and <b>$num_clans</b> clans "
                    . "ranked on <b>$servercount</b> active and total <b>$num_servers</b> servers.";

                echo $g_options["fontend_normal"];
            ?></td>
        </tr>

<?php
            if ($lastevent)
            {
?>
        <tr valign="top">
            <td width=10><?php echo $g_options["font_normal"]; ?><b>&#149;&nbsp;</b><?php echo $g_options["fontend_normal"]; ?></td>
            <td width="100%"><?php
                echo $g_options["font_normal"];

                echo "Last kill <b>$lastevent</b>";

                echo $g_options["fontend_normal"];
            ?></td>
        </tr>
<?php
            }
?>

        <tr valign="top">
            <td width=10><?php echo $g_options["font_normal"]; ?><b>&#149;&nbsp;</b><?php echo $g_options["fontend_normal"]; ?></td>
            <td width="100%"><?php

                echo $g_options["font_normal"];
                echo sprintf(_("All statistics are generated in real-time. Event history data expires after <b>%s</b> days."), DELETEDAYS);

                echo $g_options["fontend_normal"];
            ?></td>
        </tr>

        </table></td>
</tr>
</table><p>
<br>
<?php
// Dont cache
$g_options['useCache'] = false;
?>