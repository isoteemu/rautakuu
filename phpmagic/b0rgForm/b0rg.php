<?php
///
/// The tärkein osa
///
// Kenelle kaava lähetettään?

$email = "teemu@rautakuu.org";

/**
 * Käyttöohje:
 *
 * sisällytä tämä scripti javascriptinä:
 *  <script src="b0rg.php"></script>
 * Tee tyyliksi requiredMissing parametri, joka asetetaan jos kenttä uupuu:
 *  .requiredMissing { background-color: red; }
 * Jokaiseen pakolliseen kenttään pitää laittaa rel="required" parametri:
 *  <input type="text" name="foo" rel="required" />
 * Jos halutaan heittää jollekkin "kiitos" sivulle, pitää se antaa inputissa:
 *  <input type="hidden" name="redir" value="kiitos.html" />
 * ja viimeisenä, formin actioniksi tämä scripti:
 *  <form method="post" action="b0rg.php">...</form>
 */

///
/// DAS CODE
///

header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

session_name("b0rgForm");
session_start();

function toJSarray(&$array) {

    $b0rgArray = "new Array(";
    foreach($array as $needed) {
        $b0rgArray .= '"'.addslashes($needed).'",';
    }
    $b0rgArray = substr($b0rgArray, 0, strlen($b0rgArray)-1).")";
    return $b0rgArray;
}

function message($msg = null) {
    static $viesti;
    if(!isset($viesti)) $viesti = "";
    if($msg) $viesti .= $msg."\n";
    return $viesti;
}

// Jos ei ole käytetty postia, ulosta javascript
if(!$_POST) {
    header("Content-Type: text/javascript; charset=utf-8");

    if($_SESSION['_b0rg']) {
        echo "var b0rgRequired = ".toJSarray($_SESSION['_b0rg']).";\n";
        unset($_SESSION['_b0rg']);
        $_SESSION['_b0rgmessage'] = "Kaikkia tarvittavia kenttiä ei ollut laitettu :(";
        /// TODO: ulosta vanhat arvot.
    }

    if($_SESSION['_b0rgmessage']) {
        echo "var b0rgMessage = \"".addslashes($_SESSION['_b0rgmessage'])."\";\n";
        unset($_SESSION['_b0rgmessage']);
    }
?>
// Sisäinen viittaaja
var b0rgForm;

function checkForm(submitti) {
    // Haetaan kaikki input kentät, joilla on rel="required"
    var inputit = b0rgForm.getElementsByTagName("input");

    // Array niistä kentistä, joitka on vaadittuja.
    var required = new Array();

    for (var i=0; i<inputit.length; i++){
        var inputti = inputit[i];
        if( inputti.getAttribute("rel") == "required" ) {
            required.push(inputti.name);
        }
        if (inputti.getAttribute("rel") == "required" && inputti.value == "" ){
            if(!window.firstMissing) {
                var firstMissing = inputti
            }
            inputti.setAttribute("class","requiredMissing");
        } else if(inputti.getAttribute("class") == "requiredMissing") {
            inputti.setAttribute("class","");
        }
    }

    // Jos jokin kenttä uupuu, siirrä focus siihen
    if(window.firstMissing) {
        alert("Tarkista kenttä "+firstMissing.name+"!");
        firstMissing.focus();
        return false;
    } else {
        // Luodaan piilokentät vaadituista
        if(required) {
            for (var i=0; i<required.length; i++){

                var b0rgInput = document.createElement("input");
                b0rgInput.setAttribute("name","_b0rg[]");
                b0rgInput.setAttribute("type","hidden");
                b0rgInput.value = required[i];
                b0rgForm.appendChild(b0rgInput);
            }
        }

        // A-OK, lähettään kaava.
        b0rgForm.submit();
        submitti.disabled = true;
        return true;
    }
}

function quickCheck(inputti) {
    if( inputti.getAttribute("rel") == "required" && inputti.value == "" ) {
        inputti.setAttribute("class","requiredMissing");
        return false;
    } else if(inputti.getAttribute("class") == "requiredMissing") {
        inputti.setAttribute("class","");

        /*
        // käydään kaikki napit läpi, ja tarkastetaan. Jos ei ole tyhjiä,
        var inputit = b0rgForm.getElementsByTagName("input");
        for (var i=0; i<inputit.length; i++){
            var inputti = inputit[i];
            if(inputti.getAttribute("type") == "submit" && ! submitti) {
                var submitti = inputti;
            }
            if (inputti.getAttribute("rel") == "required" && inputti.value == "" ){
                AOK = false;
            }
        }
        if(AOK && submitti) {
            submitti.disabled = false;
            return true;
        }
        return false;
        */
    }
    return true;
}

function _getForm() {
    return document.getElementsByTagName("form")[0];
}


// Ajetaan scriptin alustuksessa
function initB0rg() {
    if (!document.getElementsByTagName){ return; }

    // Aseta b0rgForm muuttuja viitaamaan kaavaan.
    if(!b0rgForm) {
        // Hae eka kaava. Hanskataan vain ja ainoastaan eka.
        b0rgForm = _getForm();
    } else if(document.getElementById(b0rgForm)) {
        // b0rgForm on kaavan id, asetetaan se vastaamaan.
        b0rgForm = document.getElementById(b0rgForm);
    } // else ilmeisesti kaava oli asetettu ??

    // Etsitään kaikki kentät, jotka ovat vaadittuja. Jos niitä on, disabloidaan submit
    var inputit = b0rgForm.getElementsByTagName("input");
    var disabloi = false;
    for (var i=0; i<inputit.length; i++){
        var inputti = inputit[i];
        if (inputti.getAttribute("rel") == "required"){
            // asetetaan onblur quickCheckille
            inputti.onblur = function () { return quickCheck(this); }
        }
        if(inputti.getAttribute("type") == "submit") {
            inputti.onclick = function () { return checkForm(this); }
        }
    }

    // Jos on asetettu borganneet arvot, korosta ne.
    if(window.b0rgRequired != undefined) {
        for (var i=0; i<b0rgRequired.length; i++) {
            var inputti = b0rgForm[b0rgRequired[i]];
            if(inputti && inputti.getAttribute("rel") == "required") {
                inputti.setAttribute("class","requiredMissing");
            }
        }
    }
    if(window.b0rgMessage) {
        alert(b0rgMessage);
    }

    return true;
}

// Lisää onload eventti ilman wanhojen onloadien ylikirjoitusta
// @ Simon Willison's weblog - http://simon.incutio.com/
function addLoadEvent(func) {
    var oldonload = window.onload;
    if (typeof window.onload != 'function'){
        window.onload = func;
    } else {
        window.onload = function(){
            oldonload();
            func();
        }
    }
}

// Aja alustustoiminnot
addLoadEvent(initB0rg);

<?php
    die();
}

// Black magic
if($_POST['_b0rg']) {
    if(isset($_SESSION['_b0rg'])) unset($_SESSION['_b0rg']);
    foreach($_POST['_b0rg'] as $required) {
        if(!isset($_POST[$required]) || empty($_POST[$required])) {
            // Damn, javascripti validointi on kussu. Noh, ei voi mtn.
            // Talletetaan kuusut.
            $_SESSION['_b0rg'][] = $required;
        }
    }

    if(isset($_SESSION['_b0rg'])) {
        // JOs jokin äsken tarkastetuista on kussut, lähetä takaisin edelliselle sivulle.
        header("Location: ".substr($_SERVER['HTTP_REFERER'], 0, 255));
        die("Kaikkia ei ollut täytetty :(<script>window.url=\"{$_SERVER['HTTP_REFERER']}\";</script>");
    }
}

message("-=[ b0rg kaavan viesti ]=-\n");
message("*** Päiväys:     ".strftime("%c",time()));
message("*** Lähettäjä:   ".$_SERVER['REMOTE_ADDR']);
message("*** Prosessoitu: ".strftime("%c",$_SERVER['REQUEST_TIME']));
message("*** Viittaaja:   ".$_SERVER['HTTP_REFERER']);
message("*** Prosessoija: ".$_SERVER['REQUEST_URI']);
message("\n -=[ KENTÄT: ARVOT ]=-\n");

foreach($_POST as $key => $val) {
    if($key == "_b0rg") continue;
    message("$key: $val");
}

$headers  = 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";

mail($email,"[ b0rg kaavan viesti ]", wordwrap(message()), $headers);
if($_POST['redir']) {
    header("Location: ".substr($_POST['redir'], 0, 255));
} else {
    $_SESSION['_b0rgmessage'] = "Kiitoksia kiltit lapset, silko silmät ja vastaavat... Olet onnistuneesti lähettänyt viestin! Uskomatonta!";
    header("Location: ".substr($_SERVER['HTTP_REFERER'], 0, 255));
}
?>