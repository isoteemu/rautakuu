<?php
/*
$hakuUri = "http://localhost/cgi-bin/search.cgi";
if (isset( $_GET )) {
    $hakuUri .= "?";
    foreach( $_GET as $key => $val ) {
        $hakuUri .= urlencode($key)."=".urlencode($val)."&amp;";
    }
}
*/
if(!empty($_GET['q'])) {
    $qval = $_GET['q'];
} else {
    $qval = "Etsi rautakuun sivustolta";
}

$content .= '
<form action="/rautakuu/index.php/PageSearch" method="GET" name="f">
<input type="text" name="q" value="'.htmlentities($qval).'"  onFocus="if(this.value==\'Etsi rautakuun sivustolta\')this.value=\'\';" style="background-color : transparent; border-bottom-color : #FF3C12; border-bottom-style : solid; border-bottom-width : 1px; border-left-style : none; border-right-style : none; border-top-color : #FF3C12; border-top-style : solid; border-top-width : 1px; color : #545454; font-family : verdana; font-size : 10px; width : 70%; padding-left: 20px;" >
<input type="submit" name="cmd" value="etsi" style="background-color : transparent; border-bottom-color : #FF3C12; border-bottom-style : solid; border-bottom-width : 1px; border-left-color : #FF3C12; border-left-style : solid; border-left-width : 1px; border-right-color : #FF3C12; border-right-style : solid; border-right-width : 1px; border-top-color : #FF3C12; border-top-style : solid; border-top-width : 1px; color : #545454; font-family : verdana; font-size : 10px; width : 20%;" >
</form>
';
/*
ini_set("allow_url_fopen", true );
ini_set("user_agent", "PHP (Rautakuu [dot] org)");
ini_set("default_socket_timeout", "10");

if(connection_aborted()) die("Connection Aborted");
if(!empty($_GET['q'])) {
    if( function_exists( "file_get_contents" )) {
        $content .= file_get_contents($hakuUri);
    } else {
        $content .= implode('', file($hakuUri));
    }
}
*/
