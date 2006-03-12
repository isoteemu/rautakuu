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

    // Player History

    $player = intval($HTTP_GET_VARS["player"])
        or error("No player ID specified.");

    $db->query("
        SELECT
            hlstats_Players.lastName,
            hlstats_Players.game
        FROM
            hlstats_Players
        WHERE
            playerId=$player
    ");
    if ($db->num_rows() != 1)
        error("No such player '$player'.");

    $playerdata = $db->fetch_array();

    $pl_name = $playerdata["lastName"];
    if (strlen($pl_name) > 10)
    {
        $pl_shortname = substr($pl_name, 0, 8) . "...";
    }
    else
    {
        $pl_shortname = $pl_name;
    }
    $pl_name = ereg_replace(" ", "&nbsp;", htmlspecialchars($pl_name));
    $pl_shortname = ereg_replace(" ", "&nbsp;", htmlspecialchars($pl_shortname));


    $game = $playerdata["game"];
    $db->query("SELECT name FROM hlstats_Games WHERE code='$game'");
    if ($db->num_rows() != 1)
        $gamename = ucfirst($game);
    else
        list($gamename) = $db->fetch_row();

    pageHeader(
        array($gamename, _("Event History"), $pl_name),
        array(
            $gamename=>$g_options["scripturl"] . "?game=$game",
            _("Player Rankings")=>$g_options["scripturl"] . "?mode=players&amp;game=$game",
            _("Player Details")=>$g_options["scripturl"] . "?mode=playerinfo&amp;player=$player",
            _("Event History")=>""
        )
    );

    $table = new Table(
        array(
            new TableColumn(
                "eventTime",
                _("Date"),
                "width=20"
            ),
            new TableColumn(
                "eventType",
                _("Type"),
                "width=10&align=center"
            ),
            new TableColumn(
                "eventDesc",
                _("Description"),
                "width=40&sort=no&append=.&embedlink=yes"
            ),
            new TableColumn(
                "serverName",
                _("Server"),
                "width=20"
            ),
            new TableColumn(
                "map",
                _("Map"),
                "width=10"
            )
        ),
        "eventTime",
        "eventTime",
        "eventType",
        false,
        50,
        "page",
        "sort",
        "sortorder"
    );

    $surl = $g_options["scripturl"];


    // This would be better done with a UNION query, I think, but MySQL doesn't
    // support them yet. (NOTE you need MySQL 3.23 for temporary table support.)

    $db->query("DROP TABLE IF EXISTS hlstats_EventHistory");
    $db->query("
        CREATE TEMPORARY TABLE hlstats_EventHistory
        (
            eventType VARCHAR(32) NOT NULL,
            eventTime DATETIME NOT NULL,
            eventDesc VARCHAR(255) NOT NULL,
            serverName VARCHAR(32) NOT NULL,
            map VARCHAR(32) NOT NULL
        )
    ");

    function insertEvents ($table, $select)
    {
        global $db;

        $select = str_replace("<table>", "hlstats_Events_$table", $select);
        $db->query("
            INSERT INTO
                hlstats_EventHistory
                (
                    eventType,
                    eventTime,
                    eventDesc,
                    serverName,
                    map
                )
            $select
        ");
    }

    insertEvents("TeamBonuses", "
        SELECT
            'Team Bonus',
            <table>.eventTime,
            CONCAT('My team received a points bonus of ', bonus, ' for triggering \"', hlstats_Actions.description, '\"'),
            hlstats_Servers.name,
            <table>.map
        FROM
            <table>
        LEFT JOIN hlstats_Actions ON
            <table>.actionId = hlstats_Actions.id
        LEFT JOIN hlstats_Servers ON
            hlstats_Servers.serverId = <table>.serverId
        WHERE
            <table>.playerId=$player
    ");

    if (MODE == "LAN")
        $uqIdStr = "IP Address:";
    else
        $uqIdStr = "Unique ID:";

    insertEvents("Connects", "
        SELECT
            'Connect',
            <table>.eventTime,
            CONCAT('I connected to the server'),
            hlstats_Servers.name,
            <table>.map
        FROM
            <table>
        LEFT JOIN hlstats_Servers ON
            hlstats_Servers.serverId = <table>.serverId
        WHERE
            <table>.playerId=$player
    ");

    insertEvents("Disconnects", "
        SELECT
            'Disconnect',
            <table>.eventTime,
            'I left the game',
            hlstats_Servers.name,
            <table>.map
        FROM
            <table>
        LEFT JOIN hlstats_Servers ON
            hlstats_Servers.serverId = <table>.serverId
        WHERE
            <table>.playerId=$player
    ");

    insertEvents("Entries", "
        SELECT
            'Entry',
            <table>.eventTime,
            'I entered the game',
            hlstats_Servers.name,
            <table>.map
        FROM
            <table>
        LEFT JOIN hlstats_Servers ON
            hlstats_Servers.serverId = <table>.serverId
        WHERE
            <table>.playerId=$player
    ");

    insertEvents("Frags", "
        SELECT
            'Kill',
            <table>.eventTime,
            CONCAT('I killed %A%$surl?mode=playerinfo&player=', victimId, '%', hlstats_Players.lastName, '%/A%', ' with ', weapon),
            hlstats_Servers.name,
            <table>.map
        FROM
            <table>
        LEFT JOIN hlstats_Servers ON
            hlstats_Servers.serverId = <table>.serverId
        LEFT JOIN hlstats_Players ON
            hlstats_Players.playerId = <table>.victimId
        WHERE
            <table>.killerId=$player
    ");

    insertEvents("Frags", "
        SELECT
            'Death',
            <table>.eventTime,
            CONCAT('%A%$surl?mode=playerinfo&player=', killerId, '%', hlstats_Players.lastName, '%/A%', ' killed me with ', weapon),
            hlstats_Servers.name,
            <table>.map
        FROM
            <table>
        LEFT JOIN hlstats_Servers ON
            hlstats_Servers.serverId = <table>.serverId
        LEFT JOIN hlstats_Players On
            hlstats_Players.playerId = <table>.killerId
        WHERE
            <table>.victimId=$player
    ");

    insertEvents("Teamkills", "
        SELECT
            'Team Kill',
            <table>.eventTime,
            CONCAT('I killed teammate %A%$surl?mode=playerinfo&player=', victimId, '%', hlstats_Players.lastName, '%/A%', ' with ', weapon),
            hlstats_Servers.name,
            <table>.map
        FROM
            <table>
        LEFT JOIN hlstats_Servers ON
            hlstats_Servers.serverId = <table>.serverId
        LEFT JOIN hlstats_Players On
            hlstats_Players.playerId = <table>.victimId
        WHERE
            <table>.killerId=$player
    ");

    insertEvents("Teamkills", "
        SELECT
            'Friendly Fire',
            <table>.eventTime,
            CONCAT('My teammate %A%$surl?mode=playerinfo&player=', killerId, '%', hlstats_Players.lastName, '%/A%', ' killed me with ', weapon),
            hlstats_Servers.name,
            <table>.map
        FROM
            <table>
        LEFT JOIN hlstats_Servers ON
            hlstats_Servers.serverId = <table>.serverId
        LEFT JOIN hlstats_Players On
            hlstats_Players.playerId = <table>.killerId
        WHERE
            <table>.victimId=$player
    ");

    insertEvents("ChangeRole", "
        SELECT
            'Role',
            <table>.eventTime,
            CONCAT('I changed role to ', role),
            hlstats_Servers.name,
            <table>.map
        FROM
            <table>
        LEFT JOIN hlstats_Servers ON
            hlstats_Servers.serverId = <table>.serverId
        WHERE
            <table>.playerId=$player
    ");

    insertEvents("ChangeName", "
        SELECT
            'Name',
            <table>.eventTime,
            CONCAT('I changed my name from \"', oldName, '\" to \"', newName, '\"'),
            hlstats_Servers.name,
            <table>.map
        FROM
            <table>
        LEFT JOIN hlstats_Servers ON
            hlstats_Servers.serverId = <table>.serverId
        WHERE
            <table>.playerId=$player
    ");

    insertEvents("PlayerActions", "
        SELECT
            'Action',
            <table>.eventTime,
            CONCAT('I received a points bonus of ', bonus, ' for triggering \"', hlstats_Actions.description, '\"'),
            hlstats_Servers.name,
            <table>.map
        FROM
            <table>
        LEFT JOIN hlstats_Servers ON
            hlstats_Servers.serverId = <table>.serverId
        LEFT JOIN hlstats_Actions ON
            hlstats_Actions.id = <table>.actionId
        WHERE
            <table>.playerId=$player
    ");

    insertEvents("PlayerPlayerActions", "
        SELECT
            'Action',
            <table>.eventTime,
            CONCAT('I received a points bonus of ', bonus, ' for triggering \"', hlstats_Actions.description, '\" against %A%$surl?mode=playerinfo&player=', victimId, '%', hlstats_Players.lastName, '%/A%'),
            hlstats_Servers.name,
            <table>.map
        FROM
            <table>
        LEFT JOIN hlstats_Servers ON
            hlstats_Servers.serverId = <table>.serverId
        LEFT JOIN hlstats_Actions ON
            hlstats_Actions.id = <table>.actionId
        LEFT JOIN hlstats_Players ON
            hlstats_Players.playerId = <table>.victimId
        WHERE
            <table>.playerId=$player
    ");

    insertEvents("PlayerPlayerActions", "
        SELECT
            'Action',
            <table>.eventTime,
            CONCAT('%A%$surl?mode=playerinfo&player=', <table>.playerId, '%', hlstats_Players.lastName, '%/A% triggered \"', hlstats_Actions.description, '\" against me'),
            hlstats_Servers.name,
            <table>.map
        FROM
            <table>
        LEFT JOIN hlstats_Servers ON
            hlstats_Servers.serverId = <table>.serverId
        LEFT JOIN hlstats_Actions ON
            hlstats_Actions.id = <table>.actionId
        LEFT JOIN hlstats_Players ON
            hlstats_Players.playerId = <table>.playerId
        WHERE
            <table>.victimId=$player
    ");

    insertEvents("Suicides", "
        SELECT
            'Suicide',
            <table>.eventTime,
            CONCAT('I committed suicide with \"', weapon, '\"'),
            hlstats_Servers.name,
            <table>.map
        FROM
            <table>
        LEFT JOIN hlstats_Servers ON
            hlstats_Servers.serverId = <table>.serverId
        WHERE
            <table>.playerId=$player
    ");

    insertEvents("Chat", "
        SELECT
            'Say',
            <table>.eventTime,
            CONCAT('I said \"', message, '\"'),
            hlstats_Servers.name,
            <table>.map
        FROM
            <table>
        LEFT JOIN hlstats_Servers ON
            hlstats_Servers.serverId = <table>.serverId
        WHERE
            <table>.playerId=$player
    ");

    insertEvents("ChangeTeam", "
        SELECT
            'Team',
            <table>.eventTime,
            IF(hlstats_Teams.name IS NULL,
                CONCAT('I joined team \"', team, '\"'),
                CONCAT('I joined team \"', team, '\" (', hlstats_Teams.name, ')')
            ),
            hlstats_Servers.name,
            <table>.map
        FROM
            <table>
        LEFT JOIN hlstats_Servers ON
            hlstats_Servers.serverId = <table>.serverId
        LEFT JOIN hlstats_Teams ON
            hlstats_Teams.code = <table>.team
        WHERE
            <table>.playerId=$player
    ");


    $result = $db->query("
        SELECT
            eventTime,
            eventType,
            eventDesc,
            serverName,
            map
        FROM
            hlstats_EventHistory
        ORDER BY
            $table->sort $table->sortorder,
            $table->sort2 $table->sortorder
        LIMIT
            $table->startitem,$table->numperpage
    ");

    $resultCount = $db->query("
        SELECT
            COUNT(*)
        FROM
            hlstats_EventHistory
    ");

    list($numitems) = $db->fetch_row($resultCount);
?>
<table width="90%" align="center" border=0 cellspacing=0 cellpadding=0>
    <tr>
        <td width="100%">
            <?php echo $g_options["font_normal"];
            ?>
             &nbsp;<img src="<?php echo $g_options["imgdir"]; ?>/downarrow.gif" width=9 height=6 border=0 align="middle" alt="downarrow.gif"><b>&nbsp;Player Event History</b> (Last<?php echo DELETEDAYS;
            ?>
             Days)<?php echo $g_options["fontend_normal"];
            ?>
            <p>
                <?php
        $table->draw($result, $numitems, 100);
                ?>
        </td>
    </tr>
</table>
