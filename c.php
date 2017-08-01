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

// use today, or speficied month
if ( empty($_GET["sm"]) ) {
	$usedate=time();
} else {
	$usedate=strtotime("$_GET[sm]-01");
}

$nowyr = date('Y');
$nowmth = date('m');


$yr = date('Y', $usedate);
$mth = date('m', $usedate);
$fdaymth = strtotime("$yr-$mth-01");

$day = date('w', $fdaymth) - 1; // In Norway Monday is day 1 each week, so for first day of week to be Monday, we must "shift" -1
if ($day == -1) {
	$day = 6;
}

//$week_start = date('Y-m-d', strtotime('-'.$day.' days'));
$week_start = date('Y-m-d', strtotime(date('Y-m-d',$fdaymth). ' - ' . $day . ' days'));
// $day contains a number from 0 to 6 representing the day of the week (Sunday = 0, Monday = 1, etc.).
// $week_start contains the date for Sunday of the current week as mm-dd-yyyy.
// http://stackoverflow.com/questions/1897727/get-first-day-of-week-in-php

// $week_start contains the first monday of the week of the specified month

/*$out .="now is " . date('Y-m-d',$usedate) . ",year $yr month $mth firstdaymonth was a $day:
	" . date('Y-m-d',$fdaymth) . "first monday of first week that month was $week_start and the next was
	" . date('Y-m-d', strtotime($week_start. ' + 1 days'));
*/

// for use in next/last month links
$ny=$yr; // next=default this year
$ly=$yr; // last=default this year

$lm = $mth - 1; //last month
if ($lm == 0) {
	$lm = 12; //last month
	$ly -= 1; // decrease year as well
}

$nm = $mth + 1; //next month
if ($nm == 13) {
	$lm = 1; //last month
	$ny += 1;// increase year as well
}

// fetch tool info
$qs = "select * from tool where ix=" . mysql_escape_string($_GET["t"]);
if ($tqr = $db->query($qs)) {
	$tool=$tqr->fetch_object();
}

//var_dump($tool);
if ($tool->bookhours == "1") { // redirect to meeting room booking if bookhours is set...
   header("Location: m.php?t=$tool->ix");
   return;
}


$out .="
Bookingkalender for <a href=\"index.php?t=$tool->ix\">$tool->name</a><p>
<div class=\"toolbox\">
<a href=\"c.php?t=$_SESSION[fvtool]&amp;sm=$ly-$lm\"> &lt; Forrige</a> -
<a href=\"c.php?t=$_SESSION[fvtool]&amp;sm=$nowyr-$nowmth\" title=\"Gå til i dag\">
$mth/$yr</a> -
<a href=\"c.php?t=$_SESSION[fvtool]&amp;sm=$ny-$nm\">Neste &gt;</a>
</div>
<div>"; // this div is the calendar container


// fetch bookings and put into array
// select * from booking where date like "$sm%"
$qs = "select booking.person,booking.date,person.persname
       from booking,person
       where date like \"$yr-$mth%\" and tool=$_SESSION[fvtool] and booking.person=person.ix";
//echo $qs;

$booked=array();

if ($qr = $db->query($qs)) {
    while ($bk = $qr->fetch_object()){
//	$out .= "tool $bk->tool booked by $bk->person on $bk->date<p>";
	$booked["$bk->date"]["id"] = $bk->person; // add to array
	$booked["$bk->date"]["person"] = $bk->persname; // add to array
//	$booked["$bk->date"]["person"] = "Navn"; // add to array
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

	if ( !empty($booked[$selector]["id"])) { // entry exists, delete it
//FIXME should check if is_owner
		if ($booked[$selector]["id"] == $_SESSION['fvuser']) {
			unset($booked[$selector]["id"]);
			// delete from database as well
			$qs = "delete from booking where tool=$_SESSION[fvtool] and date=\"$selector\" "; // don't touch by-hour-bookings
			header("Location:c.php?t=$_SESSION[fvtool]&sm=$_GET[sm]"); // redirect to avoid refresh toggle loop
		} else { // you are not the owner of this booking
			$out .= "<b>OOPS! Det er ikke du som har booket den dagen. Kontakt <a href=\"tools.php?p=w&amp;w=". $booked[$selector]["id"]. "\">". $booked[$selector]["person"] . "</a> for nærmere avtale.</b><p>";
		}
//	echo "DEL $qs DEL";
		$qr = $db->query($qs);
	} else {
		$booked[$selector]["id"] = $_SESSION['fvuser']; // book it
		// write to database as well
		$qs = "insert into booking (person, tool, date) values ($_SESSION[fvuser],$_SESSION[fvtool],\"$selector\")";
		$qr = $db->query($qs);
		header("Location:c.php?t=$_SESSION[fvtool]&sm=$_GET[sm]"); // redirect to avoid refresh toggle loop
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

		$lnkdate =strtotime($week_start. " + $cntr days");
		$lnk="c.php?t=$_SESSION[fvtool]&amp;sm=$_GET[sm]&amp;td=" . date('Y-m-d', $lnkdate); // NB draw month from that...
/*		if ($nd == 21) {
			$img="background:url('pix/daytaken.png') no-repeat";
			$titl="Booket av deg";
		}
		if ($nd == 8) {
			$img="background:url('pix/daybusy.png') no-repeat";
			$titl="Opptatt (navn)";
		}
*/

		if ($lnkdate < time()) {
			$lnk="#";
			$titl="Kan ikke booke i fortiden";
		}

		$selector=date('Y-m-d', strtotime($week_start. " + $cntr days"));
		if (!empty($booked[$selector]["id"])) {
			if ($booked[$selector]["id"] == $_SESSION['fvuser']) { // booked myself
				$img="background:url('pix/daytaken.png') no-repeat";
				$titl="Booket av deg";
			} else { // someone else booked this day
				$img="background:url('pix/daybusy.png') no-repeat";
				$titl="Booket av " .$booked[$selector]["person"];
			}
		}


		if (date('m', strtotime($week_start. " + $cntr days")) <> $mth ) { // IF out of month range, create monthflip links
//			$img="background:lightgray"; // should fetch correct image anyway
			$bordoverride="gray"; // USE CSS!
			$linkoverride="gray"; // USE CSS!
			$titl="Bla";
			$lnk = "c.php?t=1&amp;sm=" . date('Y-m', strtotime($week_start. " + $cntr days"));
		}


		$out .= "
	<div class=\"cw\">
<a href=\"$lnk\" title=\"$titl\" class=\"$linkoverride\">
   <div class=\"calday $bordoverride\" style=\"$img; background-size:contain;$bordoverride\">
			$nd
		</div>
	</a></div>"; // this scale-background-to-div-respecting-aspect-ratio-trick is thanks to http://stackoverflow.com/questions/8200204/fit-background-image-to-div and http://stackoverflow.com/questions/12121090/responsively-change-div-size-keeping-aspect-ratio together
//		if ( $nd == 31 ) { break; } // draw other days, etc
		$cntr++; // let's count
	}
	$out .= "</div>"; // end of week
}
	$out .= "<div class=\"toolbox\">
	         Fremtidige bookinger (måneder) her…
	         </div>";

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
	border-color: lightgray !important;
	color:lightgray !important;
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
