<?php

Header("Pragma: no-cache");
Header("Cache-control: private, no-cache");
Header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

require_once('Text/CAPTCHA.php');

// Generate a new Text_CAPTCHA object, Image driver
$c =& Text_CAPTCHA::factory('Image');
$i =& $c->init(200, 80, null, array('font_path' => realpath(dirname(__FILE__).'/../inc/')."/",
                                    'font_file' => 'cour.ttf',
                                    'color' => array(
                                        '0' => '#FF3C12',
                                        '1' => '#FFFFFF'
                                    )));
if(PEAR::isError($i)) {
    echo "<pre>";
    print_r($c);
    echo "</pre>";
    die($i->getMessage());
}

// Get CAPTCHA secret passphrase
$_SESSION['captcha'] = $c->getPhrase();
if(PEAR::isError($_SESSION['captcha'])) {
    die($_SESSION['captcha']->getMessage());
}

// Get CAPTCHA image (as PNG)
Header("Content-Type: Image/png");
echo $c->getCAPTCHAAsPNG();
die();
