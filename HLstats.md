## Rautakuu `[`dot`]` org » HLstλts ##

Rautakuun [hlstats](http://rautakuu.org/hlstats) on customoitu [United Adminsin Hlstatsista](http://www.unitedadmins.com/index.php?p=content&content=hlstats). Projekti sai alkunsa siitä, että Valve julkaisi (taas) päivityksen, joka rikkoi vanhan CSn yhteensopivuutta. Samalla se myös rikkoi Hlstatsin ominaisuuksia, ja koska hlstatsia ei ole kehitetty enään pidempään aikaan, päätettiin itse korjata ongelma.
Myöhemmässä vaiheessa myös ominaisuuksia alettiin lisätä, joista monet ovat backportteja [HLstatsX:stä](http://www.hlstatsx.com).

### Parannuksia vakio-hlstatsiin satunnaisessa järjestyksessä ###
  * **Hakukone ystävälliset urlit** <br> Aluperäinen hlstats käytti GET metodia urleissa. Toimii, mutteivat hakukoneet tykkää. Haxin avulla koodissa urlit muutettaan hakukoneystävällisemmiksi.<br>
<ul><li><b>Sivujen cacheaminen, pakkaaminen ja esihaku</b> <br> Monet tietokannan käsittelyyn lisätyt joinit tekevät sivujen aukeamisesta hidasta, aktiivisessa ja pitkän aikavälin tarkastelussa. Niinpä ensinnäkin sivut pyritään cahceamaan. Toiseksi, myös sivut on mahdollista pakata nettikaistan säästämiseksi (puhtaan tekstin pakkaus säästää paljon kaistaa ja vie vähän tehoa). Ja, lisäksi mozilla sisältää mahdollisuuden <a href='http://www.mozilla.org/projects/netlib/Link_Prefetching_FAQ.html#What_is_link_prefetching'>"esihakea"</a> sivut, joille mahdollisesti käyttäjä seuraavaksi sunnistaa.<br>
</li><li><b>Sijoituksen näyttö pelaajan tietosivulla</b> <br> Vaikea uskoa että tämä omiaisuus uupui alkuperäisestä hlstatsista.<br>
</li><li><b>Pankissa olevat hyffet</b> <br> Rautakuun erikoisuuksiin kuuluu <a href='http://rautakuu.org/drupal/node/26'>VIPeille</a> <a href='http://rautakuu.org/drupal/cs-pankki'>pankkipalvelut</a>. Näyttää rahasumman pankkitilillä.<br>
</li><li><b>amxbans tukì</b> <br> <a href='http://www.amxbans.net/'>AMX Bans</a> tuki. Näyttää bannitut pelaajat<br>
</li><li><b>Vac bannien huomaus</b> <br> Jatkoa amx bans tuelle, bannii amxbans kantaan pelaajan, jos pelaaja saa VAC bannit.<br>
</li><li><b>Parannuksia serverin tietojen näytössä</b> <br> Jos serverillä ei ole pelaajia, voi kuka tahansa vaihtaa serverin mappia. Lisäksi myös heti hlstatsin etusivulla näytetään serverin senhetkinen kartta ja pelaajamäärä.<br>
</li><li><b>Skillin ja rankingin kehitys</b> <br> Näyttää onko skill ja/tai rankin muuttunut viikon takaiseen.<br>
</li><li><b>Aktiivisten pelaajien ranking</b> <br> Jos pelaaja ei ole pelannut N päivän aikana serverillä, häntä käsitellään inaktiivisena, ja sijoitus poistetaan. skill ja tiedot pysyvät kannassa, ja sijoitus lasketaan heti uudestaan pelaajan taas alkaessa pelaamisen.<br>
</li><li><b>RSS uutisten esitys</b> <br> Voidaan esittää uutisia hlstatsin etusivulla pohjautuen RSS newsfeediin.<br>
</li><li><b>Maatuki</b> <br> Käyttämälle Geoip:tä, kertoo mistä päin mailmaa pelaaja on.<br>
</li><li><b>Rautakuu <a href='dot.md'>dot</a> org specifikoituja muutoksia</b> <br> Ominaisuuksia mm hakea rautakuun käyttäjäkannasta pelaajalle tiedot etc.<br>
</li><li><b>"Tuki" <a href='http://admins.fi/banlist/bans/'>admins.fi</a>, <a href='http://area51.pelikaista.net/csbans/ban_list.php'>aurian</a> ja <a href='http://pelit.surffi.net/ban4/ban_list.php'>SurffiNET</a> banneille</b> <br> Tuella tarkoitetaan sitä, että jos pelaaja on bannittu, näkyy kuvake sijoituksen vieressä banniin.<br>
</li><li><b><a href='http://www.gravatar.com/'>Gravatar</a> kuvat</b> <br> Jos pelaaja on asettanut Avatarin joko drupalissa, tai asettanut hlstatsiin sähköpostinsa sekä omaa GRavatarin, on pelaajan sivulla avatarin kuvake.<br>
<h3>TEH S0URC3C0D3</h3>
SVN: <a href='https://rautakuu.googlecode.com/svn/hlstats/'>https://rautakuu.googlecode.com/svn/hlstats/</a></li></ul>

Jos et tosin osaa lukea/editoida PHP:tä/SQL:lää ([wiki:HLstatsSql Osasta tietokantaa kuvaus, loput RTFS] ), voi olla että et yritä edes väsätä tätä hlstatsia...<br>
<br>
Drupaliin sovitettu modi, ei koskaan vain valmiiksi saatu:<br>
<a href='https://rautakuu.googlecode.com/svn/rautakuusivut/drupal/modules/hlstats.module'>https://rautakuu.googlecode.com/svn/rautakuusivut/drupal/modules/hlstats.module</a>