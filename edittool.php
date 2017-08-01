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


// check for special tool value "new" (create new tool, go to blank page)
if ($_GET["t"] == "new") {
	$qr = $db->query("insert into tool (name,person,owner) values (\"NyttVerktøy\",$_SESSION[fvuser],$_SESSION[fvuser])");
	$_SESSION['fvtool'] = $db->insert_id; // last inserted tool
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

// prep bools, etc
	$qs =             "update tool set
			  name=\"$_POST[fname]\",
			  tag=\"$_POST[ftag]\",
			  serialno=\"$_POST[fserialno]\",
			  bookhours=$fbset,
			  shared=$fshar,
			  islost=$flost,
			  owner=$_POST[fowner],
			  nextcalibration=\"$_POST[fnextcalibration]\",
			  calibrationperiod=$_POST[fcalibrationperiod]
			  where ix=$_SESSION[fvtool]";

//echo $qs . "<br>";
	$qr = $db->query($qs);

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
$out .="
Redigere <a href=\"index.php?t=$_SESSON[fvtool]\">verktøy $_SESSION[fvtool]</a>

<form action=\"edittool.php\" method=\"post\" enctype=\"multipart/form-data\">

Navn <input name=\"fname\" value=\"$tool->name\"><br>
TAG <input name=\"ftag\" value=\"$tool->tag\"><br>
Serienummer <input name=\"fserialno\" value=\"$tool->serialno\"><br>";

$chktxt = "";
if ($tool->bookhours == 1) {
	$chktxt = "checked";
}


$out .= "Møterom <input type=\"checkbox\" name=\"fbookhours\" $chktxt><br>";

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

	$out .= "<option value=\"$prs->ix\" $chktxt >$prs->persname</option>";
}



$out .= "</select><br>
Neste kalibrering <input type=\"date\" name=\"fnextcalibration\" value=\"$tool->nextcalibration\"><br>
Dager mellom kalibreringer <input name=\"fcalibrationperiod\" value=\"$tool->calibrationperiod\"><br>

<a href =\"uploads/$_SESSION[fvtool].jpg\" target=\"_blank\"><img src=\"uploads/$_SESSION[fvtool]_thumb.jpg?$thbref\" alt=\"ikke noe bilde (ennå)\"></a>
    Bilde:
    <input type=\"file\" name=\"fileToUpload\" id=\"fileToUpload\"><BR>
<a href =\"uploads/$_SESSION[fvtool]_c.jpg\" target=\"_blank\"><img src=\"uploads/$_SESSION[fvtool]_c_thumb.jpg?$thbref\" alt=\"(finnes ikke)\"></a>
    Kalibreringssertifikat:
    <input type=\"file\" name=\"calibToUpload\" id=\"calibToUpload\"><br>
    <input type=\"hidden\" name=\"uploadaction\" value=\"uploading\">
    <input type=\"submit\" value=\"Lagre\" name=\"submit\">
</form>
<p>
"; // this div is the calendar container



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
<body>";
echo $out;

// check if this tool's division is the same as logged-in user's
//echo "Assign Tool no $_GET[t] to logged-in user $usr";

// no qr code parameter: Display tools list
?>
