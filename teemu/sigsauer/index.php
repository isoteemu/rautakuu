<?php

session_start();

include_once('http.inc.php');
include_once('drupal_common.inc.php');


if(file_exists('cache.inc'))
  include_once('cache.inc');


if(!isset($rss)) {


  $appId = '/** Yahoo appid tähän **/';
  $url = 'http://local.yahooapis.com/MapsService/V1/geocode?output=php&appid='.urlencode($appId).'&location=';
  
  include_once('XML/Feed/Parser.php');
  include_once('filter.inc.php');
  
  
  //$_rss = file_get_contents('http://feeds.reuters.com/reuters/topNews');
  $_rss = file_get_contents('reuters.rss');
  
  $feed = new XML_Feed_Parser($_rss);

  $rss = array();
  
  foreach ($feed as $entry) {
    if(preg_match('/^(.*) \([^\)]+\) - /', $entry->description, $match)) {
      $entry->location = $match[1];
  
      $data = file_get_contents($url.urlencode($entry->location));
      $geo = unserialize($data);
  
      $entry->geo = $geo['ResultSet']['Result'];
    }
  
  //   if($entry->feedburner:origLink) {
  //     $link = $entry->feedburner:origLink;
  //   } else {
  //     $link = $entry->link;
  //   }
  
  
    $rss[] = array(
      'title' => filter_xss($entry->title),
      'description' => filter_xss($entry->description),
      'link' => $entry->link,
      'lon' => $entry->geo['Longitude'],
      'lat' => $entry->geo['Latitude'],
      'city' => $entry->geo['City']
    );
  }

  file_put_contents('cache.inc', '<?php $rss = '.var_export($rss, 1).' ?>');
}

foreach($rss as $k => $e) {
  $imgId = md5($e['link']);
  if(!file_exists('cache/'.$imgId) && true == false) {
    $link = drupal_http_request($e['link']);

    if(preg_match('/\s+var strippedPhotoHTML = \'<img[^>]+src=\\\"(.+)\\\"[^>]+>\';/U', $link->data, $match)) {
      $img = drupal_http_request();
  
      // Hack-ish
      $img = drupal_http_request('http://www.reuters.com'.$match[1]);
      //[Content-Type] => image/jpeg
      file_put_contents('cache/'.$imgId, $img->data);
    }
  }

  if(file_exists('cache/'.$imgId))
    $rss[$k]['img'] = 'cache/'.$imgId;
}

?>

<html>
  <head>
    <title>News testi</title>
    <meta content="">
    <style>
body {
  text-align: center;
  min-width: 600px;
}

h2 {
  font-size:1.2em;
}

#wrapper {
  overflow:hidden;
  margin:0 auto;
  width:600px;
  height:400px;
  text-align: left;
  border: 1px solid #000;
  position:relative;
}

#banner {
  text-align:center;
  width:100%;
  position:absolute;
  bottom:0;
  color:#000;
  background:#fff;

  opacity: .90;
  filter: alpha(opacity=90);
}

#storyImg {
  position:absolute;
  right:0;
  top:0;
  margin-top:5%;
  margin-right:5%;
}

#map {
  position:absolute;
  top:0;
  width:100%;
  height:100%;
  left:0;
}

#map .maplayer {
  position:absolute;
  top:0;
  left:0;
}

#pointer {
  margin:auto;
  margin-top:-30px;
  margin-left:-9px;
  position:absolute;
  top:0;
  left:0;
  color:#000;
}

#pointer strong {
  margin-left:-40%;
  background: url('tint.png');
}

#pointer img {
  clear:both;
}

#clouds {
  display:none;
}
    </style>
    <script>
var rss = <?= json_encode($rss); ?>
    </script>
  </head>
  <body>
    <div id="wrapper">
      <div id="map">
<?php

// Jyväskylän lon/lat
$lon = 25.742635;
$lat = 62.240393;

if(!isset($_SESSION['pos'])) {
  $hostip = drupal_http_request('http://api.hostip.info/get_html.php?ip='.ip_address().'&position=true');

  if(preg_match_all('/^(Latitude|Longitude): ([-\d\.]+)$/m', $hostip->data, $match)) {
    $_SESSION['pos']['lon'] = $lon = $match[2][1];
    $_SESSION['pos']['lat'] = $lat = $match[2][0];
  } else {
    $_SESSION['pos']['lon'] = $lon;
    $_SESSION['pos']['lat'] = $lat;
  }
}

$now = time();
$sunrise = date_sunrise($now, SUNFUNCS_RET_TIMESTAMP, $lat, $lon);
$sunset  = date_sunset($now, SUNFUNCS_RET_TIMESTAMP, $lat, $lon);

if($sunrise < $now && $sunset > $now)
  $img = 'day.jpg';
else 
  $img = 'night.jpg';

?>
        <img src="<?= $img; ?>" class="maplayer" width="2048" height="1024" />
<!--
        <img src="clouds_2048.png" class="maplayer" width="2048" height="1024" id="clouds" />
-->

        <div id="pointer">
          <img src="arrow.gif" width="19" height="34" /><br />
          <strong id="city"><!-- place city here --></strong>
        </div>

      </div>
      <div id="storyImg">
        <img src="cache/<?= $imgId ?>">
      </div>
      <div id="banner">
        <h2><!-- Here comes the title --></h2>
      </div>
    </div>
    <a href="#" id="stop">stop</a> | <a href="#" id="pilvet">pilvet</a>

    <script src="jquery.js"></script>
    <script src="jquery.easing.js"></script>
    <script src="facebox/facebox.js"></script>
    <script>

$('#stop').click(function() {
  console.log('stopped');
  clearTimeout(_tickerTimer);
  return false;
});

$('#pilvet').toggle(function() {
  $('#clouds').show();
}, function() {
  $('#clouds').hide();
});

if (!window.console || !console.firebug) {
    var names = ["log", "debug", "info", "warn", "error", "assert", "dir", "dirxml",
    "group", "groupEnd", "time", "timeEnd", "count", "trace", "profile", "profileEnd"];

    window.console = {};
    for (var i = 0; i < names.length; ++i)
        window.console[names[i]] = function() {}
}

// Make worldMap global
var worldMap = {}


function _worldMap() {
  this.currentScale = 1;

  this.map = $('#map');
  this.map.height = parseInt($('.maplayer', this.map).css('height'));
  this.map.width = parseInt($('.maplayer', this.map).css('width'));

  this.viewPort = $('#wrapper');
  this.viewPort.width = parseInt(this.viewPort.css('width'));
  this.viewPort.height = parseInt(this.viewPort.css('height'));

  this.pivotPoint = {
    x: this.viewPort.width/2-75,
    y: this.viewPort.height/2-50
  };

  this.scale = 1;

  this.animationTime = 1500;

  // Move pointer on click event
  this.map.click(function(e) {

    console.log('Map click');
    clearTimeout(_tickerTimer);

    //var slideshow = news.stopSlideshow();

    var x = e.pageX - parseInt(this.parentNode.offsetLeft) - worldMap.pivotPoint.x;
    var y = e.pageY - parseInt(this.parentNode.offsetTop) - worldMap.pivotPoint.y;

    x = parseInt($(this).css('left')) - x;
    y = parseInt($(this).css('top')) - y;

    // Prevent over-moving
    /*
    if(y>0)
      y = 0;
    else if(y<-(worldMap.map.height*worldMap.scale)+worldMap.viewPort.height)
      y = -(worldMap.map.height*worldMap.scale)+worldMap.viewPort.height;

    if(x>0)
      x = 0;
    else if(x<-(worldMap.map.width*worldMap.scale)+worldMap.viewPort.width)
      x = -(worldMap.map.width*worldMap.scale)+worldMap.viewPort.width;
    */

    x = worldMap.validX(x);
    y = worldMap.validY(y);

    $(this).animate({
      left: x,
      top: y
    }, 'normal', 'easeInOutQuint');

    // if( slideshow ) news.startSlideshow();
  });
}

/**
 * Zoom map to fit into viewport
 */
_worldMap.prototype.zoomOut = function() {
  // Calculate scale
  console.log('Zooming out');

  var scaleX = this.viewPort.width/this.map.width;
  var scaleY = this.viewPort.height/this.map.height;
  console.log('Scales: X: %s Y: %s', scaleX, scaleY);
  var scale = Math.max(scaleX, scaleY);
  return this.zoom(scale);

}

/**
 * Zoom map image into scale
 */
_worldMap.prototype.zoom = function(scale) {
  console.log('Zoom: %s', scale);

  if(scale != this.scale) {
    console.log('pew-zoom X: %s Y: %s', this.map.css('left'), this.map.css('top'));
    var x = -1*(parseInt(this.map.css('left'))-this.pivotPoint.x)*scale;
    var y = -1*(parseInt(this.map.css('top'))-this.pivotPoint.y)*scale;

    if(x<this.viewPort.width/2)
      x = this.viewPort.width/2;
    if(y<this.viewPort.height/2)
      y = this.viewPort.height/2;

    // Checks
    console.log('Zoom positions: X: %s Y: %s', x, y);
    this.scale = scale;
    return this.zoomPanToPix(scale, x, y);
  }
}

/**
 * Zoom map image and move pivot into lon/lat
 */
_worldMap.prototype.zoomPanTo = function(scale, lon, lat) {
  this.scale = scale;
  var pos = this.geoToPix(lon,lat);
  console.log(pos);
  return this.zoomPanToPix(scale,pos.lon,pos.lat);
}

/**
 * Zoom and move pivot into point
 */
_worldMap.prototype.zoomPanToPix = function(scale, x, y) {
  this.scale = scale;
  $('.maplayer', this.map).animate({
    width: this.map.width*scale,
    height: this.map.height*scale
  }, this.animationTime, 'easeInOutQuint');
  this.map.animate({
    top: -1*y+this.pivotPoint.y,
    left: -1*x+this.pivotPoint.x
  }, this.animationTime, 'easeInOutQuint');
  return this;
}

_worldMap.prototype.panTo = function(lon, lat) {

  var pos = this.geoToPix(lon, lat);

  var W = parseInt($('.maplayer', this.map).css('width'));
  var H = parseInt($('.maplayer', this.map).css('height'));


  //pos.lon = -1*pos.lon + this.pivotPoint.x + this.map.width/2;

  pos.lon = this.validX(-1*pos.lon + this.pivotPoint.x);
  pos.lat = this.validY(-1*pos.lat + this.pivotPoint.y);
  //pos.lat = -1*pos.lat + this.pivotPoint.y + this.map.height/2;


  // TODO: estä kuvan liikasiirto
  /*
  if(pos.y>0)
    pos.y = 0;
  else if(pos.y<-H+this.viewPortHeight)
    pos.y = -H+this.viewPortHeight;

  if(pos.x>0)
    pos.x = 0;
  else if(pos.x<-W+this.viewPortHeight)
    pos.x = -W+viewPortHeight;
  */
  console.log('Panning to X: %s[%s] Y: %s[%s]', lon, pos.lon, lat, pos.lat);
  console.log(pos);

  this.map.animate({
    left: Math.round(pos.lon),
    top: Math.round(pos.lat),
  }, this.animationTime, 'easeInOutQuint');

  return this;
}

_worldMap.prototype.setPointer = function(lon, lat, city) {
  var pos = this.geoToPix(lon, lat);

  $('#pointer').fadeOut('fast', function() {

    if(city != null)
      $('#city').html(city).show(0);
    else
      $('#city').hide(0);

    $(this).css({
      top: Math.round(pos.lat),
      left: Math.round(pos.lon)
    }).fadeIn('fast');
  });

  return this;
}

_worldMap.prototype.hidePointer = function() {
  $('#pointer').fadeOut('fast');
  return this;
}

_worldMap.prototype.validX = function(x) {
  x = parseInt(x);
  if(x>0)
    x = 0;
  else if(x<-(this.map.width*this.scale)+this.viewPort.width)
    x = -(this.map.width*this.scale)+this.viewPort.width;
  return x;
}

_worldMap.prototype.validY = function(y) {
  y = parseInt(y);
  if(y>0)
    y = 0;
  else if(y<-(this.map.height*this.scale)+this.viewPort.height)
    y = -(this.map.height*this.scale)+this.viewPort.height;

  return y;
}

/**
 * Translate longitude and latitude into pix in map
 */
_worldMap.prototype.geoToPix = function(lon,lat) {
  // Longitude
  var W = this.map.width*this.scale/2;
  var X = lon/180 * W
  if(lon > 0) {
    console.log('Longitude %s is X: %s, removing W: %s = %s', lon, X, W, X-W);
//     X = W-X;
  } else {
    console.log('Longitude %s is X: %s', lon, X);
  }
  X = X+W;

  // Latitude
  var H = this.map.height*this.scale/2;
  var Y = -1*lat/90 * H;

  if(lat > 0) {
    console.log('Latitude %s is Y: %s, removing H: %s', lat, Y, H);
//     Y = H-Y;
  } else {
    console.log('Latitude %s is Y: %s', lat, Y);
  }
  Y = Y+H;

  console.log('Y: %s H: %s', Y, H);

  X = Math.round(X);
  Y = Math.round(Y);

  var r = {
    lon: X,
    lat: Y
  };

  console.log('geoToPix X: %s[%s] Y: %s[%s]', lon, r.lon, lat, r.lat);

  return r; 
}


var worldMap = new _worldMap();

function moveMap(lon, lat) {
  console.warn('Deprecated call to moveMap');
  // For map, we need change longitude polarity
  var lon = -1*(lon);

  var X = lon/180;
  var Y = lat/90;

  X = 2048/2*X-2048/2+220;
  Y = 1024/2*Y-1024/2+180;

  console.log('X: %s, Y: %s', X, Y);

  // TODO: kuvan koko
  if(Y>0)
    Y = 0;
  else if(Y<-1024+400)
    Y = -1024+400;

  if(X>0)
    X = 0;
  else if(X<-2048+600)
    X = -2048+600;

  $('#map').animate({
    left: Math.round(X),
    top: Math.round(Y),
  }, 1000, 'easeInOutQuint');
  $('#map .maplayer').animate({
    width: 2048,
    height: 1024,
  }, 1000, 'easeInOutQuint');
}

function swapStoryImg(img) {
  $('#storyImg img').fadeOut('normal',function() {
    $(this).attr('src', img).fadeIn('normal');
  });
}


function news() {
  this._tickerTimer;
}

var _tickerTimer;
var tickerTimerTime = 4000;
var currentNewsItem = 0;

function swapNews() {
  console.log('New news: %s', rss[currentNewsItem].title);
  console.log(rss[currentNewsItem]);
  $('#banner h2').fadeOut('slow',function() {
    $(this).html(rss[currentNewsItem].title).fadeIn('slow');
    if(rss[currentNewsItem].lon != null && rss[currentNewsItem].lat != null ) {
      if(worldMap.scale != 1) {
        console.log('Using zooming pan');
        worldMap.zoomPanTo(1, rss[currentNewsItem].lon, rss[currentNewsItem].lat);
      }
      else
        worldMap.panTo(rss[currentNewsItem].lon, rss[currentNewsItem].lat);

      worldMap.setPointer(rss[currentNewsItem].lon, rss[currentNewsItem].lat, rss[currentNewsItem].city);
    } else {
      worldMap.zoomOut().hidePointer();
    }

    if(rss[currentNewsItem].img != null)
      swapStoryImg(rss[currentNewsItem].img);

    currentNewsItem = ++currentNewsItem % rss.length;

  });

  _tickerTimer = setTimeout(function() {
    swapNews();
  }, tickerTimerTime);
}

// Init banner
$('#banner').hover(function() {
  clearTimeout(_tickerTimer);
}, function() {
  _tickerTimer = setTimeout(function() {
    swapNews();
  }, tickerTimerTime);
});

$('#pointer').hover(function() {
  clearTimeout(_tickerTimer);
}, function() {
  _tickerTimer = setTimeout(function() {
    swapNews();
  }, tickerTimerTime);
});


jQuery(document).ready(function($) {
  swapNews();
})

// Handle map clicking
/*
$('#map').click(function(e) {

  clearTimeout(_tickerTimer);
  _tickerTimer = setTimeout(function() {
    swapNews();
  }, tickerTimerTime);

  var viewX = parseInt($(this.parentNode).css('width'));
  var viewY = parseInt($(this.parentNode).css('height'));

  var x = e.pageX - this.parentNode.offsetLeft - viewX/2;
  var y = e.pageY - this.parentNode.offsetTop - viewY/2; 

  x = parseInt($(this).css('left'))-x;
  y = parseInt($(this).css('top'))-y;

  // TODO: kuvan koko
  if(y>0)
    y = 0;
  else if(y<-1024+400)
    y = -1024+400;

  if(x>0)
    x = 0;
  else if(x<-2048+600)
    x = -2048+600;

  $('#map').animate({
    left: x,
    top: y
  }, 1000, 'easeInOutQuint');

});
*/

    </script>
  </body>
</html>