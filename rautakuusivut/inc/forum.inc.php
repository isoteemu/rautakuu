<?php
include_once("DB.php");

$fdb =& DB::Connect($settings['forum']['db']);

if( !empty($_POST['title']) && !empty($_POST['msg'])) {
    $authOK = true;
    if(isset($_POST['nick'])) {

        $nick = addslashes($_POST['nick']);
        $cdb =& DB::Connect($settings['cyrus']['db']);
        if(DB::IsError($cdb)) {
            echo "<!-- DB ERROR: ".$cdb->getMessage()." -->";
        }
        $uidRes = $cdb->query("
            SELECT `password`
            FROM `accountuser`
            WHERE `username` LIKE '$nick'");
        if($uidRes->numRows() > 0) {
            $authOK = false;
            list($_pwhome) = $uidRes->fetchRow();
            if($_SERVER['PHP_AUTH_PW'] == $_pwhome) {
                $authOK = true;
            } else {
                $authOK = false;
                header('WWW-Authenticate: Basic realm="Suojattu nick"');
                header('HTTP/1.0 401 Unauthorized');
                echo "<strong><font color=\"red\">Suojattu nick. Viestiä ei lähetetty.</font></strong>";
            }
        }
    } else {
        $nick = "Anonyymi alkoholisti";
    }
    if( $authOK == true ) {

        if(@include_once("HTML/BBCodeParser.php")) {
                $bbMesg = new HTML_BBCodeParser(array("filters"=>"Basic,Links,Email"));
                $msg = addslashes(nl2br($bbMesg->qparse(htmlspecialchars($_POST['msg']))));
        } else {
                $msg = addslashes(nl2br(htmlspecialchars($_POST['msg'])));
        }

        $title = addslashes($_POST['title']);

        $sql = "
            INSERT INTO
                `feedpack` (`group`, `reply-to`, `from`, `time`, `IP`, `title`, `msg` )
            VALUES (
                '$page', NULL , '$nick', NOW( ) , '{$_SERVER['REMOTE_ADDR']}', '$title', '$msg'
            )";
        $fins =& $fdb->query($sql);
        if(DB::IsError($fins)) {
            echo "<!-- DB ERROR: ".$fins->getMessage()." - $sql -->";
        } else {
            $msgsend = true;
            $_POST['title'] = "";
            $_POST['msg']   = "";
        }
    }
}


$res =& $fdb->query(sprintf("SELECT `id`, `from`, `time`, `title`, `msg` FROM feedpack WHERE 1 and `GROUP` = '%s'", $page));

ob_start();

?>
<script language="Javascript">
function showSubmit() {
    var feedbackSubmit = document.getElementById("feedbackSubmit");
    if(feedbackSubmit.style.display=="none") {
        feedbackSubmit.style.display="inherit";
    }
}

function toggle(id) {
    var working = document.getElementById(id);
    if( working.style.display=="none" ) {
        working.style.display="block";
    } else {
        working.style.display="none";
    }
}

</script>
<?php
if( $res->numRows() > 0) {
?>

<div class="header">
  Kommentit
</div>
<table border="0">
  <thead>
    <tr>
      <th align="left">
        <strong>&nbsp;Viesti</strong>
      </th>
      <th align="left">
        <strong>&nbsp;Aika</strong>
      </th>
      <th align="left">
        <strong>&nbsp;Lähettäjä</strong>
      </th>
    </tr>
  </thead>
  <tbody>
<?php
    while(list($id, $from, $time, $title, $msg) = $res->fetchRow()) {
        echo '
    <tr>
      <td><a href="javascript:toggle('.$id.');">'.htmlspecialchars($title).'</a></td>
      <td>'.htmlspecialchars($time).'</td>
      <td>'.htmlspecialchars($from).'</td>
    </tr>
    <tr>
      <td colspan="3">
        <div id="'.$id.'" style="border-color:#FF3C12; border-style:dashed; border-width:1px; padding:10px; display:block; width:100%;">
          '.$msg.'
        </div>
      </td>
    </tr>
        ';
    }
?>
  </tbody>
</table>
<p>&nbsp;</p>
<?php
}

if($msgsend == true) {
?>
<blockquote>
    <strong>Kiitoksia kommentista.</strong>
</blockquote>
<?php
}
?>
<div class="header">
  <a href="javascript:showSubmit();">Lisää kommentti</a>
</div>
<table border="0" id="feedbackSubmit" style="display:none;">
  <form action="<?= $_SERVER['REQUEST_URI'] ?>" method="post">
  <tbody>
    <tr>
      <td align="left" valign="top" width="30%">
        <input type="hidden" name="replyto" value="">
        <strong>Otsikko:</strong><br />
        <input type="text" name="title" value="<?= htmlspecialchars($_POST['title']) ?>" style="width:100%;"><br />
        <strong>Nimimerkki:</strong><br />
        <input type="text" name="nick" value="<?= htmlspecialchars($_POST['nick']) ?>" style="width:100%;  color:#545454; font-family:verdana; font-size:10px;"><br />
        <input type="submit" name="btn" value="lähetä" style="width:100%;">
      </td>
      <td valign="top">
        <strong>Viesti:</strong><br />
        <textarea name="msg" rows="5" style="width:100%;"><?= htmlspecialchars($_POST['msg']) ?></textarea>
      </td>
    </tr>
  </tbody>
  </form>
</table>
<?php
$forum = ob_get_clean();
