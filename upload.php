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


// variables could be passed on to login
if (!empty($_GET["t"])) {
   $_SESSION['fvtool'] = mysql_escape_string($_GET["t"]);   // tool no. code scanned?
}

if (empty($_GET["p"])) { // if nothing specified, set to w
   $_GET["p"] = "w";
}

// check login
if (empty($_SESSION['fvuser'])) {
//echo "You must login.";
	header('Location:login.php');
	return;
	}



// html scripts
$scripts = "";
// body content
$out="";

if ($_POST['uploadaction'] == "uploading") {
	$out .= "uploading a file";
$target_dir = "uploads/";
$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
$target_file = $target_dir . $_SESSION['fvtool'] . ".jpg";
$uploadOk = 1;
$imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
// Check if image file is a actual image or fake image
if(isset($_POST["submit"])) {
    $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
    if($check !== false) {
        echo "File is an image - " . $check["mime"] . ".";
        $uploadOk = 1;
    } else {
        echo "File is not an image.";
        $uploadOk = 0;
    }
   if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
        echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";
try {
    generateThumbnail("$target_file", 100, 50, 65);
}
catch (ImagickException $e) {
    echo $e->getMessage();
}
catch (Exception $e) {
    echo $e->getMessage();
}

 echo "File saved as $target_file";
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
}


}

$out .="
Bilde <a href=\"index.php?t=$_SESSON[fvtool]\">verktøy $_GET[t]<p><img src=\"uploads/$_SESSION[fvtool]_thumb.jpg\" alt=\"ikke noe bilde (ennå)\"></a>
<form action=\"upload.php\" method=\"post\" enctype=\"multipart/form-data\">
    Select image to upload:
    <input type=\"file\" name=\"fileToUpload\" id=\"fileToUpload\">
    <input type=\"hidden\" name=\"uploadaction\" value=\"uploading\">
    <input type=\"submit\" value=\"Last opp\" name=\"submit\">
</form>
<p>
<div>"; // this div is the calendar container


// fetch bookings and put into array
// select * from booking where date like "$sm%"
$qs = "select * from booking where date like \"$yr-$mth%\"";
//echo $qs;

$booked=array();

if ($qr = $db->query($qs)) {
    while ($bk = $qr->fetch_object()){
//	$out .= "tool $bk->tool booked by $bk->person on $bk->date<p>";
	$booked["$bk->date"] = $bk->person; // add to array
    }
/* else {
       logg(NULL,NULL,NULL,$spectool,"No bookings for this tool in $yr-$mth");
       // LOG THIS
    }*/
//var_dump($booked);
}



// toggle day, if specified
if ( !empty($_GET["td"]) ){
	$selector = mysql_escape_string($_GET["td"]);

	if ( !empty($booked[$selector])) { // entry exists, delete it
//FIXME should check if is_owner
		if ($booked[$selector] == $_SESSION['fvuser']) {
			unset($booked[$selector]);
			// delete from database as well
			$qs = "delete from booking where tool=$_SESSION[fvtool] and date=\"$selector\"";
			header("Location:calendar.php?t=$_SESSION[fvtool]&sm=$_GET[sm]"); // redirect to avoid refresh toggle loop
		} else { // you are not the owner of this booking
			$out .= "<b>OOPS! Det er ikke du som har booket den dagen (men #$booked[$selector]), så du kan ikke avbooke... Kontakt (kontaktinfo her)</b><p>";
		}
//	echo "DEL $qs DEL";
		$qr = $db->query($qs);
	} else {
		$booked[$selector] = $_SESSION['fvuser']; // book it
		// write to database as well
		$qs = "insert into booking (person, tool, date) values ($_SESSION[fvuser],$_SESSION[fvtool],\"$selector\")";
		$qr = $db->query($qs);
		header("Location:calendar.php?t=$_SESSION[fvtool]&sm=$_GET[sm]"); // redirect to avoid refresh toggle loop
	}
}




$cntr=0;
for ($w=0; $w<=5; $w++) {
	if (( date('d', strtotime($week_start. " + $cntr days")) < 7 ) && ( $w > 2 )){ // exit if monday is next month, if not, go on and draw another week
		break;
	}
	$out .= "<div style=\"clear:left;\">"; // this div is the week container
	for ($d=1; $d <=7; $d++) {
		$nd = date('d', strtotime($week_start. " + $cntr days"));
//	$nd = ($w * 7) + $d-1;
		$img="background:url('pix/dayblank.png') no-repeat";
		$titl="Ledig";
		$bordoverride=""; // used for overriding border color
		$linkoverride=""; // used for overriding link color

		$lnk="calendar.php?t=1&amp;sm=$_GET[sm]&amp;td=" . date('Y-m-d', strtotime($week_start. " + $cntr days")); // NB draw month from that...
/*		if ($nd == 21) {
			$img="background:url('pix/daytaken.png') no-repeat";
			$titl="Booket av deg";
		}
		if ($nd == 8) {
			$img="background:url('pix/daybusy.png') no-repeat";
			$titl="Opptatt (navn)";
		}
*/
		$selector=date('Y-m-d', strtotime($week_start. " + $cntr days"));
		if (!empty($booked[$selector])) {
			if ($booked[$selector] == $_SESSION['fvuser']) { // booked myself
				$img="background:url('pix/daytaken.png') no-repeat";
				$titl="Booket av deg";
			} else { // someone else booked this day
				$img="background:url('pix/daybusy.png') no-repeat";
				$titl="Booket av en annen (#$booked[$selector]) ";
			}
		}

		if (date('m', strtotime($week_start. " + $cntr days")) <> $mth ) { // IF out of month range, create monthflip links
//			$img="background:lightgray"; // should fetch correct image anyway
			$bordoverride="gray"; // USE CSS!
			$linkoverride="gray"; // USE CSS!
			$titl="Bla";
			$lnk = "calendar.php?t=1&amp;sm=" . date('Y-m', strtotime($week_start. " + $cntr days"));
		}

		$out .= "
	<div class=\"cw\">
		<a href=\"$lnk\" title=\"$titl\" class=\"$linkoverride\">
		<div class=\"calday $bordoverride\" style=\"$img; background-size:contain;$bordoverride\">
			$nd
		</div></a>
	</div>"; // this scale-background-to-div-respecting-aspect-ratio-trick is thanks to http://stackoverflow.com/questions/8200204/fit-background-image-to-div and http://stackoverflow.com/questions/12121090/responsively-change-div-size-keeping-aspect-ratio together
//		if ( $nd == 31 ) { break; } // draw other days, etc
		$cntr++; // let's count
	}
	$out .= "</div>";
}

/*
// TESTING TESTING
$out .= "

<div style=\"clear:left;\">
	<div class=\"cw\">
		<div class=\"calday\" style=\"background:url('pix/dayblank.png') no-repeat;background-size:contain;\">
			1
		</div>
	</div>
	<div class=\"cw\">
		<div class=\"calday\" style=\"background:url('pix/daytaken.png') no-repeat;background-size:contain;\">
			2
		</div>
	</div>
	<div class=\"cw\">
		<div class=\"calday\" style=\"background:url('pix/daybusy.png') no-repeat;background-size:contain;\">
			3
		</div>
	</div>
	<div class=\"cw\">
		<div class=\"calday\" style=\"background:url('pix/dayblank.png') no-repeat;background-size:contain;\">
			4
		</div>
	</div>
	<div class=\"cw\">
		<div class=\"calday\" style=\"background:url('pix/dayblank.png') no-repeat;background-size:contain;\">
			5
		</div>
	</div>
	<div class=\"cw\">
		<div class=\"calday\" style=\"background:url('pix/dayblank.png') no-repeat;background-size:contain;\">
			6
		</div>
	</div>
	<div class=\"cw\">
		<div class=\"calday\" style=\"background:url('pix/dayblank.png') no-repeat;background-size:contain;\">
			7
		</div>
	</div>
</div>

<div style=\"clear:left;\">
	<div class=\"cw\">
		<div class=\"calday\" style=\"background:url('pix/dayblank.png') no-repeat;background-size:contain;\">
			1
		</div>
	</div>
	<div class=\"cw\">
		<div class=\"calday\" style=\"background:url('pix/daytaken.png') no-repeat;background-size:contain;\">
			2
		</div>
	</div>
	<div class=\"cw\">
		<div class=\"calday\" style=\"background:url('pix/daybusy.png') no-repeat;background-size:contain;\">
			3
		</div>
	</div>
	<div class=\"cw\">
		<div class=\"calday\" style=\"background:url('pix/dayblank.png') no-repeat;background-size:contain;\">
			4
		</div>
	</div>
	<div class=\"cw\">
		<div class=\"calday\" style=\"background:url('pix/dayblank.png') no-repeat;background-size:contain;\">
			5
		</div>
	</div>
	<div class=\"cw\">
		<div class=\"calday\" style=\"background:url('pix/dayblank.png') no-repeat;background-size:contain;\">
			6
		</div>
	</div>
	<div class=\"cw\">
		<div class=\"calday\" style=\"background:url('pix/dayblank.png') no-repeat;background-size:contain;\">
			14
		</div>
	</div>
</div>
";



*/






// get name of warehouse/person queried

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
