<?php

define("MINITEEMU", __FILE__);

if( function_exists("putenv")) putenv('LANG="fi_FI.UTF-8');
if( function_exists("iconv_set_encoding") ) iconv_set_encoding("output_encoding", "UTF-8");
if(function_exists("mb_internal_encoding") ) mb_internal_encoding("UTF-8");

ini_set("default_charset", "uft-8");
ini_set("mbstring.encoding_translation", "on");

include("log.class.inc.php");

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
 * Kuinka monta riviä voi containerissa olla kerrallaan säilössä.
 */
define("IRC_DATA_LINES_MAX_COUNT", 500);
/**
 * Kuinka monta riviä poistetaan kerralla kun containerissa on IRC_DATA_LINES_MAX_COUNT riviä.
 */
define("IRC_DATA_LINES_RM_COUNT", 250);

class irc_data {

    // Käsiteltävä data
    var $data;

    // Ylijäänyt osa
    var $leftOver = "";

    // Rivi objectit
    var $lines = array();

    // Kokonais rivien määrä
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

        // Jos dataa jää yli, yritettän se ottaa talteen.
        if(( $strrpos = strrpos($data ,"\n")) !== false ) {
            $this->leftOver = substr($this->data, $strrpos+1);
            irc::trace("LEFTOVER DATA: {$this->leftOver}");

            // Otetaan oleellinen osa, ja jätetään yliäänyt osa pois.
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
                // Line ei ollut hyväksyttävä. Pudotetaan se.
                irc::trace("Pudotetaan rivi {$this->i}. Ei valid()");
                unset($this->lines[$this->i]);
                continue;
            }

            $this->i++;
        }

        if($this->numLines() >= IRC_DATA_LINES_MAX_COUNT) {
            irc::trace("Cleanup. Saavutettu ".IRC_DATA_LINES_MAX_COUNT." rivin määrä");

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
     * palauttaa ylijääneen osan.
     * @param $nuke hävitetäänkö ylijäänyt osa.
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
     * Tämä palauttaa viimeisen rivin.
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
    var $error = false;

    var $timestamp;

    function irc_data_line($line) {

        $this->timestamp = timer();

        irc::trace("Saatu dataa:\n<< {$line}");
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
            $this->error = true;
        } elseif( substr( $line, 0, 1 ) == ":" ) {
            $this->valid = true;
        } else {
            $this->valid = false;
        }
        return $this->valid;
    }

    function parseLine() {

        switch(substr($this->data,0,6)) {
            case "PING :" :
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

        // WHOIS kertoo neljäntenä parametrinä nickin
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

    // Serveri, jolle liitytään
    var $server     = "fi.quakenet.org";

    // Serverin portti.
    var $port       = "6667";

    // Botin henk. koht. tietoja.
    var $botRName   = "Mini Me, completes me!";
    var $botNick    = "MiniTeemu";
    var $botUName   = "rautakuu";

    // Kuinka monta kertaa yritetään yhdistää ennen kuin annetaan periksi.
    var $tries      = 5;

    // Shall we reconnect on ERROR
    var $reconnect  = true;

    // How long to try connect before timeout? in secs
    var $_timeout   = 10;

    // Yhteys resurssi
    var $_connection;

    // Kuinka kauan odotetaan tapahtumahorisonttia?
    var $_delay     = 100;

    // IRC data container
    var $irc_data;

    // Bufferi joka sisältää kaiken saamamme datan.
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
            if( $config['server'] )     $this->server   = $config['server'];
            if( $config['port'] )       $this->port     = $config['port'];
        }

        // Huuhdellaan välittömästi
        ob_implicit_flush(true);

        // Ei aikarajaa vielä
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

    // Hoitaa viestin lähetyksen IRC serverille
    function send( $msg ) {
        if( $this->_state() === false ) {
            $this->trace("Yhteyttä ei ole");
            return false;
        }
        $this->buffer[] = $msg;
        return count($this->buffer);
    }

    function _send($msg) {
        if(! socket_write($this->_connection, $msg."\r\n")) {

            $this->trace("Viestin lähetys epäonnistui \"{$msg}\"");
            return false;
        }
        $this->trace("Viesti lähetetty\n>> {$msg}");
        return true;
    }

    function flushBuffer() {
        if(count($this->buffer) < 1) return true;
        $this->_send($this->buffer[0]);
        array_shift($this->buffer);
        return true;
    }

    /**
     * Yhdistää palvelimelle
     */
    function connect() {
        // Yritetään loopata yhteyttä
        if(!$this->_connect()) return false;
        if(!$this->login($usermode=0)) return false;
        return true;
    }

    /**
     * Kirjaudu palvelimelle
     */
    function login($usermode=0) {
        if(!$this->_state()) return false;
        $this->send("NICK ".$this->botNick);
        $this->send("USER ".$this->botUName." ".$usermode." * :".$this->botRName);
        $this->loggedin = true;
    }

    /**
     * Liity kanavalle
     */
    function join($channel) {
        if(!$this->loggedin) $this->login();
        $this->send("JOIN $channel");
    }

    /**
     * Kuuntelee kanavaa.
     */
    function listen() {

        if(!isset($this->irc_data)) {
            $this->irc_data =& new irc_data(&$this);
        }

        if(!$this->_state()) {
            $this->trace("Socket not usable; Can't listen");
            return false;
        }

        // Lets try a new thing...
        //socket_set_nonblock($this->_connection);

        // Niin kauan kuin me olemme yhteydessä, kuuntele.
        while( $this->_state() ) {

            // Annetaan 2s aikaa silmukan ajoon.
            //set_time_limit(1);

            // Send stuff
            $this->flushBuffer();

            // Jos ei kirjauduttu, kirjaudu.
            if($this->loggedin == false) {
                $this->login();
            }

            $sock = array($this->_connection);
            if(socket_select($sock, $sock, $sock, 0, $this->_delay*1000)) {

                $rawdata = socket_read($this->_connection, 10240);
		irc::trace("Received data");
                if($rawdata === false) {
                    $this->trace("Something went wrong, socket returned false");
                    $this->_state(false);
                }

                // Littetään edellinen ylijäänyt data nykyiseen dataan
                $rawdata = trim($this->irc_data->getLeftOvers().trim($rawdata));

                if(!empty( $rawdata )) {
                    // Liitetään data ja otetaan ylijäänyt data talteen
                    $this->irc_data->append( $rawdata );

		    irc::trace("Appended data, getting last line");

                    /**
                     * @todo Make this better
                     */
                    $lastline = $this->irc_data->getLastLine();
                    if($lastline->error == true) {
                        $this->trace("Got error line: ".$line->data);
                        if($this->reconnect) {
			    irc::trace("Reconnecting to server");
                            $this->reconnect();
                            continue;
                        } else {
                            return false;
                        }
                    }

		    irc::trace("Running triggers");
                    $this->irc_data->runTriggers();
                }

                if(!isset($lastping)) {
                    $lastping = $this->ping();
                } elseif(count($this->buffer) == 0 && (timer() - $this->ping($lastping)) > 60) {
                    // If there is no outgoing messages, sleep a while
                    // and send a ping request to get some action.
                    $foo = timer() - $this->ping($lastping,true);
                    $this->trace(" -!- Ping for :".$lastping." - ".$foo);

                    usleep( $this->_delay*1000);
                    $lastping = $this->ping();
                }
            } else {
                $this->trace("Could not select socket: ".socket_strerror(socket_last_error()));
                break;
            }
        }
        $this->trace("Listen loop closed");
    }

    function part( $channel, $reason=NULL ) {
        if( $reason !== NULL ) {
            $reason = " :".$reason;
        }
        $this->send("PART $channel $reason");
    }

    function disconnect($msg="") {
        $this->send("QUIT $msg");
        while(count($this->buffer) > 0) {
            $this->flushBuffer();
            usleep( $this->_delay*1000 );
        }
        socket_shutdown($this->_connection);
        socket_close($this->_connection);
    }

    function reconnect($msg="") {
        $this->disconnect();
        $this->connect();
    }

    function pong($data) {
        // Ei käytetä viesti queuea
        $this->_send("PONG ".$data);
    }

    /**
     * Ping server.
     * If $id is set, checks for ping latency
     */
    function ping($id=null,$clean=false) {
        static $pings;

	// Garbage collection
	if(count($pings)) {
		foreach($pings as $key => $val) {
			if((timer()-$val) > 10) {
				irc::trace('Collecting PING '.$key.' garbage...');
				unset($pings[$key]);
			}
		}
	}
        if($id!==null) {
            if(isset($pings[$id])) {
                $r = $pings[$id];
                if($clean) unset($pings[$id]);
            } else
                $r = timer();
            return $r;
        }
        // Generate new ping
        $id = uniqid();
        $this->send("PING :".$id);
        $pings[$id] = timer();
        $this->trace("Luodaan uusing PING kutsu $id");
        return $id;
    }

    /**
     * Lähettää perinteisen viestin.
     * Voidaan kutsua staattisesti, jos $irc on on rekisteröity sivulla,
     * ja kenelle viesti on osoitettu on asetettu.
     * @param $msg lähetettävä viesti
     * @param $to kenelle viesti lähetetään
     */
    function message($msg, $to) {

        //irc::trace("Yritetään lähettää viestiä: ".$message);

        global $irc;
        if(is_a($this, "irc")) {
            $message = "PRIVMSG ".$to." :".$msg;
            $this->send($message);
        } elseif( is_a($irc, "irc")) {
            $message = "PRIVMSG ".$to." :".$msg;
            $irc->send($message);
        }
    }

    function trace($msg="") {
        
        //log::trace($msg);

        if( function_exists( "debug_backtrace" )) {
            $tstack = debug_backtrace();
            if( $tstack[0]['function'] == "trace" ) $tstack = array_slice($tstack, 1);
            $msg = $tstack[0]['class'].$tstack[0]['type'].$tstack[0]['function']."[".$tstack[0]['line']."]: ".$msg;
        }

	if(function_exists("memory_get_usage")) {
	  $msg = '(M:'.memory_get_usage().') '.$msg; 
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

    function _connect() {
        $i = 0;
        while( $i < $this->tries ) {

            $i++;
            $this->trace("Yritetään yhdistää #".($i));
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
                $this->trace("Problems on connecting: to ".$this->server.":".$this->port." - ".socket_strerror($err));
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
     * Palauttaa yhteyden tilan.
     * @return bool true jos yhteys auki, false jos kiinni
     */
    function _state($newstate=true) {
        static $state;
        if(!isset($state)) $state = true;
        if($newstate !== true) {
            $this->trace(sprintf("state set to %", $newstate));
            $state = $newstate;
            return $state;
        } elseif( $state !== true ) {
            return $state;
        }

        if(! is_resource( $this->_connection )) {
            $this->trace("Connection not resource");
            return false;
        } elseif( ($_type = get_resource_type( $this->_connection )) != "Socket" ) {
            $this->trace("Connection type not socket: $_type");
            return false;
        } elseif ($_error = socket_last_error( $this->_connection )) {
            $this->trace("Connection socket error: $_error ".socket_strerror($_error));
            return false;
        } elseif (false === socket_select($r = array($this->_connection), $w = array($this->_connection), $f = array($this->_connection), 0)) {
            $this->trace("Socket select error: ".socket_strerror(socket_last_error()));
            return false;
        }

        return true;
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
 * Tämä luokka toimii jokaisen trigger pluginin pohjana
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
     * @var $rules Laukaisuun johtavat säännöt
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

        /* Ensimmäinen parametri on viittaus itseemme. Toinen muuttuja, $init
         * kertoo taas pluginille asetetaanko vain säännöt kuntoon */
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
     * Käskee pluginiä asettamaan parametrinsa (rules) kohdalleen
     */
    function &initLamda() {
        $lamda =& $this->_lamdadriver;
        return $lamda(&$this, true);
    }

    /**
     * Ajaa pluginin
     * Ajetaan jos säännöt täsmäsivät
     */
    function &trigger() {
        $lamda =& $this->_lamdadriver;
        return $lamda(&$this, false);
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
            // asetetaan aika menneisyydestä.
            $this->addRule("expire", time()-1);
        }
    }

    /**
     * Reply to message whit privmsg
     * This is wrong place to place this, more "correct" would
     * be in $this->line->message(), but this is simpler to get
     * it working
     *
     * @param $message message to send
     */
    function message($msg, $to=null) {
        if($to === null) {
            if($this->line->channel != $this->irc->botNick) {
                $to =& $this->line->channel;
            } elseif(!empty($this->line->nick)) {
                $to =& $this->line->nick;
            } else {
                irc::Trace("WaTaFa? could not get whom to send message");
                return false;
            }
        }
        return $this->irc->message($msg, $to);
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
            irc::trace("Plugin nimellä $name on jo, annetaan uusi nimi");
            $i=0;
            $namebase = $name."_";
            $name = $namebase.$i;
            while(isset($this->plugins[$name])) {
                $i++;
                $name=$namebase.$i;
            }
            if(function_exists("php_check_syntax")) {
                $synerror = "";
                if(!php_check_syntax($code, $synerror)) {
                    irc::Trace("Syntax error in plugin: ".$name.":".$synerror);
                    return false;
                }
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
                // Jos ei pääty .php niin hypätään ohi
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
            irc::trace("Ei voida poistaa pluginia; Pluginia {$plugin} ei löydy");
            return false;
        } elseif ( in_array($plugin, $this->pluginsToRemoval)) {
            irc::trace("Ei voida poistaa pluginia; Plugin {$plugin} lisätty jo poistettavaksi");
            return false;
        } else {
            $this->pluginsToRemoval[] = $plugin;
            return true;
        }
    }

    function unregisterPlugin($plugin) {
        if(!isset($this->plugins[$plugin])) {
            irc::trace("Ei voida poistaa pluginia; Pluginia {$plugin} ei löydy");
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
     * Käy plugin stackin läpi ja suorittaa soveliaat pluginit;
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
                    irc::trace("Pluginin {$pluginName} expire täynnä");
                    $this->removePlugin($pluginName);
                    $validPlugin = false;
                    break;
                } elseif( $key == "expire" ) continue;

                // Vertailee onko prefix täsmäävä
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
		if(!is_object($this->plugins[$pluginName])) {
		  irc::trace("Plugin {$pluginName} ei ole objekti. Ou-to-a. Dumppi:");
		  irc::trace(print_r($this->plugins,1));
		  continue;
		}
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

function writeDebugInfo() {
    static $done;
    if(isset($done)) return true;
    $filename = dirname(__FILE__)."/debug.log";
    echo "Writing debug info to $filename\n";
    touch($filename);
    $fd=fopen($filename, "w+");
    fwrite($filename, print_r(log::dumpTrace(),1));
    fclose($filename);
    $done = true;
    return true;
}

if(!register_shutdown_function("writeDebugInfo")) {
    echo "Could not register shutdown function\n";
}
