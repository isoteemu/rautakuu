## hlstats\_Adminsfi\_Cache ##
Tämä taulu toimii cachena ulkopuolisilta haetuille banneille.<br>
<ul><li><code>uniqueId</code><br> Sarake tallettaa haettavan käyttäjän SteamID:n<br>
</li><li><code>group</code><br> Bannin ryhmä, akai lähde. Tämä sekä <code>uniqueId</code> muodostavat taulun avaimen.<br>
</li><li><code>checked</code><br> Aika, jolloin viimeksi bannin tilanne on tarkastettu. Idea on luonnollisestikkin se, että vanhimmat bannit päivitetään ensimmäisenä.<br>
</li><li><code>result</code><br> Bannin haun tuloksen linkkiurl.</li></ul>

<pre><code>CREATE TABLE `hlstats_Adminsfi_Cache` (<br>
  `uniqueId` varchar(64) NOT NULL default '',<br>
  `group` varchar(32) NOT NULL default '',<br>
  `checked` datetime NOT NULL default '0000-00-00 00:00:00',<br>
  `result` varchar(255) default NULL,<br>
  PRIMARY KEY  (`uniqueId`,`group`),<br>
  KEY `checked` (`checked`)<br>
) ENGINE=MyISAM;<br>
</code></pre>

<h2>hlstats_Link_Trace</h2>
Sisältää jäljityksen siitä, kuka mennyt minnekkin sivuille. Tätä tietoa sitten jälkikäteen käytetään arvailemaan, minkä sivun selaimen kannattaisi hakea ennakkoon. Jos koneessa vähäänkään vääntöä, kannatta tämä ominaisuus poistaa lähdekoodista, koska se on <b>köh*paska*köh</b>.<br>
<br>
<pre><code>CREATE TABLE `hlstats_Link_Trace` (<br>
  `id` int(11) NOT NULL auto_increment,<br>
  `time` timestamp NOT NULL default CURRENT_TIMESTAMP,<br>
  `whom` varchar(16) NOT NULL default '',<br>
  `from` varchar(255) NOT NULL default '',<br>
  `to` varchar(255) NOT NULL default '',<br>
  PRIMARY KEY  (`id`),<br>
  KEY `whom` (`whom`)<br>
) ENGINE=MyISAM;<br>
</code></pre>

<h2>hlstats_Old_Rank</h2>
Pitää vanhat sijoitukset. Joka viikko cronissa nuketetaan, ja kopioidaan uudet rankingit. Heikkoa, mutta toimii(tm).<br>
<pre><code>CREATE TABLE `hlstats_Old_Rank` (<br>
  `rank` int(10) unsigned default NULL,<br>
  `skill` int(11) unsigned NOT NULL default '1000',<br>
  `playerId` int(10) NOT NULL default '0',<br>
  `game` varchar(16) NOT NULL default 'cstrike',<br>
  PRIMARY KEY  (`playerId`),<br>
  KEY `rank` (`rank`)<br>
) ENGINE=MyISAM;<br>
</code></pre>

<h2>hlstats_Events_Chat</h2>
Löydetty jostain kolmannen osapuolen hlstats-patcheistä.<br>
<pre><code>CREATE TABLE `hlstats_Events_Chat` (<br>
  `id` int(10) unsigned NOT NULL auto_increment,<br>
  `eventTime` datetime NOT NULL default '0000-00-00 00:00:00',<br>
  `serverid` int(10) unsigned NOT NULL default '0',<br>
  `playerid` int(10) unsigned NOT NULL default '0',<br>
  `type` smallint(6) NOT NULL default '0',<br>
  `message` text NOT NULL,<br>
  `map` varchar(20) NOT NULL default '',<br>
  PRIMARY KEY  (`id`),<br>
  KEY `eventTime` (`eventTime`)<br>
) ENGINE=MyISAM;<br>
</code></pre>