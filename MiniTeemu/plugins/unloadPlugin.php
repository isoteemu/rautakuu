<?php
/**
 * unloadPlugin.php - Lataa uuden pluginin
 */
if( $init==true ) {
    $plugin->addRule('code',   "PRIVMSG");
    $plugin->addRule('prefix', "MiniMe, unloadPlugin ");
    $plugin->addRule('nick',   "IsoTeemu");
    $plugin->addRule('break',  true);
    return;
}

$pluginName = trim(substr($plugin->line->msg, strrpos($plugin->line->msg, " ")+1));

if(!isset($plugin->irc->irc_data->triggers->plugins[$pluginName])) {
    $plugin->message("{$plugin->line->nick}: Pluginiä {$pluginName} ei ole rekisteröity.");
    return false;
}
unset($plugin->irc->irc_data->triggers->plugins[$pluginName]);
$plugin->message("{$plugin->line->nick}: Plugin {$pluginName} poistettiin.");
return true;

?>