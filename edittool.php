<?php

// STOMA
require("config.php");

header('Content-Type: text/html; charset=utf-8');

// possible parameters are
// t=tool number, mandatory
// td=date in format YYYYMMDD (date to toggle)
//  If clicking an available date, the tool is booked that date.
//  If clicking a booked date, the tool is unbooked that date.
//  If clicking a busy date, booking/contact info is displayed
// sm=show month in format YYYYMM (assuming 01 for day), optional. If not specified, today is used
//
// no params - show all in division

// group by person holding

if (!empty($_GET["t"])) {
   $_SESSION['fvtool'] = $_GET["t"]; // tool code scanned? Update only if specified
}

// check login
if (empty($_SESSION['fvuser'])) {
//echo "You must login.";
	header('Location:login.php');
	return;
}

// get logged in person's info
if ($qr = $db->query("select * from person where ix=$_SESSION[fvuser]")) {
        $fvusr = $qr->fetch_object();
}

// check if admin
if ($fvusr->adminlevel < 1 ) {
        echo "You (#$_SESSION[fvuser], $fvusr->persname) are not an admin.";
        return;
}


// check for special tool value "new" (create new tool, go to blank page)
if ($_GET["t"] == "new") {

// set tool ownership to creator by default
//	$qr = $db->query("insert into tool (name,person,owner) values (\"NyttVerktøy\",$_SESSION[fvuser],$_SESSION[fvuser])");
// set tool ownership to warehouse 2 (FIXME should be a global/company variable)
	$qr = $db->query("insert into tool (name,person,owner,tag) values (\"NyttVerktøy\",2,2,\"230-\")"); // hardwired to holder&owner=2, not good.
	$_SESSION['fvtool'] = $db->insert_id; // last inserted tool
	header("Location:edittool.php?t=$db->insert_id");
	return; // redirect to newly created tool
}


// html scripts
$scripts = "";
// body content
$out="";



// Process form
if ($_POST['uploadaction'] == "uploading") {

/*Navn <input name=\"fname\" value=\"$tool->name\"><br>
TAG <input name=\"ftag\" value=\"$tool->tag\"><br>
Serienummer <input name=\"fserialno\" value=\"$tool->serialno\"><br>
Møterom <input name=\"fbookhours\" value=\"$tool->bookhours\"><br>
Dele med andre <input name=\"fshared\" value=\"$tool->shared\"><br>
Forsvunnet <input name=\"flost\" value=\"$tool->islost\"><br>
Eier <input name=\"fowner\" value=\"$tool->owner\"><br>
Neste kalibrering <input name=\"fnextcalibration\" value=\"$tool->nextcalibration\"><br>
Dager mellom kalibreringer <input name=\"fcalibrationperiod\" value=\"$tool->calibrationperiod\"><br>*/

if (isset($_POST["fbookhours"])) {
	$fbset = 1;
} else {
	$fbset = 0;
}

if (isset($_POST["fshared"])) {
	$fshar = 1;
} else {
	$fshar = 0;
}

if (isset($_POST["flost"])) {
	$flost = 1;
} else {
	$flost = 0;
}

if ($_POST["fcalibrationperiod"] == "") {
	$fcal=0;
} else {
	$fcal=$_POST[fcalibrationperiod]; // FIXME should get int value and sanity check it
}

$tagval = $_POST["tagpart1"] . $_POST["ftag"];

// prep bools, etc
	$qs =             "update tool set
			  name=\"$_POST[fname]\",
			  tag=\"$tagval\",
			  serialno=\"$_POST[fserialno]\",
			  bookhours=$fbset,
			  shared=$fshar,
			  islost=$flost,
			  owner=$_POST[fowner],
			  nextcalibration=\"$_POST[fnextcalibration]\",
			  calibrationperiod=$fcal
			  where ix=$_SESSION[fvtool]";

//echo $qs . "<br>";
	$qr = $db->query($qs);



// create tool link QR code
qrcode("$siteurl$sitebase?t=$_SESSION[fvtool]","uploads/qrToolLink_$_SESSION[fvtool].png","Vnr:$tagval\n" . substr($_POST["fname"],0,20));
// create booking link QR code
qrcode("$siteurl${sitebase}c.php?t=$_SESSION[fvtool]","uploads/qrToolBook_$_SESSION[fvtool].png","B:$tagval");


// PROCESS picture upload

//	$out .= "uploading a file";
	$target_dir = "uploads/";
//	$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
	$target_file = $target_dir . $_SESSION['fvtool'] . ".jpg";
	$uploadOk = 1;
	$imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
	// Check if image file is a actual image or fake image
	if(isset($_POST["submit"])) {
	if(file_exists($_FILES["fileToUpload"]["tmp_name"])) {
	    $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
	    if($check !== false) {
//	        echo "File is an image - " . $check["mime"] . ".";
	        $uploadOk = 1;
	    } else {
//	        echo "File is not an image.";
	        $uploadOk = 0;
	    }
	   if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
//        echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";
	try {
	    generateThumbnail("$target_file", 200, 50, 65);
	}
	catch (ImagickException $e) {
	    echo $e->getMessage();
	}
	catch (Exception $e) {
	    echo $e->getMessage();
	}
//	 echo "File saved as $target_file";
	    } else {
	        echo "Sorry, there was an error uploading your file.";
	    }
	}
	}


// PROCESS calibration certificate

//	$out .= "uploading a file";
	$target_dir = "uploads/";
//	$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
	$target_file = $target_dir . $_SESSION['fvtool'] . "_c.jpg";
	$uploadOk = 1;
	$imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
	// Check if image file is a actual image or fake image
	if(isset($_POST["submit"])) {
	if(file_exists($_FILES["calibToUpload"]["tmp_name"])) {
	    $check = getimagesize($_FILES["calibToUpload"]["tmp_name"]);
	    if($check !== false) {
//	        echo "File is an image - " . $check["mime"] . ".";
	        $uploadOk = 1;
	    } else {
//	        echo "File is not an image.";
	        $uploadOk = 0;
	    }
	   if (move_uploaded_file($_FILES["calibToUpload"]["tmp_name"], $target_file)) {
//        echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";
	try {
	    generateThumbnail("$target_file", 100, 50, 65);
	}
	catch (ImagickException $e) {
	    echo $e->getMessage();
	}
	catch (Exception $e) {
	    echo $e->getMessage();
	}
//	 echo "File saved as $target_file";
	    } else {
	        echo "Sorry, there was an error uploading your file.";
	    }
	}
	}
}




$qr = $db->query("select * from tool where ix=$_SESSION[fvtool]");
$tool = $qr->fetch_object();


// force thumbnail refresh
$thbref .= time();

// DRAW FORM
$prevtool=intval($_SESSION["fvtool"]) -1;
if ($prevtool <1) { // Bounds check
  $prevtool = 1;
}
$nexttool=intval($_SESSION["fvtool"]) +1;

$mtq = $db->query("select MAX(ix) as maxix from tool");
$mx = $mtq->fetch_object();

//DEBUG
//var_dump($mx);
if ($nexttool > $mx->maxix) { // Bounds check
  //$nexttool = $mx->maxix;
  $nexttool = "new"; // create new on out of bounds
}

$out .="
Redigere <a href=\"index.php?t=$_SESSION[fvtool]\" title=\"Gå til\">verktøy $_SESSION[fvtool]</a>
<a href=\"edittool.php?t=$prevtool\"><img src=\"pix/arrow_left.png\" style=\"width: 1em;\" title=\"Forrige\" alt=\"&lt;\"></a>
<a href=\"edittool.php?t=$nexttool\"><img src=\"pix/arrow_right.png\" style=\"width: 1em;\" title=\"Neste\" alt=\"&gt;\"></a>
<a href=\"admin.php\"><img src=\"pix/list-icon.png\" style=\"width: 1em;\" title=\"Adminside\" alt=\"---\"></a>

<form action=\"edittool.php\" method=\"post\" enctype=\"multipart/form-data\">

Navn <input name=\"fname\" value=\"$tool->name\"><br>
TAG <select name=\"tagpart1\" title=\"Ny: skriv taggen i feltet (feks 230-1234)\">";

// get unique list
// thanks to https://stackoverflow.com/questions/5734504/mysql-left-part-of-a-string-split-by-a-separator-string
$tpq = $db->query("SELECT SUBSTRING_INDEX(tag, '-', 1) as pfx from tool where tag like '%-%' group by pfx order by pfx");
while ($tp = $tpq->fetch_object() ) {
  $tagprefixes[] = $tp->pfx;
}

$splittag=strpos($tool->tag, "-"); // find hyphen

if ($splittag == 0 ){
   $out .= "<option value=\"\" selected>(Ny)</option>\n";
   $tag1 = "";
   $tag2 = $tool->tag;
} else {
   $out .= "<option value=\"\">(Ny)</option>\n";
   $tag1 = substr($tool->tag,0,$splittag); // SIR-
   $tag2 = substr($tool->tag,$splittag+1,100); // -001
}

//var_dump($tag1);
//var_dump($tag2);
foreach($tagprefixes as $tp) {
//var_dump($tp);
   if ($tag1 == $tp) {
      $out .= "<option value=\"$tp-\" selected>$tp</option>\n";
   } else {
      $out .= "<option value=\"$tp-\">$tp</option>\n";
   }
}


$out .= "</select>
<input name=\"ftag\" value=\"$tag2\"><br>
Serienummer <input name=\"fserialno\" value=\"$tool->serialno\"><br>";


$chktxt = "";
if ($tool->bookhours == 1) {
	$chktxt = "checked";
}
// this is hidden for now, on ØK request
$out .= "<span style=\"display:none;\">Møterom <input type=\"checkbox\" name=\"fbookhours\" $chktxt><br></span>";


$chktxt = "";
if ($tool->shared == 1) {
	$chktxt = "checked";
}


$out .= "Delt verktøy <input type=\"checkbox\" name=\"fshared\" $chktxt><br>";

$chktxt = "";
if ($tool->islost == 1) {
        $chktxt = "checked";
}


$out .= "
Forsvunnet <input type=\"checkbox\"   name=\"flost\" $chktxt><br>
Eier <select name=\"fowner\">
";

// $qr = $db->query("select * from person");

$qr = $db->query("select * from person order by persname");
while ($prs = $qr->fetch_object()) {

$chktxt = "";
if ($prs->ix == $tool->owner) {
        $chktxt = "selected";
}

	$out .= "  <option value=\"$prs->ix\" $chktxt >$prs->persname</option>\n";
}


$chktxt = "";
$showcalib="style=\"display:none;\"";
if ($tool->calibrationperiod > 0) {
        $chktxt = "checked";
	$showcalib=""; // just remove hide css
}

// FIXME need to add js to hide/show span

$out .= "</select><br>

Kalibrere? <input type=\"checkbox\" id=\"calibtogl\" name=\"fcalibrate\" $chktxt onChange=\"calibtoggle();\"><br>
<span id=\"calibspan\" $showcalib>
Dager mellom kalibreringer <input  name=\"fcalibrationperiod\" value=\"$tool->calibrationperiod\"><br>
Neste kalibrering <input type=\"date\" name=\"fnextcalibration\" value=\"$tool->nextcalibration\"><br>
</span>

<div style=\"border: 1px solid; display:inline-block;clear:left;\">
<a href =\"uploads/$_SESSION[fvtool].jpg\" target=\"_blank\">
    <img src=\"uploads/$_SESSION[fvtool]_thumb.jpg?$thbref\" style=\"width: 10em;\" alt=\"ikke noe bilde (ennå)\"></a><br>
    <input type=\"file\" name=\"fileToUpload\" id=\"fileToUpload\"><BR>
</div><br>
<div style=\"border: 1px solid; display:inline-block;clear:left;\">
<a href =\"uploads/$_SESSION[fvtool]_c.jpg\" target=\"_blank\">
    <img src=\"uploads/$_SESSION[fvtool]_c_thumb.jpg?$thbref\" style=\"width: 10em;\" alt=\"ikke noe kalibreringssertifikat (ennå)\"></a><br>
    <input type=\"file\" name=\"calibToUpload\" id=\"calibToUpload\"><br>
</div><p>
    <input type=\"hidden\" name=\"uploadaction\" value=\"uploading\">
    <input type=\"submit\" value=\"Lagre\" name=\"submit\">
</form>
VerktøyQR:
<a href=\"qr.php?f=qrToolLink_$_SESSION[fvtool].png\">
<img src=\"uploads/qrToolLink_$_SESSION[fvtool].png\"></a>
Booking-QR:
<a href=\"qr.php?f=qrToolBook_$_SESSION[fvtool].png\">
<img src=\"uploads/qrToolBook_$_SESSION[fvtool].png\"></a>
<hr>
<a href=\"edittool.php?t=new\">Nytt verktøy</a> - <a href=\"admin.php\">Adminside</a>
<p>
"; // this div is the calendar container


// Page end
$out .= "</body></html>";

// page starts here
echo "<html>
<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
$metarefresh
<title>Fellesverktøy</title>
<style>
.cw {
	width: 12%;
	display: inline-block;
	position: relative;
}
.gray {
	border-color: gray !important;
	color:gray !important;
}
.cw:after {
	padding-top:89%;
	/* height/width ratio in % */
	display: block;
	content: '';
}

.calday {
  position: absolute;
  top: 0;
  bottom: 0;
  right: 0;
  left: 0;
  /* fill parent */
//  background: url('http://teigseth.no/fv/pix/give.png') no-repeat;
//  background-size:contain;

	border: solid 1px;
	border-color: green;
        padding: 4px;
        border-radius: 5px;
/*	width:100px;
	max-width:13%;
	float:left;
	text-align:center;
        text-align: center;
	background-size:contain;*/
}
.stimg {
	max-width:100%;
}
 /* unvisited link */
a:link {
    color: darkgreen;
    text-decoration:none;
    font-style:normal;
    font-weight:bold;
}

/* visited link */
a:visited {
    color: darkgreen;
    text-decoration:none;
}

/* mouse over link */
a:hover {
    color: darkgreen;
    text-decoration:underline;
}
/* selected link */
a:active {
    color: darkgreen;
    color: underline;
}
.ul {
	text-decoration:underline double !important;
}
body {
	background: #DBFEDB;
}
.holder {
	background: #BAF0BA;
	padding: 5px;
	border-radius: 15px;
	text-align: center;
}
.center {
	text-align: center;
}
tr:nth-child(2) {
	background: #BAF0BA;
}
.actionmsg {
    margin:20px 20px;
    display:inline-block;
    padding: 7px;
    border-radius: 8px;
    -webkit-box-shadow:0 0 20px blue;
    -moz-box-shadow: 0 0 20px blue;
    box-shadow:0 0 20px blue;
}
.redshadow {
    -webkit-box-shadow:0 0 20px red;
    -moz-box-shadow: 0 0 20px red;
    box-shadow:0 0 20px red;
}
.tl {
	max-width:25%;
}
.toolbox {
	border: solid;
	border-radius: 15px;
	padding: 8px;
	margin: 1px;
	float: left;
}
.contain {
	display: inline-block;
}
</style>
<script>
function calibtoggle() {
    var calibspan =     document.getElementById('calibspan');
    var calibtogl =     document.getElementById('calibtogl');

    if (calibtogl.checked) {
    	calibspan.style = \"\";
    	// should add appear transitions here
    } else {
    	calibspan.style = \"display:none;\"; 
    	// should add appear transitions here
    }
}
</script>
<body>";
echo $out;

// check if this tool's division is the same as logged-in user's
//echo "Assign Tool no $_GET[t] to logged-in user $usr";

// no qr code parameter: Display tools list
?>
