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
            $this->lines[$this->i] =& new irc_data_line( array_shift($lines) );
            // Line ei ollut hyväksyttävä. Pudotetaan se.
            if(!$this->lines[$this->i]->valid()) {
                unset($this->lines[$this->i]);
                continue;
            }

            $this->i++;
        }

        // Huono koodi. Parempi cleanup tapa täytyy tehdä.
        if(($offrows = $this->numLines()) > 500 ) {
            irc::trace("Yli 500 rivin määrä täyttynyt.");
            $this->lines = array_slice($this->lines, $offrows-500);
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
    var $server     = "port80.se.quakenet.org";

    // Serverin portti.
    var $port       = "6667";

    // Kanavan nimi, jolle liitytään.
    var $channel    = "#rautakuu";

    // Botin henk. koht. tietoja.
    var $botRName   = "Mini Me, completes me!";
    var $botNick    = "MiniTeemu";
    var $botUName   = "rautakuu";

    // Kuinka monta kertaa yritetään yhdistää ennen kuin annetaan periksi.
    var $tries      = 5;

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

        // Huuhdellaan välittömästi
        ob_implicit_flush(true);

        // Ei aikarajaa
        @set_time_limit(0);

        // PHP4 __destructori emulaatio
        if( version_compare( phpversion(), "5.0.0", "<") == 1 ){
            register_shutdown_function(array(&$this,"__destruct"));
        }

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
        if(! fwrite($this->_connection, $msg."\r\n")) {

            $this->trace("Viestin lähetys epäonnistui \"{$msg}\"");
            return false;
        }
        $this->trace("Viesti lähetetty\n>> \"{$msg}\"");
        return true;
    }

    /**
     * Yhdistää palvelimelle
     */
    function connect() {
        // Yritetään loopata yhteyttä
        $i = 0;
        while( $i < $this->tries ) {

            $i++;
            $this->trace("Yritetään yhdistää #".($i));
            $this->_connection = fsockopen($this->server, $this->port, $errno, $errstr);

            if( $this->_connection === false ) {
                $this->trace("Ei saatu yhdistettyä palvelimelle \"{$this->server}:{$this->port}\". Syy: {$errstr} ({$errno})");
                continue;
            } else {
                break;
            }
        }
        if( $this->_connection === false ) {
            $this->trace("Yhteyttä ei saatu muodostettua");
            return false;
        }

        socket_set_blocking($this->_connection, false);
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
            $this->irc_data =& new irc_data();
            $this->irc_data->irc =& $this;
        }

        // Niin kauan kuin me olemme yhteydessä, kuuntele.
        while( $this->_state() ) {
            // Nukutaan hetki
            usleep( $this->_delay*1000 );

            // Jos ei kirjauduttu, kirjaudu.
            if($this->loggedin == false) {
                $this->login();
            }

            $rawdata = trim(fread($this->_connection, 10240));

            // Littetään edellinen ylijäänyt data nykyiseen dataan
            $rawdata = trim($this->irc_data->getLeftOvers()).$rawdata;

            if(!empty( $rawdata )) {
                // Liitetään data ja otetaan ylijäänyt data talteen
                $this->irc_data->append( $rawdata );

                $this->irc_data->runTriggers();
            }
        }
    }

    function part( $channel=NULL, $reason=NULL ) {
        if( $reason !== NULL ) {
            $reason = " :".$reason;
        }
        $this->send("PART $channel $reason");
    }

    function disconnect($msg="") {
        $this->send("QUIT $msg");
    }

    function pong($data) {
        $this->send("PONG ".$data);
    }

    /**
     * Lähettää perinteisen viestin.
     * Voidaan kutsua staattisesti, jos $irc on on rekisteröity sivulla,
     * ja kenelle viesti on osoitettu on asetettu.
     */
    function message($msg, $to=null) {

        //irc::trace("Yritetään lähettää viestiä: ".$message);

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
            get_resource_type( $this->_connection ) == "stream" ) return true;
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
    var $rules = array();

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

    function irc_trigger_plugins(&$irc) {
        $this->irc =& $irc;
        $this->pluginDir = dirname(__FILE__)."/plugins";
        $this->scanPlugins();
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

    /**
     * Käy plugin stackin läpi ja suorittaa soveliaat pluginit;
     */
    function newEvent( &$line ) {
        if( $line !== null ) {
            $this->triggerLine(&$line);
        }

        foreach($this->plugins as $pluginName => $plugin) {
            $validPlugin = true;

            // Ajaa rulesien tarkistukset
            $rules = $this->plugins[$pluginName]->getRules();
            foreach( $rules as $key => $param ) {
                if( $key == "break" ) continue;

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

$irc =& new irc();
$GLOBALS['irc'] =& $irc;

irc::trace("Yhdistetään...");
$irc->connect();
irc::trace("Kuunnellaan...");
$irc->listen();
