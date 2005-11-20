<?php
$content .= '</p><strong>Kaikki dokumentoinnin sivut:</strong></p>';
$content .= '<p>';

$ares = &$db->query("SELECT page_name, change_author, change_log FROM `wicked_pages`");

while ( $apage = &$ares->fetchRow() ) {
    $content .= '<a href="/rautakuu/index.php/'.urlencode($apage[0]).'" title="'.htmlentities($apage[2]).'"><strong>'.htmlentities($apage[0]).'</strong></a> / <a href="mailto:'.$apage[1].'@rautakuu.org">'.$apage[1].'</a><br>';
}

$content .= '</p>';
