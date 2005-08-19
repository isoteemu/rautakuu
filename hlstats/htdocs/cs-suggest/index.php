<?php
header("Content-Type: text/html;charset=utf-8");
?>
<html>
  <head>
    <title>CS Suggest testi</title>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <script>
    <!--
    function focusSearchBox() {
        document.f.q.focus();
    }
    // -->
    </script>
  </head>
  <body onLoad="focusSearchBox();">
    <h2>CS Suggest testi</h2>
    <form name=f action="http://rautakuu.org/hlstats/index.php">
      <input autocomplete="off" maxLength="256" name=q value="">
      <input type="submit" name="btnG" value="etsi">
      <input type="hidden" name="mode" value="search">
      <input type="hidden" name="st" value="player">
      <input type="hidden" name="game" value="cstrike">
    </form>
  </body>
    <script src="ggsgst.js"></script>
    <script>
      InstallAC(document.f,document.f.q,document.f.btnG,"search","en");
    </script>
</html>
