<?php

class irc_data {

    var $rawdata;

    var $message;

    var $lines;

    var $i=0;

    var $lastExecLine=0;

    function append($rawdata) {
        // Muutetaan windows enterit UNIX enteriksi.
        $data = str_replace("\r", '', $rawdata);
        $this->rawdata = $data;

        $lines = explode("\n", $this->rawdata );

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
        global $irc_triggers;
        while( $this->lastExecLine < $this->i ) {
            $irc_triggers->event($this->lines[$this->lastExecLine]);
            $this->lastExecLine++;
        }
    }

    /**
     * Tämä functio palauttaa viimeisen rivin.
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

    var $msg;

    var $ping = false;

    function irc_data_line($line) {
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

        $this->code  = $exs[1];

        $this->from  = $exs[0];
        $this->nick  = substr($exs[0], 0, $poe);
        $this->host  = substr($exs[0], $poa+1);
        $this->ident = substr($exs[0], $poe+1, ($poa-$poe)-1);
        $this->msg   = trim(substr($this->data, $poc+1));
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

// Tarvitaan signal handlerille
declare(ticks = 1);

class irc {

    // Serveri, jolle liitytään
    var $server     = "irc.quakenet.org";

    // Serverin portti.
    var $port       = "6667";

    // Kanavan nimi, jolle liitytään.
    var $channel    = "#rautakuu";

    // Botin henk. koht. tietoja.
    var $botRName   = "Mini Me, completes me!";
    var $botNick    = "MiniTeemu";
    var $botUName   = "rautakuu";

    // Kuinka monta kertaa yritetään yhdistää ennen kuin annetaan periksi.
    var $tries = 5;

    // Yhteys resurssi
    var $_connection;

    // Kuinka kauan odotetaan tapahtumahorisonttia?
    var $_delay = 100;

    // Bufferi joka sisältää kaiken saamamme datan.
    var $_loggedin = false;

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
        pcntl_signal(SIGTERM, array(&$this,"_SigTerm"));
        pcntl_signal(SIGKILL, array(&$this,"_SigKill"));

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
        static $irc_data;
        $partialData = "";
        while( $this->_state() ) {
            // Nukutaan hetki
            usleep( $this->_delay*1000 );

            // Jos ei kirjauduttu, kirjaudu.
            if($this->loggedin == false) {
                $this->login();
                $this->loggedin = true;
            }

            $rawdata = trim(fread($this->_connection, 10240));

            if(!empty( $rawdata )) {
                // Liitetään viimeksi ylijäänyt data nyt käsiteltävään dataan.
                if(!isset($irc_data)) {
                    $irc_data =& new irc_data();
                }
                $irc_data->append( $rawdata.$partialData );
                $this->trace("Data liitetty");

                $irc_data->runTriggers();

                //$partialData = $irc_data->getLastLine();
                $partialData="";
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
        if($to === null) $to = $this->channel;

        $message = "PRIVMSG ".$to." :".$msg;

        //irc::trace("Yritetään lähettää viestiä: ".$message);

        global $irc;
        if(is_a($this, "irc")) {
            $this->send($message);
        } elseif( is_a($irc, "irc")) {
            $irc->send($message);
        }
    }

    function trace($msg="") {
        if( function_exists( "debug_backtrace" )) {
            $tstack = debug_backtrace();
            if( $tstack[0]['function'] == "trace" ) $tstack = array_slice($tstack, 1);
            $msg = $tstack[0]['class'].$tstack[0]['type'].$tstack[0]['function']."[".$tstack[0]['line']."]: ".$msg;
        }
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
 * base Luokka joka yrittää hanskata mitä datalla tehdään
 */
class irc_triggers_base {

    /**
     * Triggerit ja niiden säännöt
     */
    var $triggers;

    var $line;

    function irc_triggers_base() {
        // Rekisteröidään Ping vastaus.
        $this->registerTrigger(array('ping'  => true,
                                     'event' => array(&$this,'pong')));

        // Rekisteröidään liitty-ensin vastaus

        $this->registerTrigger(array('code'  => '451',
                                     'break' => true,
                                     'event' => array(&$this,'login')));

        // Welcome koodi.
        $this->registerTrigger(array('code'  => '001',
                                     'break' => true,
                                     'event' => array(&$this,'channel')));
    }

    function __construct() {
        $this->irc_triggers_base();
    }

    /**
     * Lisää triggerin trigger kasaan
     * @param $rules triggerin säännöt
     * @param $offset -1 nostaa päällimäiseksi, 0 seuraavaksi ja sitä suuremmat sijainnin
     */
    function registerTrigger( $rules, $offset=0 ) {
        if( $offset == 0 ) {
            $this->triggers[] = $rules;
        } else {
            $this->triggers[$offset] = $rules;
        }
    }


    function &triggerLine( &$line ) {
        $this->line =& $line;
    }

    function event( &$line ) {
        if( $line !== null ) {
            $this->triggerLine(&$line);
        }

        foreach($this->triggers as $trigger) {
            $valid = true;
            // Testaa sääntöjen paikkaansa pitävyyden.
            foreach( $trigger as $key => $param ) {
                if( $key == "event" ) continue;
                if( $key == "break" ) continue;

                if( $this->line->$key != $param ) {
                    //irc::trace("Avain $key ei vastaa arvoa $param");
                    $valid = false;
                    break;
                }
            }

            if ($valid == true) {
                // Katsotaan, voiko tapahtumaa suorittaa
                if( is_array( $trigger['event'] )) {
                    call_user_method($trigger['event'][1], $trigger['event'][0]);
                } else {
                    if(function_exists($trigger['event'])) {
                        call_user_method($trigger['event']);
                    } else {
                        irc::trace("Rekisteröity event ei kutsuttavissa: ".print_r($trigger['event'], 1));
                    }
                }
                // Katkaistaanko sääntöketjun tarkistus?
                if ( $trigger['break'] == true ) break;
            }
        }
    }


    function pong() {
        global $irc;
        irc::trace("Ping? Pong! ".$this->line->getLine());
        $irc->pong(substr( $this->line->getLine(), 5));
    }

    function login() {
        global $irc;
        irc::trace("451; Rekisteröidy ensin.");
        $irc->login();
    }

    function channel() {
        global $irc;
        irc::trace("Liitytään kanavalle");
        $irc->join();
    }
}

/**
 * Oma event trigger homma
 */
class irc_triggers extends irc_triggers_base {
    function irc_triggers() {
        $this->irc_triggers_base();

        // MOTD
        $this->registerTrigger(array('code'  => '372',
                                     'break' => true,
                                     'event' => array(&$this,'motd')));

    }

    function __construct() {
        $this->irc_triggers();
    }

    /**
     * Kerää palvelimen MOTDn.
     */
    function motd() {
        static $motd;
        if(!isset($motd)) $motd = "";
        $motd .= $this->line->get("msg");
        return $motd;
    }

}

// Satunnaisia testifunctioita

class irc_triggers_test extends irc_triggers {
    function irc_triggers_test() {
        $this->irc_triggers();

        // MOTD
        $this->registerTrigger(array('code'  => 'PRIVMSG',
                                     'nick'  => 'IsoTeemu',
                                     'msg'   => 'MiniMe, uptime',
                                     'break' => true,
                                     'event' => array(&$this,'getUptime')));
        $this->registerTrigger(array('code'  => 'PRIVMSG',
                                     'nick'  => 'IsoTeemu',
                                     'msg'   => 'MiniMe, memusage',
                                     'break' => true,
                                     'event' => array(&$this,'getMemUsage')));

        $this->registerTrigger(array('code'  => 'PRIVMSG',
                                     'event' => array(&$this,'notMaster')));
        $this->registerTrigger(array('code'  => 'MODE',
                                     'event' => array(&$this,'didIGotOP')));
        $this->registerTrigger(array('code'  => 'JOIN',
                                     'event' => array(&$this,'opTeemu')));

    }

    function __construct() {
        $this->irc_triggers_test();
    }

    function getUptime() {
        global $irc;
        $irc->message(trim(exec("uptime")));
    }

    function getMemUsage() {
        global $irc;
        if(! function_exists("memory_get_usage")) {
            $irc->message("Herrani, en voi täyttää pyyntöäsi");
            return false;
        }
        $irc->message(memory_get_usage());
    }

    function notMaster() {
        if(strstr($this->line->get("msg"), "MiniMe," )) {
            global $irc;
            $irc->message("Hyvä herra ".$this->line->get("nick").", et vaikuta isännältäni, enkä suostu pyyntöösi");
        }
    }

    function didIGotOP() {
        global $irc;

        if( strstr($this->line->getLine(), "+o ".$irc->botNick)) {
            $irc->message("Kiitos rakas ".$this->line->get("nick"));
        }
    }

    function opTeemu() {
        global $irc;
        if( $this->line->get("nick") == "IsoTeemu" ) {
            $irc->send("MODE ".$irc->channel." +o ".$this->line->get("nick"));
        }
    }
}


$irc_triggers =& new irc_triggers_test();

$irc =& new irc();
$GLOBALS['irc'] =& $irc;

irc::trace("Yhdistetään...");
$irc->connect();
irc::trace("Kuunnellaan...");
$irc->listen();


?>