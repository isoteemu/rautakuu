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

    // Live Stats
    // The binary functions need to be included
    // Along with the HL Query functions


if(!defined("_HLSTATS")) die("Direct access denied.");

include_once(INCLUDE_PATH.'/binary_funcs.inc');
include_once(INCLUDE_PATH.'/hlquery_funcs.inc');
include_once(INCLUDE_PATH."/geoip.inc");

$server = $HTTP_GET_VARS['server'];

// Don't cache this page
$g_options['useCache'] = false;

$db->query("
        SELECT
            s.serverId,
            s.name,
            s.address,
            s.port,
            s.publicaddress,
            s.game,
            s.rcon_password,
            g.name gamename,
            g.source
        FROM
            hlstats_Servers s
        LEFT JOIN
            hlstats_Games g
        ON
            s.game=g.code
        WHERE
            serverId='$server'
            ");
if ($db->num_rows() != 1)
    error("Invalid or no server specified.");
else
    $server = $db->fetch_array();

pageHeader(
    array('Server Statistics', $server['name']),
    array($server['gamename'] => $g_options['scripturl'] . '?game=' . $server['game'],
        'Server Statistics' => $g_options['scripturl'] . '?game=' . $server['game'],
        $server['name'] => ''
    )
);

if ($server['publicaddress'])
{
    # Port maybe different
    $temp = explode(':', $server['publicaddress']);
    $server_ip = $server['address'];
    if (isset($temp[1]))
        $server_port = $temp[1];
    else
        $server_port = $server['port'];
}
else
{
    $server_ip = $server['address'];
    $server_port = $server['port'];
}
$server_rcon = $server['rcon_password'];

$server_hltv = array();
$server_players = array();

if ($server['source'] == 1)
{
    # Source engine

    # Get info
    if (!$server_details = Source_A2S_Info($server_ip, $server_port))
        error(sprintf(_("The details for server %s couldn't be retrieved, this maybe because the server is currently unavailable."), $server['name']));

    # Get challenge
    $query_challenge = Source_A2S_GetChallenge($server_ip, $server_port);

    # Get packets with challenge number
    $server_rules = Source_A2S_Rules($server_ip, $server_port, $query_challenge);
    $server_players = Source_A2S_Player($server_ip, $server_port, $query_challenge);
}
else
{
    # HL1 engine
    if (!$server_details = HalfLife_Details($server_ip, $server_port))
    error(sprintf(_("The details for server %s couldn't be retrieved, this maybe because the server is currently unavailable."), $server['name']));

    $server_rules = HalfLife_Rules($server_ip, $server_port);
    // recent old HLDS servers has been updated to use same protocol than source
    if(!$server_rules) {
        # Get challenge
        $query_challenge = Source_A2S_GetChallenge($server_ip, $server_port);

        # Get packets with challenge number
        $server_rules = Source_A2S_Rules($server_ip, $server_port, $query_challenge);
    }
}

$server_details = Format_Info_Array($server_details);
# If HLstats currently stores the rcon, might as well try to get more data from a HL status

if ($server_rcon)
{
    if ($server['source'] == 1)
    {
        $server_players_tmp = $server_players;
        $server_status = Source_Rcon($server_ip, $server_port, $server_rcon, 'status');
    }
    else
        $server_status = HalfLife_Rcon($server_ip, $server_port, $server_rcon, 'status', $server_challenge);

    if ($server_status)
    {
        # Rcon worked
        $server_players = array();
        $server_hltv = array();
        Parse_HL_Status($server_status, $server['source'], $server_players, $server_hltv);

        $player_columns[] = array('column' => 'id', 'name' => ' ID', 'align' => 'left', 'width' => '25');
        $player_columns[] = array('column' => 'name', 'name' => 'Name', 'align' => 'left', 'width' => '');
        $player_columns[] = array('column' => 'uid', 'name' => 'Unique ID', 'align' => 'left', 'width' => '125');
        $player_columns[] = array('column' => 'ping', 'name' => 'Ping', 'align' => 'right', 'width' => '35');
        $player_columns[] = array('column' => 'frags', 'name' => 'Frags', 'align' => 'right', 'width' => '30');
        $player_columns[] = array('column' => 'loss', 'name' => 'Loss', 'align' => 'right', 'width' => '30');
        $player_columns[] = array('column' => 'time', 'name' => 'Connected', 'align' => 'right', 'width' => '75');
    }
}

# If rcon failed then $server_status is FALSE and if we don't have rcon
# it won't exist
if (!$server_status)
{
    $server_players = HalfLife_Players($server_ip, $server_port);
    $player_columns[] = array('column' => 'index', 'name' => 'ID', 'align' => 'left', 'width' => '25');
    $player_columns[] = array('column' => 'name', 'name' => 'Name', 'align' => 'left', 'width' => '');
    $player_columns[] = array('column' => 'frags', 'name' => 'Frags', 'align' => 'right', 'width' => '40');
    $player_columns[] = array('column' => 'time', 'name' => 'Connected', 'align' => 'right', 'width' => '75');
}

$server_details['hltvcount'] = count($server_hltv);

$server_details['players_real'] = $server_details['players'];
$server_details['players_real'] -= $server_details['botcount'];
$server_details['players_real'] -= $server_details['hltvcount'];

$server_details['players_connecting'] = $server_details['players'];
$server_details['players_connecting'] -= $server_details['botcount'];
$server_details['players_connecting'] -= count($server_players);
$server_details['players_connecting'] -= $server_details['hltvcount'];

?>
<table width="90%" align="center" border="0" cellspacing="0" cellpadding="0" bgcolor="<?php echo $g_options['table_border']; ?>">
    <tr>
        <td>
            <table width="100%" border="0" cellspacing="1" cellpadding="2">
                <tr bgcolor="<?php echo $g_options['table_bgcolor1']; ?>">
                    <td style="padding: 0px;" width="208" height="163">
<?php
if($g_options["map_dlurl"]) {
    $map_dlurl = str_replace("%MAP%", $server_details['map'], $g_options["map_dlurl"]);
    $map_dlurl = str_replace("%GAME%", $server['game'], $map_dlurl);
    echo "<a href=\"$map_dlurl\">";
}
?>
                    <img title="<?= _("Download this map");?>" src="<?php
$image = getImage('/maps/'.strtolower($server['game']).'/'.strtolower($server_details['map']));
if ($image)
    echo $image['url'];
else
    echo $g_options['imgdir'].'/noimage.jpg';
?>" width="218" height="164" alt="<?php echo $server_details['map']; ?>">
<?php
if($g_options["map_dlurl"]) {
    echo "</a>";
}
?>
</td>
                    <td rowspan="2" valign="top" align="center" class="fontNormal">
                        <b><?php echo $server_details['hostname'];?></b><br>
                        <a href="<?php echo $g_options['scripturl']; ?>?game=<?php echo $server['game']; ?>"><?php echo $server_details['description'];?></a><br><br>
<?php
# Ok we have an array of players
# and we have an array of columns
# So lets dynamically create something
if (count($server_players) > 0)
{
    # If we have players, lets create the table
?>
                        <script language="Javascript1.2" src="<?= $g_options['scripturl'];?>?mode=adminsfi&amp;js"></script>
                        <table border="0" width="99%" cellpadding="0">
                            <tr>
                                <th width="16"></th>
<?php
    foreach ($player_columns as $column)
    {
?>
                                <th align="<?php echo $column['align']; ?>" width="<?php echo $column['width']; ?>"><?php echo $column['name']; ?></th>
<?php
    }
?>
                            </tr>
<?php
    $totalfrags = 0;
    $totalping = 0;
    $totaltime = 0;
    $nonbots = 0;
    foreach ($server_players as $key => $player)
    {
?>
                            <tr>
<?php
        # figure out if the player is a bot
        # HLTV is not really a bot we will have to treat it like one.
        # However, HLTV shouldn't even show up at this point
        $is_bot = 0;
        if (isset($player['uid']))
        {
            if ('UNKNOWN' == $player['uid'])
                $is_bot = 1;
            elseif ('HLTV' == $player['uid'])
                $is_bot = 1;
            elseif ('0' == $player['uid'])
                $is_bot = 1;
            elseif ('0' == $player['ping'])
                $is_bot = 1;
        }

        if (1 == $is_bot)
        {
?>
                                <td><img src="<?php echo $g_options['imgdir']; ?>/server.gif" alt="Bot"></td>
<?php
            $searchfor = 'BOT:'.md5($player['name'].$server_ip.':'.$server_port);
        }
        else
        {
            # I'm a real boy
            $nonbots++;
?>
                                <td>
<?php
            $pip = substr($player['ip'], 0, strpos($player['ip'], ":"));

$gi = geoip_open(GEOIPDAT, GEOIP_STANDARD);
$flagimg = strtolower(geoip_country_code_by_addr($gi, $pip));
geoip_close($gi);

echo flag($flagimg);

?>
</td>
<?php
            if (isset($player['uid']))
                $searchfor = $player['uid'];
            else
                $searchfor = $player['name'];
        }
        foreach ($player_columns as $column)
        {
            # Special columns
            # Name = a link
            #  If we have UID - link to UID, else player
            # Loss = add a %
            # Connected = Format it
            if ('name' == $column['column'])
            {


                if (!isset($player['uid'])) {
                    $temp = '<a href="'.$g_options['scripturl'].'?mode=search&amp;q='.HTMLEntities(URLEncode($searchfor), ENT_QUOTES, 'UTF-8').'&amp;st=uniqueid&amp;game='.$server['game'].'">'.str_replace(' ', '&nbsp;', HTMLEntities($player['name'], ENT_QUOTES, 'UTF-8')).'</a>';
                    $temp .= '<script>addPlayer(\''.URLEncode($searchfor).'\');</script>';
                } else {
                    $db->query("
                                SELECT
                                    `playerId`
                                FROM
                                    `hlstats_PlayerUniqueIds`
                                WHERE `uniqueId` = '".$searchfor."'
                                    AND `game` = '".$server['game']."'
                                LIMIT 0, 1");
                    list($playerId) = $db->fetch_row();
                    if($playerId) {
                        $temp = '<a href="'.$g_options['scripturl'].'?mode=playerinfo&amp;player='.URLEncode($playerId).'">'.str_replace(' ', '&nbsp;', HTMLEntities($player['name'], ENT_QUOTES, 'UTF-8')).'</a>';
                    } else {
                       $temp = '<a href="'.$g_options['scripturl'].'?mode=search&amp;q='.HTMLEntities(URLEncode($searchfor), ENT_QUOTES, 'UTF-8').'&amp;st=player&amp;game='.$server['game'].'">'.str_replace(' ', '&nbsp;', HTMLEntities($player['name'], ENT_QUOTES, 'UTF-8')).'</a>';
                    }
                 }
            } elseif ('loss' == $column['column'])
                $temp = $player['loss'].'%';
            elseif ('time' == $column['column'])
                $temp = Format_Time($player['time']);
            elseif ('frags' == $column['column'])
            {
                $temp = $player[$column['column']];

                if ($server_status)
                {
                    if ($server['source'])
                        $temp = $server_players_tmp[$key]['frags'];
                }
                $totalfrags += $temp;
            }
            else
                $temp = $player[$column['column']];
?>
                                <td align="<?php echo $column['align']; ?>"><?php echo $temp; ?></td>
<?php
        }
?>
                            </tr>
<?php
        if (!$is_bot)
        {
            $totalping += $player['ping'];
            $totaltime += $player['time'];
        }
    }
    # +1 for the special icon column
    $colspan = count($player_columns) + 1;
?>
                            <tr>
                                <td colspan="<?php echo $colspan; ?>" nowrap>&nbsp;</td>
                            </tr>
                            <tr>
                                <th align="right" colspan="<?php echo $colspan - 1; ?>" nowrap>Total Time</th>
                                <th align="right"><?php echo Format_Time($totaltime); ?></th>
                            </tr>
                            <tr>
                                <th align="right" colspan="<?php echo $colspan - 1; ?>" nowrap>Total Frags</th>
                                <th align="right"><?php echo $totalfrags; ?></th>
                            </tr>
<?php
    # Get the average ping (don't include bots!)
    if ($totalping > 0)
    {
?>
                            <tr>
                                <th align="right" colspan="<?php echo $colspan - 1; ?>" nowrap>Average Ping</th>
                                <th align="right"><?php echo round($totalping/$nonbots, 0); ?></th>
                            </tr>
<?php
    }
?>
                        </table>
                        <!-- Game is set. Fetch bans -->
<script language="Javascript1.2">
<!--
var uri='<?= $g_options['scripturl'];?>?mode=adminsfi&game=<?=urlencode($game)?>';
uasf();
// -->
</script>

<?php
}

if (count($server_hltv) > 0)
{
?>
                        <br><br>
                        <table border="0" width="99%">
                            <tr>
                                <th align="left" width="16"></th>
                                <th align="left">Name</th>
                                <th align="left" width="125">IP</th>
                                <th align="right" width="40">Delay</th>
                                <th align="right" width="60">Viewers</th>
                                <th align="right" width="60">Capacity</th>
                                <th align="right" width="75">Connected</th>
                            </tr>
<?php
    foreach ($server_hltv as $hltv)
    {
?>
                            <tr>
                                <td><img src="<?php echo $g_options['imgdir'];?>/hltv.gif" alt="HLTV"></td>
                                <td align="left"><?php echo $hltv['name'];?></td>
                                <td align="right"><?php echo $hltv['ip'];?></td>
                                <td align="right"><?php echo $hltv['delay'];?></td>
                                <td align="right"><?php echo $hltv['viewers'];?></td>
                                <td align="right"><?php echo $hltv['capacity'];?></td>
                                <td align="right"><?php echo $hltv['time'];?></td>
                            </tr>
<?php
    }
?>
                        </table>
<?php
}
if ($server_details['players_connecting'] > 0)
{
    echo 'There are currently <b>'.$server_details['players_connecting'].'</b> player(s) connecting to the server.';
}
?>
                    </td>
                </tr>
                <tr bgcolor="<?php echo $g_options['table_bgcolor1']; ?>">
                    <td valign="top" class="fontNormal">
<?php
# For our main server information area we can grab some
# bits of information from the rules

# If ClanMod or AMX mod is installed we may know what
# the next map is
if (isset($server_rules['cm_nextmap']))
    $server_details['nextmap'] = $server_rules['cm_nextmap'];
elseif (isset($server_rules['amx_nextmap']))
    $server_details['nextmap'] = $server_rules['amx_nextmap'];

# Some unfortunate games like CS don't usually give the map timeleft
# I wonder if some plugin can yet again provide a use here...
# Generally the plugin version is more reliable so that is the highest priority to use
if (isset($server_rules['amx_timeleft']))
    $server_details['timeleft'] = $server_rules['amx_timeleft'];
elseif (isset($server_rules['cm_timeleft']))
    $server_details['timeleft'] = $server_rules['cm_timeleft'];
elseif (isset($server_rules['mp_timeleft']))
    $server_details['timeleft'] = sprintf('%02u:%02u', ($server_rules['mp_timeleft'] / 60), ($server_rules['mp_timeleft'] % 60));
?>
                        <?= _("Address");?>: <?php echo $server_details['address']; ?><br>
                        <?= _("Server Type");?>: <?php echo $server_details['os']; ?>, <?php echo $server_details['type']; ?><br>
<?php
//
// Map change. If there is no players, allow changin map from web.
//
$players = $server_details['players_connecting']+$nonbots;

$changedMap = false;
if (isset($_POST['changemap']) && $players < 1 && $server_rcon) {
    $usableres = $db->query("
        SELECT
            map
        FROM
            hlstats_Events_Frags
        LEFT JOIN hlstats_Players ON
            hlstats_Players.playerId = hlstats_Events_Frags.killerId
        WHERE
            hlstats_Events_Frags.map='".quotemeta($_POST['changemap'])."' AND
            hlstats_Events_Frags.serverId='{$HTTP_GET_VARS['server']}'
        GROUP BY
            hlstats_Events_Frags.map");
    if($db->num_rows($usableres) >= 1) {
        $mapcmd = "changelevel ".$_POST['changemap'];
        if ($server['source'] == 1) {
            Source_Rcon($server_ip, $server_port, $server_rcon, $mapcmd);
        } else {
            HalfLife_Rcon($server_ip, $server_port, $server_rcon, $mapcmd, $server_challenge);
        }
        $changedMap = true;
        $server_details['nextmap'] = $_POST['changemap'];
        $messages['footer'][] = sprintf(_("Map has been changed to %s"),$_POST['changemap']);
    } else {
        $messages['footer'][] = sprintf(_("No such map: %s"),$_POST['changemap']);
    }
}
if ($players < 1 && $server_rcon && $changedMap == false) {
    $mapsresult = $db->query("
        SELECT
            map,
            COUNT(map) AS count
        FROM
            hlstats_Events_Frags
        LEFT JOIN hlstats_Players ON
            hlstats_Players.playerId = hlstats_Events_Frags.killerId
        WHERE
            hlstats_Events_Frags.map != '' AND
            hlstats_Events_Frags.serverId='{$HTTP_GET_VARS['server']}'
        GROUP BY
            hlstats_Events_Frags.map
        ORDER BY
            count DESC
");

    echo '<form name="changelevel" method="POST" action="'.$g_options["scripturl"].'?mode=live_stats&server='.$HTTP_GET_VARS['server'].'" style="padding:2px;margin:0px;">';
    echo _("Map").': ';
    echo '<select name="changemap" onchange="document.changelevel.submit()">';
    while ($rowdata = $db->fetch_array($mapsresult)) {

        echo '<option value="'.$rowdata['map'].'"';
        if($server_details['map'] == $rowdata['map']) echo ' selected';
        echo '>'.$rowdata['map'].'</option>'."\n";
    }
    echo '</select></form>';
} else {
?>
    <?= _("Map");?>: <a href="<?php echo $g_options['scripturl']; ?>?mode=mapinfo&amp;map=<?php echo $server_details['map']; ?>&amp;game=<?php echo $server['game']; ?>"><?php echo $server_details['map']; ?></a><br>
<?php
}

if (isset($server_details['nextmap']))
    echo _("Nextmap").': <a href="'.$g_options['scripturl'].'?mode=mapinfo&amp;map='.$server_details['nextmap'].'&amp;game='.$server['game'].'">'.$server_details['nextmap'].'</a><br>';

# Are there any time limits or frag limits?
if (isset($server_details['timeleft']))
{
    echo 'Timeleft: '.$server_details['timeleft'];
    if(isset($server_rules['mp_timelimit']))
        echo ' ('.sprintf('%02u:%02u', $server_rules['mp_timelimit'], 0).')';
    echo '<br>';
}
if ($server_rules['mp_fraglimit'] > 0)
{
    echo 'Fragsleft: '.$server_rules['mp_fragsleft'];
    if (isset($server_rules['mp_fragslimit']))
        echo '('.$server_rules['mp_fraglimit'].')';
    echo '<br>';
}
?>
                        Password: <?php echo $server_details['password']; ?><br>
                        Players: <?php echo $server_details['players_real']; ?>/<?php echo $server_details['max'];?><br>
<?php
if ($server_details['botcount'] > 0)
{
    # Don't show this information if there are no bots
?>
                        Bots: <?php echo $server_details['botcount']; ?>/<?php echo $server_details['max']; ?><br>
<?php
}
if ($server_details['hltvcount'] > 0)
{
    # Don't show this information if there is no HLTV
?>
                        HLTV: <?php echo $server_details['hltvcount']; ?>/<?php echo $server_details['max']; ?><br>
<?php
}
if ($server_details['players_connecting'] > 0)
{
    # Don't show this information if there are no players connecting
?>
                        Connecting: <?php echo $server_details['players_connecting'] ?>/<?php echo $server_details['max']; ?><br>
<?php
}
?>
                        Valve Anti-Cheat: <?php echo $server_details['secure']; ?><br>
<?php
$addon_array = array();
$server_details['addon_count'] = 0;
if ($server_rules)
{
?>
                        Rules:<br>
                        <select name="rules" style="width: 200px;">
<?php
    # Load our plugin list
    $db->query("SELECT * FROM hlstats_server_addons");

    while ($addon_list = $db->fetch_array())
        $server_addon[$addon_list['rule']] = array('addon' => $addon_list['addon'], 'url' => $addon_list['url']);

    ksort($server_rules);

    foreach ($server_rules as $key => $value)
    {
        if (isset($server_addon[$key]))
        {
            if ($server_addon[$key]['url'])
                $addon_array[] = '<a href="'.$server_addon[$key]['url'].'" target="_blank">'.str_replace('%', $value, $server_addon[$key]['addon']).'</a>';
            else
                $addon_array[] = str_replace('%', $value, $server_addon[$key]['addon']);
            $server_details['addon_count']++;
        }
        echo "<option>$key = $value</option>\n";
    }
?>
                        </select><br><br>
<?php
}
?>
<?php
if ($server_details['addon_count'] > 0)
{
?>
Server Addons:<br>
<ul>
<?php
    foreach ($addon_array as $addon)
    {
?>
    <li> <?php echo $addon; ?></li>
<?php
    }
?>
</ul>
<?php
}
?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
