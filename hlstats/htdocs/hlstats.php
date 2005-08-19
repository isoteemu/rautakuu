<?php

  /*
   * HLstats - Real-time player and clan rankings and statistics for Half-Life
   * http://sourceforge.net/projects/hlstats/
   *
   * Copyright (C) 2001  Simon Garner
   *
   * This program is free software; you can redistribute it and/or
   * modify it under the terms of the GNU General Public License
   * as published by the Free Software Foundation; either version 2
   * of the License, or (at your option) any later version.
   *
   * This program is distributed in the hope that it will be useful,
   * but WITHOUT ANY WARRANTY; without even the implied warranty of
   * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   * GNU General Public License for more details.
   *
   * You should have received a copy of the GNU General Public License
   * along with this program; if not, write to the Free Software
   * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
   */

define("_HLSTATS", 1);

// INCLUDE_PATH - Filesystem path to the hlstatsinc directory. This path can
//                be specified relative to hlstats.php by prepending ./ or
//                ../  If the path begins with a / then it is taken as a
//                full absolute filesystem path. However if the path begins
//                with none of these constructs, PHP will search your
//                include_path (as set in php.ini) (probably NOT the current
//                directory as might be expected!).
//                   Example paths:
//                      1) /usr/local/apache/hlstatsinc
//                           (absolute path)
//                      2) ../hlstatsinc
//                      -) ./hlstatsinc
//                           (paths relative to hlstats.php)
//                      3) hlstats/include
//                           (path relative to include_path)
//                Under Windows, make sure you use forward slash (/) instead
//                of back slash (\).
define("INCLUDE_PATH", dirname(__FILE__)."/hlstatsinc");

require(INCLUDE_PATH."/conf.inc.php");

// IE check
if(isset($_SERVER['HTTP_USER_AGENT']) &&
   preg_match('/msie.*.(win)/i' ,$_SERVER['HTTP_USER_AGENT']) &&
   !preg_match('/opera/i',$_SERVER['HTTP_USER_AGENT'])) {

  if( $_GET['forceie'] == "true" ) {
    setcookie("forceie", "true");
  } elseif ( $_COOKIE['forceie'] != "true" ) {

    header("Location: http://rautakuu.org/drupal/node/36");
    die("Internet Explorer ei ole tuettu");
  }
 }

// Allows HLstats to work with register_globals Off
if ( function_exists('ini_get') ) {
  $globals = ini_get('register_globals');
 } else {
  $globals = get_cfg_var('register_globals');
 }
if ($globals != 1) {
  @extract($_SERVER, EXTR_SKIP);
  @extract($_COOKIE, EXTR_SKIP);
  @extract($_POST, EXTR_SKIP);
  @extract($_GET, EXTR_SKIP);
  @extract($_ENV, EXTR_SKIP);
 }

if(isset($_SERVER['PATH_INFO'])) {
  $pathParts = explode("/", $_SERVER['PATH_INFO']);
  foreach($pathParts as $pathPart) {
    if(strstr($pathPart, ",")) {
      $key=substr($pathPart, 0, strpos($pathPart,","));
      $val=substr($pathPart, strpos($pathPart,",")+1);
      $HTTP_GET_VARS[$key] = $val;
      if(!isset($$key)) {
	$$key = $val;
      }
    }
  }
 }


// Set Finnish locale
setlocale(LC_ALL, "fi_FI");

// Check PHP configuration

if (version_compare(phpversion(), "4.1.0", "<"))
  {
    error("HLstats requires PHP version 4.1.0 or newer (you are running PHP version " . phpversion() . ").");
  }

if (!get_magic_quotes_gpc())
  {
    error("HLstats requires <b>magic_quotes_gpc</b> to be <i>enabled</i>. Check your php.ini or refer to the PHP manual for more information.");
  }

if (get_magic_quotes_runtime())
  {
    error("HLstats requires <b>magic_quotes_runtime</b> to be <i>disabled</i>. Check your php.ini or refer to the PHP manual for more information.");
  }

// this doesn't work with php 4.0.3+
/*if (!ini_get("track_vars"))
 {
 error("HLstats requires <b>track_vars</b> to be <i>enabled</i>. Check your php.ini or refer to the PHP manual for more information.");
 }*/

// do not report NOTICE warnings
error_reporting(E_ALL ^ E_NOTICE);
//error_reporting(E_ALL);


///
/// Classes
///

// Load database classes
require(INCLUDE_PATH . "/db.inc");


//
// Table
//
// Generates an HTML table from a DB result.
//

class Table
{
  var $columns;
  var $keycol;
  var $sort;
  var $sortorder;
  var $sort2;
  var $page;
  var $showranking;
  var $numperpage;
  var $var_page;
  var $var_sort;
  var $var_sortorder;
  var $sorthash;

  var $columnlist;
  var $startitem;

  var $maxpagenumbers = 20;


  function Table ($columns, $keycol, $sort_default, $sort_default2,
		  $showranking=false, $numperpage=50, $var_page="page",
		  $var_sort="sort", $var_sortorder="sortorder", $sorthash="",
		  $sort_default_order="desc")
  {
    global $HTTP_GET_VARS;

    $this->columns = $columns;
    $this->keycol  = $keycol;
    $this->showranking = $showranking;
    $this->numperpage  = $numperpage;
    $this->var_page = $var_page;
    $this->var_sort = $var_sort;
    $this->var_sortorder = $var_sortorder;
    $this->sorthash = $sorthash;
    $this->sort_default_order = $sort_default_order;

    $this->page = intval($HTTP_GET_VARS[$var_page]);
    $this->sort = $HTTP_GET_VARS[$var_sort];
    $this->sortorder = $HTTP_GET_VARS[$var_sortorder];


    if ($this->page < 1) $this->page = 1;
    $this->startitem = ($this->page - 1) * $this->numperpage;


    foreach ($columns as $col)
      {
	if ($col->sort != "no")
	  $this->columnlist[] = $col->name;
      }


    if (!is_array($this->columnlist) || !in_array($this->sort, $this->columnlist))
      {
	$this->sort = $sort_default;
      }

    if ($this->sortorder != "asc" && $this->sortorder != "desc")
      {
	$this->sortorder = $this->sort_default_order;
      }

    if ($this->sort == $sort_default2)
      {
	$this->sort2 = $sort_default;
      }
    else
      {
	$this->sort2 = $sort_default2;
      }
  }

  function draw ($result, $numitems, $width=100, $align="center")
  {
    global $g_options, $game, $db;

    $numpages = ceil($numitems / $this->numperpage);
    ?>

      <table width="<?php echo $width; ?>%" align="<?php echo $align; ?>" border=0 cellspacing=0 cellpadding=0 bgcolor="<?php echo $g_options["table_border"]; ?>">

	 <tr>
	 <td><table width="100%" border=0 cellspacing=1 cellpadding=4>

	 <tr valign="bottom" bgcolor="<?php echo $g_options["table_head_bgcolor"]; ?>">
	 <?php
	 $totalwidth = 0;

    if ($this->showranking)
      {
	$totalwidth += 5;

	echo "<td width=\"5%\" align=\"right\">"
	  . "<font color=\"" . $g_options["table_head_text"] . "\">"
	  . $g_options["font_small"] . "Rank" . "</font>"
	  . $g_options["fontend_small"] . "</td>\n";
      }

    foreach ($this->columns as $col)
      {
	$totalwidth += $col->width;

	echo "<td width=\"" . $col->width . "%\" align=\"$col->align\">";

	if ($col->sort != "no")
	  {
	    echo getSortArrow($this->sort, $this->sortorder, $col->name,
			      $col->title, $this->var_sort, $this->var_sortorder,
			      $this->sorthash);
	  }
	else
	  {
	    echo $g_options["font_small"];
	    echo "<font color=\"" . $g_options["table_head_text"] . "\">";
	    echo $col->title;
	    echo "</font>";
	    echo $g_options["fontend_small"];
	  }
	echo "</td>\n";
      }
    ?>
      </tr>

	  <?php
	  if ($totalwidth != 100)
	    {
	      error("Warning: Column widths do not add to 100%! (=$totalwidth%)", false);
	    }

    $rank = ($this->page - 1) * $this->numperpage + 1;

    while ($rowdata = $db->fetch_array($result))
      {
	echo "<tr>\n";
	$i = 0;

	if ($this->showranking)
	  {
	    $c = ($i % 2) + 1;
	    $i++;

	    echo "<td align=\"right\" bgcolor=\""
	      . $g_options["table_bgcolor$c"] . "\">"
	      . $g_options["font_normal"] . "$rank."
	      . $g_options["fontend_normal"] . "</td>\n";
	  }

	foreach ($this->columns as $col)
	  {
	    $c = ($i % 2) + 1;

	    $cellbody = "";
	    $colval = $rowdata[$col->name];

	    if ($col->align != "left")
	      $colalign = " align=\"$col->align\"";
	    else
	      $colalign = "";

	    $bgcolor = $g_options["table_bgcolor$c"];

	    if ($col->icon)
	      {
		$cellbody = "&nbsp;";
	      }

	    if ($col->link)
	      {
		$link = ereg_replace("%k", urlencode($rowdata[$this->keycol]), $col->link);
		$cellbody .= "<a href=\"" . $g_options["scripturl"] . "?$link\">";
	      }

	    if ($col->icon)
	      {
		$cellbody .= "<img src=\"" . $g_options["imgdir"]
		  . "/$col->icon.gif\" width=16 height=16 hspace=4 "
		  . "border=0 align=\"middle\" alt=\"$col->icon.gif\">";
	      }

	    switch ($col->type)
	      {
	      case "plain" :
		$cellbody .= $colval;
		break;
	      case "weaponimg":
		$colval = strtolower(ereg_replace("[ \r\n\t]*", "", $colval));

		$bgcolor = $g_options["table_wpnbgcolor"];

		$image = getImage("/weapons/$game/$colval");

		// check if image exists
		if ($image)
		  {
		    $cellbody .= "<img src=\"" . $image["url"] . "\" " . $image["size"] . " border=0 alt=\"" . strToUpper($colval) . "\">";
		  }
		else
		  {
		    $cellbody .= $g_options["font_small"];
		    $cellbody .= "<font color=\"#FFFFFF\" class=\"weapon\"><b>";
		    $cellbody .= strToUpper($colval);
		    $cellbody .= "</b></font>";
		    $cellbody .= $g_options["fontend_small"];
		  }

		break;

	      case "bargraph":
		$cellbody .= "<img src=\"" . $g_options["imgdir"] . "/bar";

		if ($colval > 40)
		  $cellbody .= "6";
		elseif ($colval > 30)
		  $cellbody .= "5";
		elseif ($colval > 20)
		  $cellbody .= "4";
		elseif ($colval > 10)
		  $cellbody .= "3";
		elseif ($colval > 5)
		  $cellbody .= "2";
		else
		  $cellbody .= "1";

		$cellbody .= ".gif\" width=\"";

		if ($colval < 1)
		  $cellbody .= "1%";
		elseif ($colval > 100)
		  $cellbody .= "100%";
		else
		  $cellbody .= sprintf("%d%%", $colval + 0.5);

		$cellbody .= "\" height=10 border=0 alt=\"$colval%\">";

		break;

	      default:
		if ($this->showranking && $rank == 1 && $i == 1)
		  $cellbody .= "<b>";

		$colval = nl2br(htmlentities($colval, ENT_COMPAT, "UTF-8"));

		if ($col->embedlink == "yes")
		  {
		    $colval = ereg_replace("%A%([^ %]+)%", "<a href=\"\\1\">", $colval);
		    $colval = ereg_replace("%/A%", "</a>", $colval);
		  }

		$cellbody .= $colval;

		if ($this->showranking && $rank == 1 && $i == 1)
		  $cellbody .= "</b>";

		break;
	      }

	    if ($col->link)
	      {
		$cellbody .= "</a>";
	      }

	    if ($col->append)
	      {
		$cellbody .= $col->append;
	      }

	    echo "<td$colalign bgcolor=\"$bgcolor\">"
	      . $g_options["font_normal"]
	      . $cellbody
	      . $g_options["fontend_normal"] . "</td>\n";

	    $i++;
	  }

	echo "</tr>\n\n";

	$rank++;
      }
    ?>
      </table></td>
	  </tr>

	  </table>
	  <?php
	  if ($numpages > 1)
	    {
	      ?>
	      <p>
		<table width="<?php echo $width; ?>%" align="<?php echo $align; ?>" border=0 cellspacing=0 cellpadding=0>

		<tr valign="top">
		<td width="100%" align="right"><?php
		echo $g_options["font_normal"];
	      echo "Page: ";

	      $start = $this->page - intval($this->maxpagenumbers / 2);
	      if ($start < 1) $start=1;

	      $end = $numpages;
	      if ($end > $this->maxpagenumbers + $start-1)
		$end = $this->maxpagenumbers + $start-1;

	      if ($end - $start + 1 < $this->maxpagenumbers)
		$start = $end - $this->maxpagenumbers + 1;

	      if ($start < 1) $start=1;

	      if ($start > 1)
		{
		  if ($start > 2)
		    $this->_echoPageNumber(1, "First page", "", " ...");
		  else
		    $this->_echoPageNumber(1, 1);
		}

	      for ($i=$start; $i <= $end; $i++)
		{
		  if ($i == $this->page)
		    {
		      echo "<b>$i</b> ";
		    }
		  else
		    {
		      $this->_echoPageNumber($i, $i);
		    }

		  if ($i == $end && $i < $numpages)
		    {
		      if ($i < $numpages - 1)
			$this->_echoPageNumber($numpages, "Last page", "... ");
		      else
			$this->_echoPageNumber($numpages, 10);
		    }
		}
	      echo $g_options["fontend_normal"];
	      ?></td>
		    </tr>

		    </table><p>
		    <?php
		    }
  }

  function _echoPageNumber ($number, $label, $prefix="", $postfix="")
  {
    global $g_options;

    echo "$prefix<a href=\"" . $g_options["scripturl"] . "?"
      . makeQueryString($this->var_page, $number);
    if ($this->sorthash)
      echo "#$this->sorthash";
    echo "\">$label</a>$postfix ";
  }
}


//
// TableColumn
//
// Data structure for the properties of a column in a Table
//

class TableColumn
{
  var $name;
  var $title;

  var $align = "left";
  var $width = 20;
  var $icon;
  var $link;
  var $sort = "yes";
  var $type = "text";
  var $embedlink = "no";

  function TableColumn ($name, $title, $attrs="")
  {
    $this->name = $name;
    $this->title= $title;

    $allowed_attrs = array(
			   "align",
			   "width",
			   "icon",
			   "link",
			   "sort",
			   "append",
			   "type",
			   "embedlink"
			   );

    parse_str($attrs);

    foreach ($allowed_attrs as $a)
      {
	if (isset($$a))
	  {
	    $this->$a = $$a;
	  }
      }
  }
}


///
/// Functions
///


//
// void error (string message, [boolean exit])
//
// Formats and outputs the given error message. Optionally terminates script
// processing.
//

function error ($message, $exit=true)
{
  global $g_options;
  ?>
    <table border=1 cellspacing=0 cellpadding=5>
       <tr>
       <td bgcolor="#CC0000"><font face="Arial, Helvetica, sans-serif" size=2 color="#FFFFFF"><b>ERROR</b></font></td>
       </tr>
       <tr>
       <td bgcolor="#FFFFFF"><font face="Arial, Helvetica, sans-serif" size=2 color="#000000"><?php echo $message; ?></font></td>
															 </tr>
															 </table>
  <?php
  if ($exit) {
    header("Status: 404 Not Found");
    exit;
  }
}


//
// string makeQueryString (string key, string value, [array notkeys])
//
// Generates an HTTP GET query string from the current HTTP GET variables,
// plus the given 'key' and 'value' pair. Any current HTTP GET variables
// whose keys appear in the 'notkeys' array, or are the same as 'key', will
// be excluded from the returned query string.
//

function makeQueryString($key, $value, $notkeys = array())
{
  global $HTTP_GET_VARS;

  if (!is_array($notkeys))
    $notkeys = array();

  foreach ($HTTP_GET_VARS as $k=>$v)
    {
      if ($k && $k != $key && !in_array($k, $notkeys))
	{
	  $querystring .= urlencode($k) . "=" . urlencode($v) . "&amp;";
	}
    }

  $querystring .= urlencode($key) . "=" . urlencode($value);

  return $querystring;
}


//
// array getOptions (void)
//
// Retrieves HLstats option and style settings from the database.
//

function getOptions()
{
  global $db;

  $result  = $db->query("SELECT keyname, value FROM hlstats_Options");
  $numrows = $db->num_rows($result);

  if ($numrows)
    {
      while ($rowdata = $db->fetch_row($result))
	{
	  $options[$rowdata[0]] = $rowdata[1];
	}
      return $options;
    }
  else
    {
      error("Warning: Could not find any options in table " .
	    "<b>hlstats_Options</b>, database <b>" . DB_NAME . "</b>. Check HLstats configuration.");
      return array();
    }
}


//
// void pageHeader (array title, array location)
//
// Prints the page heading.
//

function pageHeader($title, $location)
{
  global $g_options, $HTTP_GET_VARS;
  if(!headers_sent()) {
    ob_start();
  } else {
    error("Headerit jo lähetetty", false);
    $g_options['doGzip'] = false;
  }
  include(INCLUDE_PATH . "/header.inc");
}


//
// void pageFooter (void)
//

// Prints the page footer.
//

function pageFooter()
{
  global $g_options;
  include(INCLUDE_PATH . "/footer.inc");

  $took = timer();
  echo "<!-- Took $took s. to generate -->";

  $content = ob_get_clean();

  // kirjoitetaan urlit uudestaan
  function formatUrlParams($str) {
    $seek='%(&amp;|&|\?)(\w+)=(\w+)%';
    $str = preg_replace($seek, '/\\2,\\3', $str);
    return 'href="'.$_SERVER['SCRIPT_NAME'].$str.'/"';
  }

  $me = preg_quote($_SERVER['SCRIPT_NAME']);
  $content = preg_replace('%href=\"'.$me.'(\?[^\"]*?)\"%e', 'formatUrlParams("\\1");', $content);

  if(pageNotFound()) header("Status: 404 Not Found");
  trackUser();

  // Pakataan sivu
  if($g_options['doGzip'] && !headers_sent()) {
    Header('Content-Encoding: gzip');
    $content = "\x1f\x8b\x08\x00\x00\x00\x00\x00".
      substr(gzcompress($content, 9), 0, - 4). // substr -4 isn't needed
      pack('V', crc32($content)).              // crc32 and
      pack('V', strlen($content));             // size are ignored by all the browsers i have tested
  }

  // Cacheammeko...
  if($g_options['useCache'] && !pageNotFound()) {
    if( $took > 1 ) {
      global $cache, $cache_handle;

      // Ydinfysiikkaa cachen lifetimen laskemiseksi.
      $cacheLifeTime = round($took*60);
      $cache->save($cache_handle, $content, $cacheLifeTime);
    }
  }
  die($content);
}

function trackUser() {
  if(pageNotFound()) return false;
  if(!$_SERVER['HTTP_REFERER']) return false;
  if (isset($_SERVER['HTTP_X_MOZ'])) {
    return false;
  }

  global $db;

  $db->query(sprintf("
                      INSERT INTO
                          `hlstats_Link_Trace`
                      (
                          `time`, `whom`, `from`, `to`
                      ) VALUES (
                          NOW(), '%s', '%s', '%s'
                      )",
		     $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_REFERER'], $_SERVER['SCRIPT_URI']));

}


//
// void getSortArrow (string sort, string sortorder, string name,
//                    string longname, [string var_sort,
//                    string var_sortorder, string sorthash])
//
// Returns the HTML code for a sort arrow <IMG> tag.
//

function getSortArrow ($sort, $sortorder, $name, $longname,
		       $var_sort="sort", $var_sortorder="sortorder",
		       $sorthash="")
{
  global $g_options;

  if ($sortorder == "asc")
    {
      $sortimg = "sort-ascending.gif";
      $othersortorder = "desc";
    }
  else
    {
      $sortimg = "sort-descending.gif";
      $othersortorder = "asc";
    }

  $arrowstring = $g_options["font_small"]
    . "<a href=\"" . $g_options["scripturl"] . "?"
    . makeQueryString($var_sort, $name, array($var_sortorder));

  if ($sort == $name)
    {
      $arrowstring .= "&amp;$var_sortorder=$othersortorder";
    }
  else
    {
      $arrowstring .= "&amp;$var_sortorder=$sortorder";
    }

  if ($sorthash)
    {
      $arrowstring .= "#$sorthash";
    }

  $arrowstring .= "\" style=\"color: " . $g_options["table_head_text"]
    . ";display:block;\" title=\"Change sorting order\"  rel=\"nofollow\">"
    . "<font color=\"" . $g_options["table_head_text"] . "\">"
    . "$longname</font>";

  if ($sort == $name)
    {
      $arrowstring .= "&nbsp;<img src=\"" . $g_options["imgdir"] . "/$sortimg\""
	. "width=7 height=7 hspace=4 border=0 align=\"middle\" alt=\"$sortimg\">";
    }

  $arrowstring .= "</a>".$g_options["fontend_small"];

  return $arrowstring;
}


//
// string getSelect (string name, array values, [string currentvalue])
//
// Returns the HTML for a SELECT box, generated using the 'values' array.
// Each key in the array should be a OPTION VALUE, while each value in the
// array should be a corresponding descriptive name for the OPTION.
//
// The 'currentvalue' will be given the SELECTED attribute.
//

function getSelect ($name, $values, $currentvalue="")
{
  $select = "<select name=\"$name\">\n";

  $gotcval = false;

  foreach ($values as $k=>$v)
    {
      $select .= "\t<option value=\"$k\"";

      if ($k == $currentvalue)
	{
	  $select .= " selected";
	  $gotcval = true;
	}

      $select .= ">$v\n";
    }

  if ($currentvalue && !$gotcval)
    {
      $select .= "\t<option value=\"$currentvalue\" selected>$currentvalue\n";
    }

  $select .= "</select>";

  return $select;
}


//
// string getLink (string url[, int maxlength[, string type[, string target]]])
//

function getLink ($url, $maxlength=40, $type="http://", $target="_blank")
{
  if ($url && $url != $type)
    {
      if (ereg("^$type(.+)", $url, $regs))
	{
	  $url = $type . $regs[1];
	}
      else
	{
	  $url = $type . $url;
	}

      if (strlen($url) > $maxlength)
	{
	  $url_title = substr($url, 0, $maxlength-3) . "...";
	}
      else
	{
	  $url_title = $url;
	}

      $url = str_replace("\"", urlencode("\""), $url);
      $url = str_replace("<",  urlencode("<"),  $url);
      $url = str_replace(">",  urlencode(">"),  $url);

      return "<a href=\"$url\" target=\"$target\">"
	. htmlentities($url_title, ENT_COMPAT, "UTF-8") . "</a>";
    }
  else
    {
      return "";
    }
}


//
// string getEmailLink (string email[, int maxlength])
//

function getEmailLink ($email, $maxlength=40)
{
  if (ereg("(.+)@(.+)", $email, $regs))
    {
      if (strlen($email) > $maxlength)
	{
	  $email_title = substr($email, 0, $maxlength-3) . "...";
	}
      else
	{
	  $email_title = $email;
	}

      $email = str_replace("\"", urlencode("\""), $email);
      $email = str_replace("<",  urlencode("<"),  $email);
      $email = str_replace(">",  urlencode(">"),  $email);

      return "<a href=\"mailto:$email\">"
	. htmlentities($email_title, ENT_COMPAT, "UTF-8") . "</a>";
    }
  else
    {
      return "";
    }
}


//
// array getImage (string filename)
//

function getImage ($filename)
{
  global $g_options;

  $url = $g_options["imgdir"] . $filename;

  if ($g_options["imgpath"])
    {
      $path = $g_options["imgpath"] . $filename;
    }
  else
    {
      // figure out absolute path of image

      if (!ereg("^/", $g_options["imgdir"]))
	{
	  ereg("(.+)/[^/]+$", $_SERVER["SCRIPT_NAME"], $regs);
	  $path = $regs[1] . "/" . $url;
	}
      else
	{
	  $path = $url;
	}

      $path = $_SERVER["DOCUMENT_ROOT"] . $path;
    }

  // check if image exists
  if (file_exists($path . ".gif"))
    {
      $ext = "gif";
    }
  elseif (file_exists($path . ".jpg"))
    {
      $ext = "jpg";
    }
  else
    {
      $ext = "";
    }

  if ($ext)
    {
      $size = getImageSize("$path.$ext");

      return array(
		   "url"=>"$url.$ext",
		   "path"=>"$path.$ext",
		   "width"=>$size[0],
		   "height"=>$size[1],
		   "size"=>$size[3]
		   );
    }
  else
    {
      return false;
    }
}

function pageNotFound($status = NULL) {
  static $is404;
  if(!isset($is404)) $is404 = false;
  if($status != NULL) $is404 = $status;
  return $is404;
}

function timer() {
  static $start;
  list($usec, $sec) = explode(" ", microtime());
  $now = ((float)$usec + (float)$sec);
  if(!isset( $start )) {
    $start = $now;
  }
  $timed = $now - $start;
  return $timed;
}

////
//// Initialisation
////

define("VERSION", "<sup><a href=\"http://rautakuu.org\">Rautakuu [dot] org</a></sup>");

$db_classname = "DB_" . DB_TYPE;
$db = new $db_classname;

$g_options = getOptions();

if (!$g_options["scripturl"])
  $g_options["scripturl"] =& $_SERVER['SCRIPT_NAME'];

if (!$g_options['doGzip'])
  $g_options['doGzip'] = true;

if($HTTP_GET_VARS["cache"] == "false" ) $g_options['useCache'] = false;
if (!$g_options['useCache']) {
  if(include_once("Cache.php")) {
    $g_options['useCache'] = true;
  } else {
    $g_options['useCache'] = false;
  }
 }

////
//// Main
////

if($g_options['useCache']) {
  if(!class_exists("Cache")) {
    error("Cache defined to be used but Cache_Output class is missing. Disabling cache.", false);
    $g_options['useCache'] = false;
  }
  $cache =& new Cache(CACHE_STORAGE_CLASS, array('database' => DB_NAME, 'phptype' => DB_TYPE, 'username' => DB_USER, 'password' => DB_PASS, 'cache_table' => 'hlstats_Pear_Cache'));
  $cache_handle = $cache->generateID($_SERVER['REQUEST_URI']);

  // Jos post käytössä, älä cachea.
  if(!empty($_POST)) {
    $g_options['useCache'] = false;
    $cache->remove($cache_handle);
  }
  elseif ($content = $cache->get($cache_handle)) {
    if(substr($content,0,8) == "\x1f\x8b\x08\x00\x00\x00\x00\x00" ) Header('Content-Encoding: gzip');
    die($content);
  } elseif( $cache->isExpired($cache_handle) ) {
    // Jätteitä, siivouksen aika
    $cache->garbageCollection();
  }
 }

// Init timer
timer();

$mode =& $HTTP_GET_VARS["mode"];
//if($_SERVER['REQUEST_URI'] == $_SERVER['SCRIPT_NAME']) $mode = "contents";
//if(empty($mode)) $mode = "contents";

$modes = array(
	       "players",
	       "clans",
	       "weapons",
	       "maps",
	       "actions",
	       "claninfo",
	       "playerinfo",
	       "weaponinfo",
	       "mapinfo",
	       "actioninfo",
	       "playerhistory",
	       "search",
	       "admin",
	       "help",
	       "live_stats"
	       );

if (!in_array($mode, $modes))
  {
    pageNotFound(true);
    $mode = "contents";
  }

include(INCLUDE_PATH . "/$mode.inc");

pageFooter();

?>