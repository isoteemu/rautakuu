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


    /*
     * HLstats Page Footer
     * This file will be inserted at the end of every page generated by HLstats.
     * This file can contain PHP code.
     */

if(!defined("_HLSTATS")) die("Direct access denied.");

?>
</td>
</tr>
</table>
<center>
<br /><br />
<?php echo $g_options["font_small"];

$messages['footer'][] = sprintf(_("Generation took: %ss."),substr(timer(),0,4));
$messages['footer'][] = sprintf(_("Generated by: %s %s"),"<a href=\"http://svn.rautakuu.org/trac/homebrevcomputing/wiki/HLstats\">Rautakuu [dot] org ::  Hlstλts</a>", VERSION);

global $messages;

if(isset($messages['footer']) && is_array($messages['footer'])) {
    foreach($messages['footer'] as $msg) {
        echo "<span style=\"white-space:nowrap;\">[ $msg ]</span> ";
    }
}
echo "<br />";


global $user;
if(!isset($user->uid)) {
    echo ' [ <a href="'.$g_options["drupal_url"].'">'._("Admin login").'</a> ]';
}

?>
<?php
if(isset($messages['admin']) && is_array($messages['admin'])) {
    foreach($messages['admin'] as $msg) {
        echo " [ $msg ]";
    }
}

echo $g_options["fontend_small"];
?>

</center>
<script language=text/javascript src="/awstats_misc_tracker.js"></script>

</body>
</html>
