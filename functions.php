<?php

// STOMA site-wide functions

// This file is called from config.php

// Connect to database
//   $dbconn = mysql_connect($dbhost, $dbuser, $dbpassword) or die(mysql_error());
//   $db = mysql_select_db($dbname) or die(mysql_error());

// random_bytes support
// from https://github.com/paragonie/random_compat
require_once('random_compat/lib/random.php');
include('phpqrcode/qrlib.php');

  $db = new mysqli($dbhost, $dbuser, $dbpassword, $dbname);

  /* create database connection */
  if ($db->connect_errno) {
    printf("Database connection failed: %s\n", $db->connect_error);
    exit();
  }
  mysqli_set_charset($db,"utf8");

  session_start(); // start/resume session

  // if no SESSION userid is set, try to login with cookie if that matches database:
  // set logged in user to $_SESSION['fvuser'] on success
  if (empty($_SESSION['fvuser']) && !empty($_COOKIE['remembertools'])) {

    list($selector, $authenticator) = explode(':', $_COOKIE['remembertools']);
    $qs = "SELECT * FROM auth_tokens
                       WHERE selector = \"$selector\"";
    $qr = $db->query($qs);
    $row = $qr->fetch_object();
    if (empty($row)) {
       // could be a spoof attempt
       logg(NULL,NULL,$_SESSION['fvuser'],NULL,"Client supplied cookie not found.");
    } else {
       // does the cookie token match the one stored in db?
       // Note, deactivated users' tokens should be purged from auth_tokens
       if (hash_equals($row->token, hash('sha256', base64_decode($authenticator)))) {

          $_SESSION['fvuser'] = $row->userid;
          // Then regenerate login token as above
	  generatelogintoken($db, $_SESSION['fvuser']);
       }
    }
  } // end of login



// FUNCTIONS // // // // // //

	function qrcode($codeText,$qrfnam,$tagval) { // returns nothing
// PROCESS QR CODE
//$codeText = $_SESSION[fvtool]; // content to qrencode
//$qrfnam = "uploads/qrkode_$param.png";

// write QR code to disk
QRcode::png($codeText, $qrfnam,QR_ECLEVEL_L, 4); //http://phpqrcode.sourceforge.net/examples/index.php?example=006

/* Create some objects */
$image = new Imagick();
$draw = new ImagickDraw();
$pixel = new ImagickPixel( 'white' );

// read qr code into memory
$image = new Imagick($qrfnam);
// text box
$image->newImage(116, 20, $pixel);
$draw->setFillColor('black');
$draw->setFontSize( 15 );
$draw->setGravity(Imagick::GRAVITY_CENTER);
$image->annotateImage($draw, 0, 0, 0, "$tagval");
$image->resetIterator();
$qrout = $image->appendimages(true);

$qrout->writeImage($qrfnam);

//echo "AAA<img src=\"$qrfnam\">AAA"  ;

	}




	function logg($comp, $div, $pers, $tool, $txt) {
		global $db;
// http://stackoverflow.com/questions/5398674/get-users-current-location-in-php
		$user_ip = $_SERVER["REMOTE_ADDR"];
		$geo = unserialize(file_get_contents("http://www.geoplugin.net/php.gp?ip=$user_ip"));
		$country = $geo["geoplugin_countryName"];
		$city = $geo["geoplugin_city"];

		$logsql = "insert into log
	   (ip, location, date, company, division, person, tool, text) values 
           ( \"$_SERVER[REMOTE_ADDR]\", \"$country/$city\",convert_tz(now(),@@session.time_zone,'CET'), 
	   NULLIF('$comp',''), NULLIF('$div',''), NULLIF('$person',''), NULLIF('$tool',''), \"$txt\")";
		if ($pq = $db->query($logsql)) {
			// all is ok
		} else {
			echo "log sql failed: $logsql ";
		}
		return true;
	} // end function logg

// generate thumbnail
function generateThumbnail($img, $width, $height, $quality = 90)
{
    if (is_file($img)) {
        $imagick = new Imagick(realpath($img));
        $imagick->setImageFormat('jpeg');
        $imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
        $imagick->setImageCompressionQuality($quality);
        $imagick->thumbnailImage($width, $height, true, false); // param 3 is bestfit
        $filename_no_ext = reset(explode('.', $img));
        if (file_put_contents($filename_no_ext . '_thumb' . '.jpg', $imagick) === false) {
            throw new Exception("Could not put contents.");
        }
        return true;
    }
    else {
        throw new Exception("No valid image provided with {$img}.");
    }
}


// sets session fvuser and cookie
function generatelogintoken($database, $usrid) {


// VERY VERY BAD HACK
//    $selector = base64_encode("012345678");
//    $authenticator = "012345678901234567890123456789012";

    $selector = base64_encode(random_bytes(9));
    $authenticator = random_bytes(33);

    $_SESSION['fvuser'] = $usrid;

    setcookie(
        'remembertools',
         $selector.':'.base64_encode($authenticator),
         time() + 864000,
         $sitebase,
         $siteurl,
         false, // true for TLS-only
         true  // http-only
    );
// in logout:  setcookie('remembertools', '',  time() -3600, $sitebase, $siteurl, false, true);

// UGLY hack should use parameterization
    $qs = "INSERT INTO auth_tokens
         (selector, token, userid, expires)
         VALUES (\"$selector\", \"" . hash('sha256', $authenticator) . "\",
                  $usrid, \"" . date('Y-m-d\TH:i:s', time() + 864000) . "\")";
    $database->query($qs );
} // end function generatelogintoken


?>
