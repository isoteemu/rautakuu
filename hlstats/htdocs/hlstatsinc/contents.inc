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


    // Contents

    $resultGames = $db->query("
        SELECT
            code,
            hlstats_Games.name
        FROM
            hlstats_Games
        INNER JOIN `hlstats_Servers` ON
            hlstats_Servers.game=hlstats_Games.code
        WHERE
            hlstats_Games.hidden='0' AND
            hlstats_Servers.game IS NOT NULL
        GROUP BY hlstats_Games.code
        ORDER BY
            name ASC

    ");

    $num_games = $db->num_rows($resultGames);

    if ($num_games == 1 || $game = $HTTP_GET_VARS["game"])
    {
        if ($num_games == 1)
            list($game) = $db->fetch_row($resultGames);

        include(INCLUDE_PATH . "/game.inc");
    }
    else
    {
        pageHeader(array(_("Contents")), array(_("Contents")=>""));
?>

<table width="90%" align="center" border=0 cellspacing=0 cellpadding=0>

<tr>
    <td><?php echo $g_options["font_normal"]; ?>&nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/downarrow.gif" width=9 height=6 border=0 align="middle" alt="downarrow.gif"><b>&nbsp;Games</b><?php echo $g_options["fontend_normal"];?><p>

        <table width="80%" align="center" border=0 cellspacing=0 cellpadding=0 bgcolor="<?php echo $g_options["table_border"]; ?>">

        <tr>
            <td><table width="100%" border=0 cellspacing=1 cellpadding=4>

                <tr valign="bottom" bgcolor="<?php echo $g_options["table_head_bgcolor"]; ?>">
                    <td width="60%" align="left"><?php echo $g_options["font_small"]; ?><font color="<?php echo $g_options["table_head_text"]; ?>">&nbsp;Game</font><?php echo $g_options["fontend_small"]; ?></td>
                    <td width="20%" align="center"><?php echo $g_options["font_small"]; ?><font color="<?php echo $g_options["table_head_text"]; ?>">&nbsp;Top Player</font><?php echo $g_options["fontend_small"]; ?></td>
                    <td width="20%" align="center"><?php echo $g_options["font_small"]; ?><font color="<?php echo $g_options["table_head_text"]; ?>">&nbsp;Top Clan</font><?php echo $g_options["fontend_small"]; ?></td>
                </tr>

<?php
                while ($gamedata = $db->fetch_row($resultGames))
                {
                    $result = $db->query("
                        SELECT
                            playerId,
                            lastName
                        FROM
                            hlstats_Players
                        WHERE
                            game='$gamedata[0]'
                            AND hideranking=0
                        ORDER BY
                            skill DESC
                        LIMIT 1
                    ");

                    if ($db->num_rows($result) == 1)
                    {
                        $topplayer = $db->fetch_row($result);
                    }
                    else
                    {
                        $topplayer = false;
                    }

                    $result = $db->query("
                        SELECT
                            hlstats_Clans.clanId,
                            hlstats_Clans.name,
                            AVG(hlstats_Players.skill) AS skill,
                            COUNT(hlstats_Players.playerId) AS numplayers
                        FROM
                            hlstats_Clans
                        LEFT JOIN hlstats_Players ON
                            hlstats_Players.clan = hlstats_Clans.clanId
                        WHERE
                            hlstats_Clans.game='$gamedata[0]'
                        GROUP BY
                            hlstats_Clans.clanId
                        HAVING
                            skill IS NOT NULL
                            AND numplayers > 1
                        ORDER BY
                            skill DESC
                        LIMIT 1
                    ");

                    if ($db->num_rows($result) == 1)
                    {
                        $topclan = $db->fetch_row($result);
                    }
                    else
                    {
                        $topclan = false;
                    }
        ?>

                <tr valign="middle">
                    <td bgcolor="<?php echo $g_options["table_bgcolor1"]; ?>">
                        <table width="100%" border=0 cellspacing=0 cellpadding=0>

                        <tr valign="middle">
                            <td width="100%"><?php echo $g_options["font_normal"]; ?><b>&nbsp;<a href="<?php echo $g_options["scripturl"] . "?game=$gamedata[0]"; ?>"><img src="<?php
    $imgfile = $g_options["imgdir"] . "/game-$gamedata[0].gif";
    if (file_exists($imgfile))
    {
        echo $imgfile;
    }
    else
    {
        echo $g_options["imgdir"] . "/game.gif";
    }
?>" hspace=3 border=0 align="middle" alt="Game"><?php echo $gamedata[1]; ?></a></b><?php echo $g_options["fontend_normal"]; ?></td>
                            <td><?php echo $g_options["font_small"]; ?>&nbsp;<a href="<?php echo $g_options["scripturl"] . "?mode=players&amp;game=$gamedata[0]"; ?>"><img src="<?php echo $g_options["imgdir"]; ?>/player.gif" hspace=3 alt="Player Rankings" border=0 align="middle">Players</a>&nbsp;&nbsp;<?php echo $g_options["fontend_small"]; ?></td>
                            <td><?php echo $g_options["font_small"]; ?>&nbsp;<a href="<?php echo $g_options["scripturl"] . "?mode=clans&amp;game=$gamedata[0]"; ?>"><img src="<?php echo $g_options["imgdir"]; ?>/clan.gif" width=16 height=16 hspace=3 alt="Clan Rankings" border=0 align="middle">Clans</a>&nbsp;&nbsp;<?php echo $g_options["fontend_small"]; ?></td>
                        </tr>

                        </table></td>
                    <td align="center" bgcolor="<?php echo $g_options["table_bgcolor2"]; ?>"><?php
                        echo $g_options["font_normal"];
                        if ($topplayer)
                        {
                            echo "<a href=\"" . $g_options["scripturl"] . "?mode=playerinfo&amp;player="
                                . $topplayer[0] . "\">$topplayer[1]</a>";
                        }
                        else
                        {
                            echo "-";
                        }
                        echo $g_options["fontend_normal"];
                    ?></td>
                    <td align="center" bgcolor="<?php echo $g_options["table_bgcolor1"]; ?>"><?php
                        echo $g_options["font_normal"];
                        if ($topclan)
                        {
                            echo "<a href=\"" . $g_options["scripturl"] . "?mode=claninfo&amp;clan="
                                . $topclan[0] . "\">$topclan[1]</a>";
                        }
                        else
                        {
                            echo "-";
                        }
                        echo $g_options["fontend_normal"];
                    ?></td>
                </tr>
<?php
                }
?>
                </table></td>
        </tr>

        </table><p>
        <br>


        <?php echo $g_options["font_normal"]; ?>&nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/downarrow.gif" width=9 height=6 border=0 align="middle" alt="downarrow.gif"><b>&nbsp;General Statistics</b><?php echo $g_options["fontend_normal"];?><p>

        <?php
            $result = $db->query("SELECT COUNT(*) FROM hlstats_Players");
            list($num_players) = $db->fetch_row($result);
            $num_players = intval($num_players);

            $result = $db->query("SELECT COUNT(*) FROM hlstats_Clans");
            list($num_clans) = $db->fetch_row($result);
            $num_clans = intval($num_clans);

            $result = $db->query("SELECT COUNT(*) FROM hlstats_Servers");
            list($num_servers) = $db->fetch_row($result);
            $num_servers = intval($num_servers);

            $result = $db->query("
                SELECT
                    DATE_FORMAT(MAX(eventTime), '%d.%m.%Y, %H:%i:%s')
                FROM
                    hlstats_Events_Frags
            ");
            list($lastevent) = $db->fetch_row($result);
?>

        <table width="80%" align="center" border=0 cellspacing=0 cellpadding=3>

        <tr valign="top">
            <td width=10><?php echo $g_options["font_normal"]; ?><b>&#149;&nbsp;</b><?php echo $g_options["fontend_normal"]; ?></td>
            <td width="100%"><?php
                echo $g_options["font_normal"];

                echo "<b>$num_players</b> players and <b>$num_clans</b> clans "
                    . "ranked in <b>$num_games</b> games on <b>$num_servers</b>"
                    . " servers.";

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

                echo "All statistics are generated in real-time. Event history data expires after <b>" . DELETEDAYS . "</b> days.";

                echo $g_options["fontend_normal"];
            ?></td>
        </tr>

        </table></td>
</tr>

</table>

<?php
    }
?>
