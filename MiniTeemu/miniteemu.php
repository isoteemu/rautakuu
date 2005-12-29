<?php

define("MINITEEMU", __FILE__);

/**
 * Ajastin function.
 */
function timer() {
    static $start;
    list($usec, $sec) = explode(" ", microtime());
    $now = ((float)$usec + (float)$sec);
    if(!isset( $start )) {
        $start = $now;
    }
    $timed = $now - $start;
    $secs = explode(".", $timed );
    return $secs[0].".".substr($secs[1], 0, 2);
}

/**
 * Kuinka monta rivi� voi containerissa olla kerrallaan s�il�ss�.
 */
define("IRC_DATA_LINES_MAX_COUNT", 500);
/**
 * Kuinka monta rivi� poistetaan kerralla kun containerissa on IRC_DATA_LINES_MAX_COUNT rivi�.
 */
define("IRC_DATA_LINES_RM_COUNT", 250);

class irc_data {

    // K�sitelt�v� data
    var $data;

    // Ylij��nyt osa
    var $leftOver = "";

    // Rivi objectit
    var $lines = array();

    // Kokonais rivien m��r�
    var $i=0;

    // Viimeksi tarkastettu rivi
    var $lastExecLine=0;

    // IRC olion referenssi
    var $irc;

    // Triggerit.
    var $triggers;

    function irc_data(&$irc) {
        $this->irc =& $irc;
        $this->triggers =& new irc_trigger_plugins(&$this->irc);
    }

    function append($rawdata) {
        // Muutetaan windows enterit UNIX enteriksi.
        $this->data = str_replace("\r", '', $rawdata);
        $this->leftOver = "";

        // Jos dataa j�� yli, yritett�n se ottaa talteen.
        if(( $strrpos = strrpos($data ,"\n")) !== false ) {
            $this->leftOver = substr($this->data, $strrpos+1);
            irc::trace("LEFTOVER DATA: {$this->leftOver}");

            // Otetaan oleellinen osa, ja j�tet��n yli��nyt osa pois.
            $this->data = substr($this->data, 0, $strrpos);
        }

        // Hajotettaan data riveiksi.
        $lines = explode("\n", $this->data );

        while( count( $lines ) > 0 ) {

            // sanity check
            if(isset($this->lines[$this->i])){
                irc::trace("Rivi {$this->i} on jo olemassa. O'ou.");
            }

            $this->lines[$this->i] =& new irc_data_line( array_shift($lines) );

            if(!$this->lines[$this->i]->valid()) {
                // Line ei ollut hyv�ksytt�v�. Pudotetaan se.
                irc::trace("Pudotetaan rivi {$this->i}. Ei valid()");
                unset($this->lines[$this->i]);
                continue;
            }

            $this->i++;
        }

        if($this->numLines() >= IRC_DATA_LINES_MAX_COUNT) {
            irc::trace("Cleanup. Saavutettu ".IRC_DATA_LINES_MAX_COUNT." rivin m��r�");

            $this->lines = array_slice($this->lines, 0-IRC_DATA_LINES_RM_COUNT);
            ksort($this->lines);
        }
    }

    function numLines() {
        return count( $lines );
    }

    /**
     * Ajaa triggerit
     */
    function runTriggers() {
        irc::trace("lastExecLine: {$this->lastExecLine} i: {$this->i} ");

        if(!isset($this->triggers)) {
            $this->triggers =& new irc_trigger_plugins(&$this->irc);
        }
        while( $this->lastExecLine < $this->i ) {
            $this->triggers->newEvent(&$this->lines[$this->lastExecLine]);
            $this->lastExecLine++;
        }
    }

    /**
     * palauttaa ylij��neen osan.
     * @param $nuke h�vitet��nk� ylij��nyt osa.
     */
    function getLeftOvers($nuke=true) {
        if(empty($this->leftOver)) return;
        $data = $this->leftOver;
        if( $nuke == true ) {
            $this->leftOver = "";
        }
        return $data;
    }

    /**
     * T�m� palauttaa viimeisen rivin.
     */
    function getLastLine() {
        if( is_array($this->lines) && count($this->lines) > 0 ) {
            return $this->lines[count($this->lines)-1]->getLine();
        } else {
            return "";
        }
    }
}

class irc_data_line {

    var $data;
    var $valid;

    var $nick;
    var $from;
    var $code;
    var $host;
    var $ident;
    var $channel;

    var $msg;

    var $ping = false;

    var $timestamp;

    function irc_data_line($line) {

        $this->timestamp = timer();

        irc::trace("Saatu dataa:\n<< \"{$line}\"");
        if( substr($line,0,6) != "PING :" && $this->valid($line) ) {
            $this->data = substr($line, 1);
        } else {
            $this->data = $line;
        }

        $this->parseLine();
    }

    function valid($line=null) {
        if( $line === null ) $line = $this->data;
        if(!empty( $this->valid )) return $this->valid;
        if( substr($line,0,6) == "PING :" ) {
            $this->valid = true;
        } elseif( substr( $line, 0, 7 ) == "ERROR :" ) {
            $this->valid = true;
        } elseif( substr( $line, 0, 1 ) == ":" ) {
            $this->valid = true;
        } else {
            $this->valid = false;
        }
        return $this->valid;
    }

    function parseLine() {

        if( substr($this->data,0,6) == "PING :") {
            $this->ping = true;
            return;
        }

        $exs = explode(" ", $this->data);
        $poe = strpos( $exs[0], "!" );
        $poa = strpos( $exs[0], "@" );
        $poc = strpos( $this->data, ":" );

        $this->code    = $exs[1];

        $this->from    = $exs[0];
        $this->nick    = substr($exs[0], 0, $poe);
        $this->host    = substr($exs[0], $poa+1);
        $this->ident   = substr($exs[0], $poe+1, ($poa-$poe)-1);
        $this->channel = $exs[2];

        // WHOIS kertoo nelj�nten� parametrin� nickin
        if(empty($this->nick) && isset($exs[3])) $this->nick = $exs[3];

        $this->msg     = trim(substr($this->data, $poc+1));
    }

    function getLine() {
        return $this->data;
    }

    function get ($what) {
        if(isset($this->$what)) {
            return $this->$what;
        } else {
            irc::trace("Ei {$what}:ia tiedossa");
            return false;
        }
    }
}

define("IRC_TRACE_ECHO", 0);
define("IRC_TRACE_SEND", 1);

class irc {

    // Serveri, jolle liityt��n
    var $server     = "port80.se.quakenet.org";

    // Serverin portti.
    var $port       = "6667";

    // Kanavan nimi, jolle liityt��n.
    var $channel    = "#rautakuu";

    // Botin henk. koht. tietoja.
    var $botRName   = "Mini Me, completes me!";
    var $botNick    = "MiniTeemu";
    var $botUName   = "rautakuu";

    // Kuinka monta kertaa yritet��n yhdist�� ennen kuin annetaan periksi.
    var $tries      = 5;

    // How long to try connect before timeout? in secs
    var $_timeout   = 10;

    // Yhteys resurssi
    var $_connection;

    // Kuinka kauan odotetaan tapahtumahorisonttia?
    var $_delay     = 100;

    // IRC data container
    var $irc_data;

    // Bufferi joka sis�lt�� kaiken saamamme datan.
    var $_loggedin  = false;

    // Trace ajuri
    var $traceDrv   = IRC_TRACE_ECHO;

    // viestibufferi
    var $buffer     = array();

    /**
     * PHP5 constructori. Kutsuu PHP4 constructorin
     */
    function __construct($config=null) {
        $this->irc($config);
    }

    /**
     * PHP4 constructor
     * @param $config asetukset
     */
    function irc( $config = null ) {
        if( $config != null && is_array( $config )) {
            if( $config['server'] )     $this->server   = $config['channel'];
            if( $config['channel'] )    $this->channel  = $config['channel'];
            if( $config['port'] )       $this->port     = $config['port'];
        }

        // Huuhdellaan v�litt�m�sti
        ob_implicit_flush(true);

        // Ei aikarajaa viel�
        @set_time_limit(0);

        // PHP4 __destructori emulaatio
        if( version_compare( phpversion(), "5.0.0", "<") == 1 ){
            register_shutdown_function(array(&$this,"__destruct"));
        }

        // Luodaan data stack
        $this->irc_data =& new irc_data(&$this);

        // Signal handlerit
        //pcntl_signal(SIGTERM, array(&$this,"_SigTerm"));
        //pcntl_signal(SIGKILL, array(&$this,"_SigKill"));

    }

    // Hoitaa viestin l�hetyksen IRC serverille
    function send( $msg ) {
        if( $this->_state() === false ) {
            $this->trace("Yhteytt� ei ole");
            return false;
        }
        $this->buffer[] = $msg;
    }

    function _send($msg) {
        if(! socket_write($this->_connection, $msg."\r\n")) {

            $this->trace("Viestin l�hetys ep�onnistui \"{$msg}\"");
            return false;
        }
        $this->trace("Viesti l�hetetty\n>> \"{$msg}\"");
        return true;
    }

    function flushBuffer() {
        if(count($this->buffer) < 1) return true;
        $this->_send($this->buffer[0]);
        array_shift($this->buffer);
        return true;
    }

    /**
     * Yhdist�� palvelimelle
     */
    function connect() {
        // Yritet��n loopata yhteytt�
        $i = 0;
        while( $i < $this->tries ) {

            $i++;
            $this->trace("Yritet��n yhdist�� #".($i));
            //$this->_connection = fsockopen($this->server, $this->port, $errno, $errstr);
            $this->_connection = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            socket_set_nonblock($this->_connection);

            $timeout = time();
            while(!socket_connect($this->_connection, $this->server, $this->port)) {
                $err = socket_last_error($this->_connection);
                if ($err == 115 || $err == 114) {
                    if ((time() - $timeout) >= $this->_timeout) {
                        $this->trace("Connection timed out");
                        continue 2;
                    } else {
                        sleep(1);
                        continue;
                    }
                }
                $this->trace("Problems on connecting: ".socket_strerror($err));
                continue 2;
            }
            if(!socket_set_block($this->_connection)) {
                $this->trace("Could not set socket block ".socket_strerror(socket_last_error($this->_connection)));
                continue;
            }
            socket_clear_error($this->_connection);
            $this->trace("R:".is_resource( $this->_connection )." T:".get_resource_type( $this->_connection )." E:".socket_last_error( $this->_connection ));
            return true;
        }
        return false;
    }

    /**
     * Kirjaudu palvelimelle
     */
    function login($usermode=0) {
        $this->send("NICK ".$this->botNick);
        $this->send("USER ".$this->botUName." ".$usermode." * :".$this->botRName);
        $this->loggedin = true;
    }

    /**
     * Liity kanavalle
     */
    function join($channel=NULL) {
        if( $channel !== NULL ) {
            $this->channel = $channel;
        }
        $this->send("JOIN ".$this->channel);
    }

    /**
     * Kuuntelee kanavaa.
     */
    function listen() {

        if(!isset($this->irc_data)) {
            $this->irc_data =& new irc_data(&$this);
        }

        // Niin kauan kuin me olemme yhteydess�, kuuntele.
        while( $this->_state() ) {

            // Annetaan 2s aikaa silmukan ajoon.
            set_time_limit(2);

            // Send stuff
            $this->flushBuffer();

            // Jos ei kirjauduttu, kirjaudu.
            if($this->loggedin == false) {
                $this->login();
            }

            $sread = array($this->_connection);
            if(socket_select($sread, $w = null, $e = null, 0, $this->_delay*1000)) {

                $rawdata = trim(socket_read($this->_connection, 10240));

                // Littet��n edellinen ylij��nyt data nykyiseen dataan
                $rawdata = trim($this->irc_data->getLeftOvers().$rawdata);

                if(!empty( $rawdata )) {
                    // Liitet��n data ja otetaan ylij��nyt data talteen
                    $this->irc_data->append( $rawdata );

                    $this->irc_data->runTriggers();
                }
            }
        }
        $this->trace("Listen loop closed");
    }

    function part( $channel=NULL, $reason=NULL ) {
        if( $reason !== NULL ) {
            $reason = " :".$reason;
        }
        $this->send("PART $channel $reason");
    }

    function disconnect($msg="") {
        $this->send("QUIT $msg");
        sleep( $this->_delay*1000 );
        socket_shutdown($this->_connection);
        socket_close($this->_connection);
    }

    function pong($data) {
        // Ei k�ytet� viesti queuea
        $this->_send("PONG ".$data);
    }

    /**
     * L�hett�� perinteisen viestin.
     * Voidaan kutsua staattisesti, jos $irc on on rekister�ity sivulla,
     * ja kenelle viesti on osoitettu on asetettu.
     */
    function message($msg, $to=null) {

        //irc::trace("Yritet��n l�hett�� viesti�: ".$message);

        global $irc;
        if(is_a($this, "irc")) {
            if($to === null) $to = $this->channel;
            $message = "PRIVMSG ".$to." :".$msg;
            $this->send($message);
        } elseif( is_a($irc, "irc")) {
            if($to === null) $to = $irc->channel;
            $message = "PRIVMSG ".$to." :".$msg;
            $irc->send($message);
        }
    }

    function trace($msg="") {
        if( function_exists( "debug_backtrace" )) {
            $tstack = debug_backtrace();
            if( $tstack[0]['function'] == "trace" ) $tstack = array_slice($tstack, 1);
            $msg = $tstack[0]['class'].$tstack[0]['type'].$tstack[0]['function']."[".$tstack[0]['line']."]: ".$msg;
        }
        $msg = timer()." ".$msg;
        /*
        switch( $this->traceDrv ) {
            case IRC_TRACE_SEND :
                $this->message($msg);
                break;
            case IRC_TRACE_ECHO :
            default :
                echo $msg."\n";
                break;
        }
        */
        echo $msg."\n";
    }

    /**
     * Palauttaa yhteyden tilan.
     * @return bool true jos yhteys auki, false jos kiinni
     */
    function _state() {
        if( is_resource( $this->_connection ) &&
            get_resource_type( $this->_connection ) == "Socket" &&
            !socket_last_error( $this->_connection ) ) return true;
        $this->trace("Socket not in usable state");
        return false;
    }

    function _SigTerm() {
        $this->disconnect("Joku terminoi minut");
        die();
    }

    function _SigKill() {
        $this->disconnect("Joku tappoi minut");
        die();
    }

    function __destruct() {
        if( $this->_state() ) {
            $this->disconnect();
        } else {
            $this->trace();
        }
    }
}

/**
 * @class irc_trigger_plugin IRC trigger plugin luokka
 * T�m� luokka toimii jokaisen trigger pluginin pohjana
 */

class irc_trigger_plugin {
    /**
     * @var $line senhetkinen irc_data_line olion referenssi
     */
    var $line;

    /**
     * @var irc senhetkisen irc olion referenssi
     */
    var $irc;

    /**
     * @var $rules Laukaisuun johtavat s��nn�t
     */
    var $rules = array("expire" => 0,
                       "break"  => false);

    /**
     * @var $_lamdadriver plugin koodin functio
     */
    var $_lamdadriver;

    /**
     * Constructori, initialisoi lamdadriverin functioksi
     * @param $irc IRC olion referenssi
     * @param $code pluginin koodi
     */
    function irc_trigger_plugin(&$irc, $code) {
        $this->irc =& $irc;

        /* Ensimm�inen parametri on viittaus itseemme. Toinen muuttuja, $init
         * kertoo taas pluginille asetetaanko vain s��nn�t kuntoon */
        if($this->_lamdadriver = create_function('&$plugin, $init=false', $code)) {
            $this->initLamda();
        } else {
            return false;
        }
    }

    /**
     * PHP5 constructori. Kutsuu PHP4 constructorin
     */
    function __construct(&$irc, $code) {
        $this->irc_trigger_plugin(&$irc, $code);
    }

    /**
     * K�skee plugini� asettamaan parametrinsa (rules) kohdalleen
     */
    function &initLamda() {
        $lamda =& $this->_lamdadriver;
        return $lamda(&$this, true);
    }

    /**
     * Ajaa pluginin
     * Ajetaan jos s��nn�t t�sm�siv�t
     */
    function &trigger() {
        $lamda =& $this->_lamdadriver;
        $lamda(&$this, false);
    }

    function addRule($name, $rule) {
        $this->rules[$name] = $rule;
        return $name;
    }

    function &getRule($ruleName, $silent=false) {
        if(isset($this->rules[$ruleName])) return $this->rules[$ruleName];
        if($silent==false) irc::trace("No such rule {$ruleName}");
    }

    function &getRules() {
        return $this->rules;
    }

    function &setLine(&$line) {
        $this->line =& $line;
    }

    /**
     * Asettaa pluginin expiroitumaan
     */
    function expire($time=null) {
        if($time === null) {
            // asetetaan aika menneisyydest�.
            $this->addRule("expire", time()-1);
        }
    }
}

/**
 * @class irc_trigger_plugins Pluginit stack
 */
class irc_trigger_plugins {

    /**
     * @var $plugins Plugin stack.
     */
    var $plugins = array();

    var $pluginDir;

    var $irc;

    var $line;

    /**
     * Pluginit poistettavaksi
     */
    var $pluginsToRemoval = array();

    function irc_trigger_plugins(&$irc) {
        $this->irc =& $irc;
        $this->pluginDir = dirname(__FILE__)."/plugins";
        $this->scanPlugins();
    }

    function newPlugin($code, $name=null) {
        if($name===null) {
            $name = "pseudoPlugin";
        }
        if(isset($this->plugins[$name])) {
            irc::trace("Plugin nimell� $name on jo, annetaan uusi nimi");
            $i=0;
            $namebase = $name."_";
            $name = $namebase.$i;
            while(isset($this->plugins[$name])) {
                $i++;
                $name=$namebase.$i;
            }
        }
        $this->plugins[$name] =& new irc_trigger_plugin($this->irc, $code);
        irc::trace("Uusi plugin {$name} luotu");
        return $name;
    }

    function scanPlugins() {
        if(!is_readable($this->pluginDir) || !is_dir($this->pluginDir)) {
            irc::trace("Plugindir {$this->pluginDir} is not readable");
            return false;
        }
        if($PDHandle = opendir($this->pluginDir)) {
            while (false !== ($file = readdir($PDHandle))) {
                if(is_dir($this->pluginDir."/".$file)) continue;
                if(!is_readable($this->pluginDir."/".$file)) continue;
                // Jos ei p��ty .php niin hyp�t��n ohi
                if( substr($file, -4) != ".php" ) continue;

                $this->registerPluginFile($this->pluginDir."/".$file);

            }
            closedir($PDHandle);
            irc::trace("Registered ".count($this->plugins)." plugins");
        } else {
            irc::trace("Could not open plugindir");
        }
    }

    function registerPlugin($pluginname) {
        $pluginfile = $this->pluginDir."/".basename($pluginname).".php";
        if(!file_exists($pluginfile)) {
            return false;
        }
        return $this->registerPluginFile($pluginfile);
    }

    function registerPluginFile($pluginFile) {
        $pluginName = basename($pluginFile);
        $pluginName = substr($pluginName, 0, strlen($pluginName)-4);
        $read = implode("\n", file($pluginFile));

        // Irrota PHPn tagit jos on.
        if( preg_match("/(<\?php|<\?)(.*)\?>/si", $read, $readPreg )) {
            irc::trace("PHP tags found in plugin {$pluginName}");
            $read = $readPreg[2];
        }

        irc::trace("Registering plugin {$pluginName}");
        $this->plugins[$pluginName] =& new irc_trigger_plugin($this->irc, $read);
        if(is_a($this->plugins[$pluginName], "irc_trigger_plugin")) {
            irc::trace("Plugin {$pluginName} registered succesfully");
            return true;
        } else {
            irc::trace("Plugin {$pluginName} NOT registered");
            return false;
        }
    }

    function &triggerLine( &$line ) {
        $this->line =& $line;
    }

    function removePlugin($plugin) {
        if(!isset($this->plugins[$plugin])) {
            irc::trace("Ei voida poistaa pluginia; Pluginia {$plugin} ei l�ydy");
            return false;
        } elseif ( in_array($plugin, $this->pluginsToRemoval)) {
            irc::trace("Ei voida poistaa pluginia; Plugin {$plugin} lis�tty jo poistettavaksi");
            return false;
        } else {
            $this->pluginsToRemoval[] = $plugin;
            return true;
        }
    }

    function unregisterPlugin($plugin) {
        if(!isset($this->plugins[$plugin])) {
            irc::trace("Ei voida poistaa pluginia; Pluginia {$plugin} ei l�ydy");
            return false;
        }
        unset($this->plugins[$plugin]);
        return true;
    }

    function _unregisterScheudledPlugins() {
        foreach($this->pluginsToRemoval as $key => $plugin) {
            irc::trace("Poistetaan plugin {$plugin}:{$key}");
            $this->unregisterPlugin($plugin);
            unset($this->pluginsToRemoval[$key]);
        }
    }

    /**
     * K�y plugin stackin l�pi ja suorittaa soveliaat pluginit;
     */
    function newEvent( &$line ) {
        if( $line !== null ) {
            $this->triggerLine(&$line);
        }

        // Poistetaan wanhat pluginit ensin...
        $this->_unregisterScheudledPlugins();

        foreach($this->plugins as $pluginName => $plugin) {
            $validPlugin = true;

            // Ajaa rulesien tarkistukset
            $rules = $this->plugins[$pluginName]->getRules();
            foreach( $rules as $key => $param ) {
                if( $key == "break" ) continue;

                if( $key == "expire" && $param > 0 && $param < time() ) {
                    irc::trace("Pluginin {$pluginName} expire t�ynn�");
                    $this->removePlugin($pluginName);
                    $validPlugin = false;
                    break;
                } elseif( $key == "expire" ) continue;

                // Vertailee onko prefix t�sm��v�
                if( $key == "prefix" ) {
                    if( substr($this->line->msg, 0, strlen($param)) != $param) {
                        $validPlugin = false;
                    }
                    continue;
                }

                if( $this->line->$key != $param ) {
                    $validPlugin = false;
                }
            }
            if( $validPlugin == true ) {
                irc::trace("Plugin \"{$pluginName}\" matched");
                // Aseta dataline vastaamaan
                $this->plugins[$pluginName]->setLine($this->line);
                // Aja plugin
                $this->plugins[$pluginName]->trigger();
                // Katkaise suoritus jos plugin niin haluaa.
                if($this->plugins[$pluginName]->getRule('break', true) == true) break;
            }
        }

    }
}

