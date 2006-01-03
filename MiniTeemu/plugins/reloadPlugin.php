<?php
/**
 * reloadPlugin.php - Lataa pluginin uudelleen
 */
if( $init==true ) {
    $plugin->addRule('code',   "PRIVMSG");
    $plugin->addRule('prefix', "MiniMe, reloadPlugin ");
    $plugin->addRule('nick',   "IsoTeemu");
    $plugin->addRule('break',  true);
    return;
}

$pluginName = trim(substr($plugin->line->msg, strrpos($plugin->line->msg, " ")+1));

if(!isset($plugin->irc->irc_data->triggers->plugins[$pluginName])) {
    $plugin->message("{$plugin->line->nick}: Pluginiä {$pluginName} ei ole rekisteröity");
    return false;
}

if(!$plugin->irc->irc_data->triggers->registerPlugin($pluginName)) {
    $plugin->message("{$plugin->line->nick}: Virhe ladattaessa pluginiä {$pluginName}");
    return false;
}
$plugin->message("{$plugin->line->nick}: Plugin {$pluginName} ladattiin onnistuneesti");
return true;

?>