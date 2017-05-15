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
//   echo "set fvtool";

}
//  echo "fvtool is $_SESSION[fvtool]";

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


$qs="select person.persname,person.ix,person.iswarehouse
     from   person,tool,person as owner
     where person.division=owner.division
       and owner.ix=tool.person
       and person.active = 1
       and tool.ix=$_SESSION[fvtool]
     order by person.iswarehouse desc, person.persname";


//$out .="$qs …Personliste her…<p><a href=\"logout.php\">Logg ut</a>";
$out .="Liste over lagere/personer, klikk for å se verktøy:  |  <a href=\"logout.php\">Logg ut</a><hr>";

if ($qr = $db->query($qs)) {

$isw = 1;
while ($prs = $qr->fetch_object()) {
	if ( $prs->iswarehouse == 1 && $isw == 1) {
		$out .= "<b>Lagere:</b><p>";
		$isw = 0;
	}
	if ( $prs->iswarehouse == 0 && $isw == 0) {
		$out .= "<b>Personer:</b><p>";
		$isw = 1;
	}
	$out .= "
$prs->persname -
<a href=\"tools.php?p=h&amp;w=$prs->ix\">Vis I Hende</a> -
<a href=\"tools.php?p=w&amp;w=$prs->ix\">Vis eide verktøy</a>
<p>";
}

}

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
