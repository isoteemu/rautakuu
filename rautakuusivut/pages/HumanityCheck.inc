<?php
if(!isset($_SESSION['captcha_return'])) {
    $_SESSION['captcha_return'] = $_SERVER['HTTP_REFERER'];
}
if(isset($_GET['clearCaptcha'])) $_SESSION['captcha_ok'] = false;

if($_POST['captcha'] == $_SESSION['captcha'] && !empty($_SESSION['captcha'])) {
    $_SESSION['captcha_ok'] = true;
}

if($_SESSION['captcha_ok'] == true ) {
    $to = $_SESSION['captcha_return'];
    unset($_SESSION['captcha_return']);
    unset($_SESSION['captcha']);
    Header("Location: {$to}");
    $content .= '<a href="'.$to.'">'.$to.'</a>';
} else {
    unset($_SESSION['captcha']);
    $content .= "<p><strong>Ihmisyytesi varmistin.</strong><br>Käyttäjiemme yksityisyyden ja heidän tietoturvan nimissä sinun tulee osoittaa olevasi \"ihminen\". Se tapahtuu niin, että kirjoitat kuvan alle, mitä kuvassa lukee.<br />Jos et saa kuvasta selvää, paina reloadia.</p>";
    $content .= "<form method=\"POST\" action=\"".$_SERVER['REQUEST_URI']."\" name=\"captcha\"><img src=\"ImageCaptcha\"><br><input type=\"text\" name=\"captcha\" style=\"width:200px;\" onLoad=\"self.focus();\"></form>";
    $content .= '
        <script>
        <!--
            document.captcha.captcha.focus();
        // -->
        </script>';
}