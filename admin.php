<?php

// STOMA
require("config.php");

header('Content-Type: text/html; charset=utf-8');

// possible parameters are
// w=number mandatory
// p=w - show all in warehouse no.n
//  subparams:
//    o=o show tools OUT
//    o=i show tools IN
//    o= show ALL tools
//
//
//
// no params - show all in division

// group by person holding


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


// list all persons, tools, warehouses, meeting rooms...

// get logged in person's info
if ($qr = $db->query("select * from person where ix=$_SESSION[fvuser]")) {
	$fvusr = $qr->fetch_object();
}

// check if admin
if ($fvusr->adminlevel < 1 ) {
	echo "You (#$_SESSION[fvuser], $fvusr->persname) are not an admin.";
	return;
}


$out .= "<h2>Administrasjon</h2>";

// LIST TOOLS
$out .= "<h3>Verktøy</h3>
<table class=\"sortable\">
<tr>
 <th>Verktøy</th>
 <th>TAG</th>
 <th>Eier</th>
</tr>
";
// <th>Serienr</th>

// FIXME add admin layer (division) here
// if only division admin
if ($qr = $db->query("select tag,serialno,name,persname,person.division,tool.ix,owner
                      from tool,person
                      where tool.owner=person.ix
                       and division=$fvusr->division
                       and bookhours=0
                      order by tag")) {

	while ($tool = $qr->fetch_object()) {
		$out .= "<tr>
		 <td><a href=\"edittool.php?t=$tool->ix\">$tool->name</a></td>
		 <td><a href=\"edittool.php?t=$tool->ix\">$tool->tag</a></td>
		 <td><a href=\"editpers.php?t=$tool->owner\">$tool->persname</a></td>
		</tr>\n";
/*	

		 <td><a href=\"edittool.php?t=$tool->ix\">$tool->serialno</a></td>


	$out .= "
		<a href=\"index.php?t=$tool->ix\">$tool->name</a> -
		<a href=\"edittool.php?t=$tool->ix\">Rediger</a>
		<br>";*/

	}
}

$out .= "</table>";
$out .= "<a href=\"edittool.php?t=new\"> - - Nytt verktøy- - </a><br>";

// LIST MEETING ROOMS
$out .= "<h3>Møterom</h3>";

// FIXME add admin layer (division) here
// if only division admin
if ($qr = $db->query("select name,persname,person.division,tool.ix from tool,person where tool.owner=person.ix and division=$fvusr->division and bookhours=1 order by name")) {
	while ($tool = $qr->fetch_object()) {
		$out .= "
		<a href=\"index.php?t=$tool->ix\">$tool->name</a> -
		<a href=\"edittool.php?t=$tool->ix\">Rediger</a>
		<br>";
	}
	$out .= "<a href=\"edittool.php?t=new\"> - - Nytt møterom- - </a><br>";
}

// LIST People
$out .= "<h3>Folk</h3>";

// FIXME add admin layer (division) here
// if only division admin
if ($qr = $db->query("select * from person where division=$fvusr->division and iswarehouse=0 order by persname")) {
	while ($pers = $qr->fetch_object()) {
		$out .= "<a href=\"index.php?p=w&amp;w=$pers->ix\">$pers->persname</a> -
		<a href=\"editpers.php?w=$pers->ix\">Rediger</a>
		<br>";
	}
	$out .= "<a href=\"editpers.php?w=new\"> - - Ny person - - </a><br>";
}


// LIST Warehouses
$out .= "<h3>Lagre</h3>";

// FIXME add admin layer (division) here
// if only division admin
if ($qr = $db->query("select * from person where division=$fvusr->division and iswarehouse=1 order by persname")) {
	while ($pers = $qr->fetch_object()) {
		$out .= "<a href=\"index.php?p=w&amp;w=$pers->ix\">$pers->persname</a> -
		<a href=\"editpers.php?w=$pers->ix\">Rediger</a>
		<br>";
	}
	$out .= "<a href=\"editpers.php?w=new\"> - - Nytt lager - - </a><br>";
}

$out .= "<h3>Innstillinger</h3>";

$out .= "Her kommer eventuelle globale innstillinger (nytt verktøy default i lager…, Tag starter med…)";

// page starts here
echo "<html>
<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
$metarefresh
<script src=\"js/sorttable.js\"></script>
<title>Fellesverktøy</title>
<style>

/* Sortable tables */
table.sortable thead {
    background-color:#00ec3d;
    color:#666666;
    font-weight: bold;
    cursor: default;
}
/* tr:nth-child(even), */
table.sortable td:nth-child(even) {
    background-color:#D2FED2;
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
	clear: left;
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
