<?php
$twoweek = time()-mktime(0,0,0,0,14);

$eres =& $db->query("
SELECT
    `page_name`, `page_text`, `change_author`, `version_created`
FROM
    `wicked_pages`
WHERE
    `page_name` LIKE 'News%'
AND
    `page_text` NOT LIKE ''
AND
    `version_created` > '{$twoweek}'
ORDER BY
    `version_created` DESC");

$ltime = 0;

if($eres->numRows() > 0 ) {
    while( list($pname, $text, $author, $created) = $eres->fetchRow()) {
        $psname = substr($pname, 4);
        $content .= "<p>";
        $content .= "<div class=\"header\" title=\"Uutista viimeksi muokannut: {$author}\"><strong>";
        $content .= strftime("%d.%m.%Y", $created);
        $content .= ":</strong> <a href=\"/rautakuu/index.php/{$pname}\">{$psname}</a></div>";
        $content .= "<blockquote>";
        $content .= mb_convert_encoding($wiki->transform($text, 'Xhtml'), "utf-8", "iso-8859-15");
        $content .= "</blockquote></p>";
        if( $created > $ltime ) $ltime = $created;
    }
}

$content .= "<p>&nbsp;</p>";

$eres = $db->query("
SELECT
    `page_text`, `version_created`
FROM
    `wicked_pages`
WHERE
    `page_name` = 'EtuSivu'");

list($esivu, $created) = $eres->fetchRow();
if( $created > $ltime ) $ltime = $created;

$lmtime = gmstrftime( "%a, %d %b %Y %T %Z", $ltime );

$content .= "<p>".$wiki->transform($esivu, 'Xhtml')."</p>";
$content .= '<iframe width="100%" height="200" src="/rautakuu/irclog.php" frameborder="0">Selaimesi ei tue iFramea :(. Et näe sika siistiä IRC logia.</iframe>';
