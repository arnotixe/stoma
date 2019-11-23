<?php

//header( "Refresh:2; url=" . $_SERVER['HTTP_REFERER']);

//echo "Denne adminsiden finnes ikke ennå. Arno jobber \"på spreng\" med å få fiksa det.";


// STOMA
require("config.php");

header('Content-Type: text/html; charset=utf-8');

// possible parameters are
// w=person number, mandatory

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


// check for special person value "new" (create new tool, go to blank page)
if ($_GET["w"] == "new") {

/* mysql> desc person;
+-------------+-------------+------+-----+---------+----------------+
| Field       | Type        | Null | Key | Default | Extra          |
+-------------+-------------+------+-----+---------+----------------+
| ix          | int(11)     | NO   | PRI | NULL    | auto_increment |
| division    | int(11)     | NO   | MUL | NULL    |                |
| persno      | varchar(20) | NO   |     | NULL    |                |
| persname    | varchar(40) | NO   |     | NULL    |                |
| active      | tinyint(1)  | NO   |     | 1       |                |
| phone       | varchar(60) | YES  |     | NULL    |                |
| mail        | varchar(60) | YES  |     | NULL    |                |
| iswarehouse | tinyint(1)  | NO   |     | 0       |                |
| pin         | varchar(60) | YES  |     | NULL    |                |
| adminlevel  | int(11)     | NO   |     | 0       |                |
+-------------+-------------+------+-----+---------+----------------+

*/
        $qs= "insert into person (active,iswarehouse,persname,phone,mail,pin,persno,adminlevel,division) values (1,0,\"Ny Person\",\"+47\",\"em@il.adresse\",\"0000\",\"0000\",0,1)"; // hardwired division 0
        $qr = $db->query($qs); // hardwired division 0
        $_SESSION['fvtool'] = $db->insert_id; // last inserted tool
        header("Location:editpers.php?w=$db->insert_id");

//        echo "query $qs, last insert id was $db->insert_id"; // DEBUG
        return; // redirect to newly created tool
}

// html scripts
$scripts = "";
// body content
$out="";




// Process form
if ($_POST['uploadaction'] == "uploading") {

if (isset($_POST["active"])) {
	$factive = 1;
} else {
	$factive = 0;
}

if (isset($_POST["iswarehouse"])) {
	$fiswarehouse = 1;
} else {
	$fiswarehouse = 0;
}

if (isset($_POST["adminlevel"])) {
	$fadminlevel = 1;
} else {
	$fadminlevel = 0;
}

$tagval = $_POST["tagpart1"] . $_POST["ftag"];

// prep bools, etc
	$qs =             "update person set
			  active=\"$factive\",
			  iswarehouse=\"$fiswarehouse\",
			  adminlevel=\"$fadminlevel\",
			  persname=\"$_POST[persname]\",
			  persno=\"$_POST[persno]\",
			  phone=\"$_POST[phone]\",
			  mail=\"$_POST[mail]\",
			  pin=\"$_POST[pin]\",
			  division=\"$_POST[division]\"
			  where ix=$_POST[w]";

//echo $qs . "<br>";
	$qr = $db->query($qs);

 // redirect to saved
        header("Location:editpers.php?w=$_POST[w]");
        return; // redirect to newly created tool

} // end of form process




$qr = $db->query("select * from person where ix=$_GET[w]");
$prs = $qr->fetch_object();

//<a href=\"editpers.php?t=$prevtool\"><img src=\"pix/arrow_left.png\" style=\"width: 1em;\" title=\"Forrige\" alt=\"&lt;\"></a>
//<a href=\"editpers.php?t=$nexttool\"><img src=\"pix/arrow_right.png\" style=\"width: 1em;\" title=\"Neste\" alt=\"&gt;\"></a>
// https://fv.sonnico.no/tools.php?p=w&w=1
$out .="
Redigere <a href=\"tools.php?p=w&w=$_GET[w]\" title=\"Gå til\">person $_GET[w]</a>
<a href=\"admin.php\"><img src=\"pix/list-icon.png\" style=\"width: 1em;\" title=\"Adminside\" alt=\"---\"></a>

<form action=\"editpers.php\" method=\"post\" enctype=\"multipart/form-data\">
Navn <input name=\"persname\" value=\"$prs->persname\"><br>
Epost <input name=\"mail\" value=\"$prs->mail\"><br>
Telefon <input name=\"phone\" value=\"$prs->phone\"><br>
Ansattnr <input name=\"persno\" value=\"$prs->persno\"><br>
Passord <input name=\"pin\" value=\"$prs->pin\"><br>
<span style=\"display:none;\">Divisjon <input name=\"division\" value=\"$prs->division\"><br></span>";


$chktxt = "";
if ($prs->active == 1) {
	$chktxt = "checked";
}
// this is hidden for now, on ØK request
$out .= "<span>Aktiv <input type=\"checkbox\" name=\"active\" $chktxt><br></span>";



$chktxt = "";
if ($prs->iswarehouse == 1) {
	$chktxt = "checked";
}

$out .= "Er verktøylager <input type=\"checkbox\" name=\"iswarehouse\" $chktxt><br>";


$chktxt = "";
if ($prs->adminlevel == 1) {
        $chktxt = "checked";
}

$out .= "
Admin <input type=\"checkbox\" name=\"adminlevel\" $chktxt><br>

    <input type=\"hidden\" name=\"uploadaction\" value=\"uploading\">
    <input type=\"hidden\" name=\"w\" value=\"$_GET[w]\">
    <input type=\"submit\" value=\"Lagre\" name=\"submit\">
</form>
<hr>
<a href=\"editpers.php?t=new\">Nytt verktøy</a> - <a href=\"admin.php\">Adminside</a>
<p>";


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

?>
