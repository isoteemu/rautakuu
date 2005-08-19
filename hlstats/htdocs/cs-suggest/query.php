<?php
header("Content-Type: text/html;charset=utf-8");

if (empty($_GET['q'])) {

	die('
<html>
<head>
<title>CS Suggest</title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<script>
function bodyLoad() {
  if (parent == window) return;
  var frameElement = this.frameElement;
  parent.sendRPCDone(frameElement, "", new Array(), new Array(), new Array(""));
}
</script></head><body onload=\'bodyLoad();\' bgcolor="#15154D"></body></html>');
}

include_once("DB.php");

$q = addslashes($_GET['q']);

$DB =& DB::Connect("mysql://cs:f9307fe00c@localhost/hlds");
if (DB::isError($DB)) {
    die($DB->getMessage());
}

$query = "SELECT
	name,
	COUNT( name )
FROM
	hlstats_PlayerNames
WHERE
	name LIKE '".$q."%'
	AND name NOT LIKE '[bot]%'
GROUP BY name
ORDER BY name ASC
LIMIT
	0, 15";

$res =& $DB->query($query);
if (DB::isError($res)) {
	die($res->getMessage());
}

// Alustetaan muuttujat
$qr = "";
$qi = "";

function eee( $int ) {
	$i = $int - 1;
	$e = "";
	$ii = 0;
	while( $i != $ii ) {
		$ii++;
		$e .= "e";
	}
	return $e;
}

if ( $q{0} == "t" && $q{1} == "e" && substr($q, 1) == eee(strlen( $q ))) {
	$qr .= '"'.$q.'emu", ';
	$qi .= '"0wn3d", ';
}

while ($row =& $res->fetchRow()) {
	$qr .= '"'.addslashes($row[0]).'", ';
	$qi .= '"'.addslashes($row[1]);
	if( $row[1] > 1 ) {
		$qi .= ' Nickki&auml;", ';
	} else {
		$qi .= ' Nickki", ';
	}
}

// Olen laiska. mutta Toimii(tm).
$qr = substr($qr, 0, strlen( $qr )-2);
$qi = substr($qi, 0, strlen( $qi )-2);



//die('sendRPCDone(frameElement, "fast bug", new Array("fast bug track", "fast bugs", "fast bug", "fast bugtrack"), new Array("793,000 results", "2,040,000 results", "6,000,000 results", "7,910 results"), new Array(""));');

$rcp = 'sendRPCDone(frameElement, "'.addslashes($q).'", new Array('.$qr.'), new Array('.$qi.'), new Array(""));';

echo $rcp;

?>