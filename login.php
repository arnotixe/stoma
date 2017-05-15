<?php

// STOMA login page

require("config.php");

header('Content-Type: text/html; charset=utf-8');


// hobby login: index.php?us=1 or whatever user
/*
if (!empty($_GET["us"])) {
  // in functions.php, sets session fvuser and write token to database
  generatelogintoken($db,$_GET["us"]);
}
*/

// some company info should be available from the scanned tool/code


$division = 0;
$scripts = "";
// body content
$out="";



$out .= "tool scanned was $_SESSION[fvtool] and warehouse was $_SESSION[fvwh]<br>
<a href=/fv/?t=2343>nonex tool</a>
<a href=/fv/?t=1>ex tool</a><p>
";

// someone just stumbled across this page, or logged out, or something went wrong
if ( empty($_SESSION[fvtool]) && empty($_SESSION[fvwh]) ) {
echo "For å bruke disse sidene må du først scanne en verktøykode med en QR-scanner.";
return;
}


// check if tool, warehouse or whatever input actually exist (and is active)

// CHECK if tool exists
$tl = mysql_escape_string($_SESSION[fvtool]);
$out .= "TOOLID = $_SESSION[fvtool] ";
$qs="SELECT division,company.name as cname, division.contactperson as pname, division.phone as pphone, division.mail as pmail
            from tool,person,company,division
	    where tool.owner=person.ix and person.division=division.ix and division.company=company.ix and
            tool.ix = $_SESSION[fvtool]";
// echo $qs;
if ($pq = $db->query($qs)) {
    if ($tfound = $pq->fetch_object()){
	// Always show owner info, in case it is lost...
	$out .= "<div class=\"toolbox\">
		 Dette verktøyet tilhører <b>$tfound->cname</b>, kontaktperson er
		 <br><b>$tfound->pname</b><p>
                 <a href=\"tel:$tfound->pphone\"><img class=\"tl\" src=\"pix/phone.png\" alt=\"Ring\"></a>
                 <a href=\"sms:$tfound->pphone?body=Hei, jeg fant et av dine verktøy:(#$_SESSION[fvtool]) Mvh\"><img  class=\"tl\" src=\"pix/sms.png\" alt=\"SMS\"></a>
                 <a href=\"mailto:$tfound->pmail?subject=Fant et av dine verktøy&amp;body=Hei, jeg fant et av dine verktøy (#$_SESSION[fvtool]):%0D%0A%0D%0AMvh \">
		 <img class=\"tl\"  src=\"pix/mail.png\" alt=\"Mail\"></a>
		</div>";

	$division = $tfound->division; // users trying to log in should belong to the same division as the tool
	// echo "tool found was $tfound->division";
    } else {
	$out .= "Beklager! Verktøy #$_SESSION[fvtool] finnes ikke i denne databasen (ennå?).";
	unset($_SESSION[fvtool]);
        logg(NULL,NULL,NULL,$spectool,"Tool #$_SESSION[fvtool] does not exist");
	echo $out;
	return;
    }
}

/*
TODO check warehouse etc here. Should match tool, etc
*/

// next box
$out .= "<div class=\"toolbox\">";

// If we got here, tool and/or warehouse are ok, and we've got a division

if ( !empty($_POST["password"])) {
	$usr = mysql_escape_string($_POST["fvuser"]);

//	$out .= "Supplied password for user $_POST[fvuser] was $_POST[password]. Could be good. Could be bad.";
  $qs="SELECT person.pin
            from person
	    where person.ix=$usr and person.iswarehouse=0"; // someone could be tampering with user id
//   echo $qs;
  if ($pq = $db->query($qs)) {
    if ($tfound = $pq->fetch_object()){
      if ( $tfound->pin == $_POST["password"] ) {
	// good password
        generatelogintoken($db,$usr);
	$out .= "good password, userid is now set in cookie and session.";
	header("Location:index.php");
	return;
      } else {
	$out .= "<p><div class=\"actionmsg\">Helt eller delvis feil!</div><p>";
	$_GET["u"] = $usr; // recycle user below
	$_GET["n"] = $_POST["n"]; // recycle user below
	// bad password
      }
    }
  }
}

// bad password, or no user specified
//$out .= "GET u is $_GET[u]";

if ( !empty($_GET["u"])) {
	// Draw password box
	$out .= "<form action=\"login.php\" method=\"post\">";
	$out .= "Passord for $_GET[n]: <input name=\"password\" autofocus placeholder=\"\"><p>";
	$out .= "<input type=\"hidden\" name=\"fvuser\" value=\"$_GET[u]\">";
	$out .= "<input type=\"hidden\" name=\"n\" value=\"$_GET[n]\">";
	$out .= "<input type=\"submit\" value=\"Logg inn\">";
	$out .= "</form>";
//	echo "Logg inn bruker $_GET[u]";
} else {
  // draw list of persons attached to that division/company
  $out .= "... eller logg inn for å behandle:<p>";
  $qs="SELECT person.ix,person.persname
            from person
	    where person.division=$division and person.iswarehouse=0 and person.active=1
	    order by persname";
  // echo $qs;
  if ($pq = $db->query($qs)) {
    while ($tfound = $pq->fetch_object()){
	$out .= "<a href=\"?u=$tfound->ix&amp;n=$tfound->persname\">$tfound->persname</a><p>";
    }
  }
} //get[u] set?

// end of box 2
$out .= "</div>";


echo "<html>
<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
$metarefresh
<title>Fellesverktøy: Logg inn</title>
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
