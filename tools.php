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


// variables could be passed on to login
if (!empty($_GET["w"])) {
   $_SESSION['fvwh'] = mysqli_escape_string($db, $_GET["w"]);   // warehouse no. code scanned?
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

// get, amotg other things, admin status into fvusr
if ($qr = $db->query("select * from person where ix=$_SESSION[fvuser]")) {
        $fvusr = $qr->fetch_object();
}


// html scripts
$scripts = "";
// body content
$out="";

// get name of warehouse/person queried

// nb if warehouse, check other fields (?) FIXME
if ($qr = $db->query("select * from person where ix=" . mysqli_escape_string($db, $_GET["w"]))) {

$whouse = $qr->fetch_object();

if ($_GET["all"] == "comp") {
} else {
echo "
<div class=\"toolbox\">
Kontaktinfo for $whouse->persname (<a href=\"persons.php\">vis alle</a>)
                <div class=\"holder\">
                 <a href=\"tel:$whouse->phone\"><img class=\"tl\" src=\"pix/phone.png\" alt=\"Ring\"></a>
                 <a href=\"sms:$whouse->phone?body=Hei, det er meg. Mvh \"><img  class=\"tl\" src=\"pix/sms.png\" alt=\"SMS\"></a>
                 <a href=\"mailto:$whouse->mail?subject=Hei&amp;body=Hei, det er meg\"><img class=\"tl\"  src=\"pix/mail.png\" alt=\"Mail\"></a><p>
                </div>
</div>
<div class=\"toolbox\">";
}

switch ($_GET["p"]) { //show what for person get[w]?
	case "w": // show all belonging to warehouse/person
		switch ($_GET["o"]) { //show what for person get[w]?
			case "o": // show only OUT
				$limiters=" and tool.owner<>tool.person ";
				$links = "
					<a href=\"tools.php?w=$_GET[w]&amp;p=w\">Alle</a>
					<a href=\"tools.php?w=$_GET[w]&amp;p=w&amp;o=i\">Inne</a>
					<a href=\"tools.php?w=$_GET[w]&amp;p=w&amp;o=o\" class=\"ul\">Ute</a>";
				break;
			case "i": // show only IN
				$limiters=" and tool.owner=tool.person ";
				$links = "
					<a href=\"tools.php?w=$_GET[w]&amp;p=w\">Alle</a>
					<a href=\"tools.php?w=$_GET[w]&amp;p=w&amp;o=i\" class=\"ul\">Inne</a>
					<a href=\"tools.php?w=$_GET[w]&amp;p=w&amp;o=o\">Ute</a>";
				break;
			default: // show all
				$limiters="";
				$links = "
					<a href=\"tools.php?w=$_GET[w]&amp;p=w\" class=\"ul\">Alle</a>
					<a href=\"tools.php?w=$_GET[w]&amp;p=w&amp;o=i\">Inne</a>
					<a href=\"tools.php?w=$_GET[w]&amp;p=w&amp;o=o\">Ute</a>";
		}
		$qs = "select tool.ix as tix,tool.name as toolname, person.ix as hix,owner.persname as ownername, person.persname as holdername
		       from person,tool,person as owner
		       where tool.person=person.ix and owner.ix=tool.owner and owner.ix=$_SESSION[fvwh]
		       $limiters
		       order by toolname asc, holdername asc";

		// override qs on displayALL tools in company
		if ($_GET["all"] == "comp") {
		$qs = "select tool.ix as tix, concat(tool.name, ' (', ifnull(tool.tag, '-') , ')') as toolname, person.ix as hix,owner.persname as ownername, person.persname as holdername
		       from person,tool,person as owner
		       where tool.person=person.ix and owner.ix=tool.owner
		       order by toolname asc, holdername asc";
		}

		$whqry=$db->query($qs);
		$tabl = "<table >
			<th>
				Verktøy
			</td>
			<th>
				Hvem har det
			</th>";
		while ($we = $whqry->fetch_object()) {
			$warehouse = $we->ownername;
			$tabl .= "<tr>
					<td>
						<a href=\"index.php?t=$we->tix\" title=\"Vis verktøy\">$we->toolname</a>
					</td>
					<td>
						<a href=\"tools.php?w=$we->hix&amp;p=h&amp;o=$_GET[o]\" title=\"Vis verktøy registrert på\">$we->holdername</a>
					</td>
				 </tr>";
		}
		$tabl .= "</table>";

		if ($_GET["all"] == "comp") {
			$out .= "<b>Alle verktøy
		         </b>";
		} else {
			$out .= "<b>Verktøy <a href=\"tools.php?p=w&amp;w=$_GET[w]&amp;o=$_GET[o]\" class=\"ul\">tilhørende</a>/<a href=\"tools.php?p=h&amp;w=$_GET[w]&amp;o=$_GET[o]\">ihende</a>
			<a href=\"persons.php\">$whouse->persname</a>: $links
		         </b>";
		}
// FIXME show on admins only
		$out .= "";

		$out .= "<p>$tabl";
		break;
	case "h": // show all held by warehouse/person
		switch ($_GET["o"]) { //show what for person get[w]?
			case "o": // show only OTHERS'
				$limiters=" and tool.owner<>tool.person ";
				$links = "
					<a href=\"tools.php?w=$_GET[w]&amp;p=h\">Alle</a>
					<a href=\"tools.php?w=$_GET[w]&amp;p=h&amp;o=i\">Egne</a>
					<a href=\"tools.php?w=$_GET[w]&amp;p=h&amp;o=o\" class=\"ul\">Andres</a>";
				break;
			case "i": // show only OWN
				$limiters=" and tool.owner=tool.person ";
				$links = "
					<a href=\"tools.php?w=$_GET[w]&amp;p=h\">Alle</a>
					<a href=\"tools.php?w=$_GET[w]&amp;p=h&amp;o=i\" class=\"ul\">Egne</a>
					<a href=\"tools.php?w=$_GET[w]&amp;p=h&amp;o=o\">Andres</a>";
				break;
			default: // show all
				$limiters="";
				$links = "
					<a href=\"tools.php?w=$_GET[w]&amp;p=h\" class=\"ul\">Alle</a>
					<a href=\"tools.php?w=$_GET[w]&amp;p=h&amp;o=i\">Egne</a>
					<a href=\"tools.php?w=$_GET[w]&amp;p=h&amp;o=o\">Andres</a>";
		}

		$qs = "select tool.ix as tix,owner.ix as oix, tool.name as toolname,owner.persname as ownername, person.persname as holdername
		       from person,tool,person as owner
		       where tool.person=person.ix and owner.ix=tool.owner and person.ix=$_SESSION[fvwh]
		       $limiters
		       order by toolname asc, ownername asc";
//		$out .= $qs;

		$whqry=$db->query($qs);
		$tabl = "<table >
			<th>
				Verktøy
			</td>
			<th>
				Hvem eier det
			</th>";
		while ($we = $whqry->fetch_object()) {
			$warehouse = $we->holdername;
			$tabl .= "<tr>
					<td>
						<a href=\"index.php?t=$we->tix\" title=\"Vis verktøy\">$we->toolname</a>
					</td>
					<td>
						<a href=\"tools.php?w=$we->oix&amp;p=w&amp;o=$_GET[o]\" title=\"Vis eide verktøy\">$we->ownername</a>
					</td>
				 </tr>";
		}
		$tabl .= "</table>";

//		$out .= "<b>Verktøy $warehouse har: $links";
		$out .= "<b>Verktøy <a href=\"tools.php?p=w&amp;w=$_GET[w]&amp;o=$_GET[o]\">tilhørende</a>/<a href=\"tools.php?p=h&amp;w=$_GET[w]&amp;o=$_GET[o]\" class=\"ul\" >ihende</a>
			<a href=\"persons.php\">$whouse->persname</a>: $links
				         </b>";
		if ($fvusr->adminlevel > 0 ) {
		}
		$out .= "</b><p>$tabl";
		break;
	default:// show is empty, show all in company/division
		$out .= " show all";
}
$out .= "</div>";


// add warehouse QR code
// DEBUG

// FIXME generate QR SHOULD BE DONE ONLY ONCE EVERY PERSON EDIT
// refreshing on each page load for now
qrcode("$siteurl$sitebase?w=$_GET[w]","uploads/qrWareLink_$_GET[w].png",$whouse->persname);


// echo "ADMINLEVEL $fvusr->adminlevel ";
if ($_GET["all"] <> "comp") {
if ($fvusr->adminlevel > 0 ) {
	$out .= "
	<div class=\"toolbox\" style=\"clear:left;\">
	<p>Lagerlink:<a href=\"qr.php?f=qrWareLink_$_GET[w].png\"><img src=\"uploads/qrWareLink_$_GET[w].png\"></a>
         <p><a href=\"admin.php\">Adminside</a>
	</div>";
} // if admin
} // if alltools

} else {  // if query didn't go well
	$out .= "Invalid page parameters.";
}  // if query went well

//} // if we should show warehouse code

// page starts here
echo "<html>
<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
$metarefresh
<title>Fellesverktøy</title>
<style>
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
