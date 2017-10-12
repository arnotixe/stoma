<?php

// STOMA
require("config.php");

header('Content-Type: text/html; charset=utf-8');

// Currently, iPhone opens URL without asking.
// Android users can use https://play.google.com/store/apps/details?id=me.scan.android.client and configure to open URL without asking :)



/*
Avoiding Confusion With Alphanumeric Characters

https://www.ncbi.nlm.nih.gov/pmc/articles/PMC3541865/
https://www.ncbi.nlm.nih.gov/pmc/articles/PMC3541865/table/t1-ptj3712663/

Therefore if we need to read tags:

7, l, 1, i, I -> I
d, D, o, O, 0 -> O
c, C, e, E, f, F -> E
t, T, y, Y, 2, z, Z -> Z
g, G, q, Q -> Q
b, B, 8 -> B
m, M, n, N -> N
3, s, S, 5 -> S

*/

//Session-based login and remember-me by cookie is called in config.php and functions.php; just check if logged in or not
/*

// login in cookie
session_start(); // start/resume session
// if no SESSION userid is set, try to login with cookie if that matches database:
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
}
*/

if ($_GET["t"] == "") {
   $_SESSION['fvtool'] = 1; // set default (Tool 1 should be describing this tool management system)
}


// these variables are sessioned to pass them on to login and other pages
if (!empty($_GET["t"])) {
   $_SESSION['fvtool'] = $_GET["t"]; // tool code scanned? Update only if specified

// don't recycle  $_SESSION["fvpush"] (scan-to-push-to…?)
   unset($_SESSION["fvpush"]);
   unset($_SESSION['fvwh']);
}

if (empty($_SESSION['fvtool']) && !empty($_SESSION['fvwh'])) { // no tool, set warehouse GET in case we return from login with no tool here... MESSY CODE sorry should have a returnlink "user came to see this"
	$_GET["w"] = $_SESSION['fvwh'];
}


if (!empty($_GET["w"])) {
   $_SESSION["fvwh"] = $_GET["w"];   // warehouse code scanned? reg it into SESSION
//echo "GET W is " . $_GET["w"] . " and session fvwh " . $_SESSION["fvwh"];
//var_dump($_SESSION);
//return;
//echo "user was just returining tool $_SESSION[fvtool] _$_SESSION[fvpush]_ $_GET[w]. Let's hope that's a warehouse in the correct company.";
// should reset  $_SESSION["fvpush"] = "towarehouse"; afterwards.

   if (!empty($_SESSION["fvpush"])) { // tool push is flagged.
	     $_GET["a"] = "pt"; // trick this page into using warehouse as push-to $_GET[w]
	     $_GET["pt"] = $_GET["w"]; // trick this page into using warehouse as push-to $_GET[w]

        } else { // tool push is not flagged
	        // GET w, no push? show warehouse (tools->all on warehouse) page, since no tool push is flagged.
		unset($_SESSION['fvtool']); // remove fvtool
	   	header("Location:tools.php?w=$_SESSION[fvwh]");
	   	return;
   }
}


// DEBUG
// var_dump($_SESSION);


if (empty($_SESSION['fvuser'])) {
//echo "You must login.";
	header('Location:login.php');
return;
}


// get logged in person's info, like if he's an $fvusr->adminlevel > 0
if ($qr = $db->query("select * from person where ix=$_SESSION[fvuser]")) {
        $fvusr = $qr->fetch_object();
}




// hobby login: index.php?us=1 or whatever user
/*
if (!empty($_GET["us"])) {
  // in functions.php, sets session fvuser and write token to database
  generatelogintoken($db,$_GET["us"]);
}
*/


// If we get this far then $_SESSION['fvuser'] has logged in correctly, and exists.



// IF LOGGED IN then: http://stackoverflow.com/questions/3128985/php-login-system-remember-me-persistent-cookie/30135526#30135526
// GENERATE LOGIN TOKEN
/*
if( $usr <> 0) {
	  generatelogintoken($usr);

}
*/

// when logged in,

// qr code parameter: Assign tool to yerself

$spectool = mysql_escape_string($_SESSION["fvtool"]);

// html scripts
$scripts = "";
// body content
$out="";

/*
// debug
$out="
<a href=logout.php>Logout</a> -
<a href=index.php?t=1>Existing tool</a> -
<a href=index.php?t=34>Nonexistant tool</a>
";
*/

//echo "<html><head><meta http-equiv=\"Content-type\" content=\"text/html; charset=utf-8\" /></head><body>";

// Who is logged in?
$qs="SELECT persname,person.ix,division,company.ix as cix,person.mail,person.phone,person.iswarehouse
            from person,division,company
            where person.ix=$_SESSION[fvuser] and company.ix=division.company
            and division.ix=person.division limit 1";

//echo $qs;
if ($pq = $db->query($qs)) {
    if ($reassign = $pq->fetch_object()){
    } else {
       logg(NULL,NULL,$reassign->ix,$spectool,"User does not exist");
       // LOG THIS
    }
}

// Logged in user's attachment
$company = $reassign->cix;
$division = $reassign->division;

// Who has this tool?
if ($pq = $db->query("SELECT persname,person.ix,mail,person.phone,person.division
		      from person,tool where
                      person.ix=tool.person and tool.ix=$spectool
		      limit 1")) {
    if ($person = $pq->fetch_object()){
    } else {
       logg(NULL,NULL,NULL,$spectool,"Tool has no holder");
       // LOG THIS
    }
}

$phone = $person->phone;
$mail = $person->mail;

// Who owns this tool?
if ($pq = $db->query("SELECT persname,person.ix,iswarehouse from person,tool where person.ix=tool.owner and tool.ix=$spectool limit 1")) {
    if ($owner = $pq->fetch_object()){
    } else {
       logg(NULL,NULL,NULL,$spectool,"Tool has no owner");
       // LOG THIS
    }
}

// check if tool is valid
if ($result = $db->query("SELECT * from tool where ix=$spectool limit 1")) {

// DEBUGGING
//    printf("Select returned %d rows.\n", $result->num_rows);
   if ($row = $result->fetch_object()){ // bør kun returnere 1...
// DEBUGGING
$adminstuff="";
if ($fvusr->adminlevel > 0) {
$adminstuff="<a href=\"edittool.php?t=$_SESSION[fvtool]\"><img src=\"pix/edit-icon.png\" title=\"Redigér\" style=\"width:1em;\" alt=\"Redigér\"></img></a>";
}

	$out .= "
		<div class=\"contain\">
		<div class=\"toolbox\">
		 Verktøyet: <b>$row->name</b>$adminstuff (#$spectool) hører hjemme hos $owner->persname; nå er det hos:
		<div class=\"holder\">
		 $person->persname<br>
		 <a href=\"tel:$phone\"><img class=\"tl\" src=\"pix/phone.png\" alt=\"Ring\"></a>
		 <a href=\"sms:$phone?body=Hei, kan jeg låne $row->name? Mvh $reassign->persname\"><img  class=\"tl\" src=\"pix/sms.png\" alt=\"SMS\"></a>
		 <a href=\"mailto:$mail?subject=$row->name&amp;body=Hei, kan jeg låne $row->name?%0D%0A%0D%0AMvh $reassign->persname\"><img class=\"tl\"  src=\"pix/mail.png\" alt=\"Mail\"></a><p>
		</div>
";

	$pickbuttons="
<a href=\"index.php?t=$spectool&amp;a=r\"><img  class=\"tl\" src=pix/give.png alt=\"Return to owner\" title=\"Returnér\"></a>
<a href=\"index.php?t=$spectool&amp;a=t\"><img  class=\"tl\" src=pix/take.png alt=\"I'll take it!\" title=\"Ta\"></a>
<a href=\"index.php?t=$spectool&amp;a=p\"><img  class=\"tl\" src=pix/trans.png alt=\"Assign to...\" title=\"Gi til...\"></a><br>";

	$out .= "<div class=\"center\">";

	 if ($_GET["a"] == "" ) { // no action specified
	     $out .= "$pickbuttons";
	 }

         // IF updating: take
	 if ($_GET["a"] == "t" ) { // take tool
	     $out .= "$pickbuttons";
	     $metarefresh = "<meta http-equiv=\"refresh\" content=\"5; url=index.php?t=$spectool\">";
	     if ($person->ix == $reassign->ix ) { // already have it?
	         $out .= "<div class=\"actionmsg\">
	         Du har allerede dette verktøyet.
	         </div>";
  	     } else {
	       if ($upq = $db->query("update tool set person=$reassign->ix where ix=$spectool")) {
	         $out .= "<div class=\"actionmsg\">";
	         $out .= "OK verktøyet ble registrert på deg.";
		 $sndout = "<audio src=\"pix/success.wav\" autoplay=\"autoplay\" ></audio>";
	         $out .= "</div>";
	         logg(NULL,NULL,NULL,$spectool,"$reassign->persname har det");
   	       } else {
	         logg(NULL,NULL,NULL,$spectool,"Error reassigning tool. SQL: update tool set person=$reassign->ix where ix=$spectool");
  	       }
             }
  	  }

         // IF updating: return
	 if ($_GET["a"] == "r" ) { // return tool
	     // check if returning to a warehouse. if so, set fvpush
	     if ($owner->iswarehouse == 1 ){ // trying to return to a warehouse owner
		     $_SESSION["fvpush"] = "towarehouse";
		     header("Location:index.php?t=$spectool&a=pt&pt=$owner->ix");
		     return;
	     }



	     $out .= "$pickbuttons";
	     $metarefresh = "<meta http-equiv=\"refresh\" content=\"5; url=index.php?t=$spectool\">";
	     if ($owner->ix == $person->ix ) { // already have it?
	         $out .= "<div class=\"actionmsg\">";
	         $out .= "Verktøyet er allerede lagt på plass.<br>Men det er tanken som teller!";
	         $out .= "</div>";
  	     } else {
  	       if ($upq = $db->query("update tool set person=$owner->ix where ix=$spectool")) {
	         $out .= "<div class=\"actionmsg\">";
	         $out .= "OK verktøyet ble lagt på plass. BRA!";
		 $sndout = "<audio src=\"pix/success.wav\" autoplay=\"autoplay\" ></audio>";
	         $out .= "</div>";
   	         logg(NULL,NULL,NULL,$spectool,"$reassign->persname la verktøyet på plass");
   	       } else {
	         logg(NULL,NULL,NULL,$spectool,"Error returning tool. SQL: update tool set person=$owner->ix where ix=$spectool");
  	       }
             }
  	  }


         // IF updating: push to other person
	 if ($_GET["a"] == "pt" ) { // push to
	     $_SESSION["fvpush"] = "towarehouse";
	     $out .= "$pickbuttons";
		$pt = mysql_escape_string($_GET["pt"]);
		// Who has this tool?
		$qs= "SELECT * from person where person.ix=$pt  limit 1";
//		echo $qs;
	if ($pq = $db->query($qs)) { // success?
	  $ptn=$pq->fetch_object(); // push to-name
//echo "get pt $_GET[pt] ptnix $ptn->ix ";

	     if ($person->ix == $pt ) { // already have it?
	         $out .= "<div class=\"actionmsg\">";
	         $out .= "$ptn->persname har allerede dette verktøyet.";
	         $out .= "</div>";
	         $metarefresh = "<meta http-equiv=\"refresh\" content=\"5; url=index.php?t=$spectool\">";
	         unset($_SESSION["fvpush"]);
  	     } else { // push to other person/warehouse, if possible

	      // check if tool owner is in same division as intended push-to user. $person->divison contains current holder
	      $ptq = $db->query("select * from person where ix=$pt");
	      $pushto =$ptq->fetch_object();
//echo "PERS $person->division vs PUSH $pushto->division ";

	      if  ( $person->division == $pushto->division ) { // push-to belongs to same company?
//echo "PERS = PUSH ";
  	     	// check if tool and new person actually belong to the same division...
//echo "iswerehouse $pushton->iswarehouse || get pt $_GET[pt] ptnix $ptn->ix vs get_w $_GET[w] ";
		// ptn is current holder.
		if ($ptn->iswarehouse == 0 ||  $ptn->ix == $_GET["w"] ) { // this is a person, OR warehouse is scanned and is equal to specified pt
		       if ($upq = $db->query("update tool set person=$pt where ix=$spectool")) {
		         $out .= "<div class=\"actionmsg\">";
		         $out .= "OK verktøyet ble registrert på $ptn->persname.";
			 $sndout = "<audio src=\"pix/success.wav\" autoplay=\"autoplay\" ></audio>";
		         $out .= "</div>";
	                 $metarefresh = "<meta http-equiv=\"refresh\" content=\"5; url=index.php?t=$spectool\">";
		         logg(NULL,NULL,NULL,$spectool,"$ptn->persname har det");
			unset($_SESSION["fvpush"]);
	   	       } else {
		         logg(NULL,NULL,NULL,$spectool,"Error reassigning tool. SQL: update tool set person=$pt where ix=$spectool");
	  	       }
			// no recycling
			unset($_SESSION["fvpush"]);
		} else { // this is a warehouse
		         $out .= "<div class=\"actionmsg\">";
			 $out .= "NESTEN I MÅL!<p>Du må nå scanne koden på <b>$ptn->persname</b> for å få det registrert dit :)";
		         $out .= "</div>";
		} // if warehouse
	      } else {// if tool owner is in same division as intended push-to user
		         $out .= "<div class=\"actionmsg redshadow\">";
			 $out .= "ØH!<p>Det lageret/personen tilhører ikke samme firma/avdeling som det verktøyet er registrert på! Den går ikke.";
		         $out .= "</div>";
	      }
             }

		    } else {
		       logg(NULL,NULL,$pt,NULL,"Push to-user does not exist");
		       // LOG THIS
		    }
//		    $pq->close();
		} // action pt

	 // PUSH step 2
         // IF updating: push to... (display selection box)
	 if ($_GET["a"] == "p" ) { // push tool to...
	    $scripts .= "";
	    $out .= "<form action=\"\" method=\"get\">
	    	     <input type=hidden name=t value=$spectool>
	    	     <input type=hidden name=a value=pt>
	    	     Overlat verktøyet til: <select name=pt onchange='this.form.submit()'>";
	    $qs= "select persname, person.ix from person,division,company
	    	  where active=1
	    	  and division=$person->division
	    	  and company.ix=division.company and division.ix=person.division
	    	  order by persname";
	    if ($eq = $db->query($qs)) {
                 $out .= "<option value=\"0\">velg...</option>";
	       while ($emp = $eq->fetch_object()){
                 $out .= "<option value=\"$emp->ix\">$emp->persname</option>";
               }
               $eq->close();
            }
	    $out .= "</select></form>";
  	  }

	   $out .= "
</div>
</div>
<div class=\"toolbox\" style=\"clear:left;\">
<i><a href=\"history.php?t=$spectool\">Historikk:</a></i><br>";
  	  // show recent tool history
	    $qs= "select * from log where tool=$spectool order by date desc limit 10";
	    if ($eq = $db->query($qs)) {
	       while ($emp = $eq->fetch_object()){
                 $out .= "$emp->date: $emp->text<br>\n";
               }
               $eq->close();
            }


// Check next calibration, lost, or not, serial no.
//if () {
// }

// force thumbnail refresh
$thbref .= time();

// need tool owner for tools page
$out .= "
</div>
<div>
<div class=\"toolbox center\" style=\"clear:left;\">
<a href=\"c.php?t=$spectool\"><img  class=\"tl\" src=pix/cal.png alt=\"Bookingkalender\" title=\"Bookingkalender\"></a>
<a href=\"tools.php?p=w&amp;w=$owner->ix\"><img  class=\"tl\" src=pix/tools.png alt=\"Verktøyliste\" title=\"Verktøy her\"></a>
<a href=\"persons.php\"><img  class=\"tl\" src=pix/persons.png alt=\"Personliste\" title=\"Personliste\"></a><br>
</div>
<div class=\"toolbox\" style=\"clear:left;\">
<a href=\"uploads/$_SESSION[fvtool].jpg\" target=\"_blank\"><img src=\"uploads/$_SESSION[fvtool]_thumb.jpg?thbref\" alt=\"(har ikke bilde)\"></a><br>
Serienr: $row->serialno<br>
Neste kalibrering: $row->nextcalibration<br>
</div>

";
   } else { // fetch tool went wrong
     $out .= "<div class=\"actionmsg red\">Hmmm det verktøyet (#$spectool) finnes ikke i databasen (ennå).";
     logg(NULL,NULL,NULL,$spectool,"No such tool.");
   } // fetch tool

   /* free result set */
   $result->close();
}

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
}
.contain {
	display: inline-block;
}
</style>
<body>";
echo $out;

// $sndout could be put in up there, but will probably be annoying :)
// turns out it's actually blocked by browsers...

// check if this tool's division is the same as logged-in user's
//echo "Assign Tool no $_GET[t] to logged-in user $usr";

// no qr code parameter: Display tools list

// stuff that only admins should see
$adminstuff="";
if ($fvusr->adminlevel > 0) {
$adminstuff="<p><a href=\"edittool.php?t=$_SESSION[fvtool]\">Rediger verktøy</a>
 <p><a href=\"admin.php\">Adminside</a>";
}

echo "
<div class=\"toolbox\" style=\"clear:left;\">
<p>Verktøylink: <a href=\"qr.php?f=qrToolLink_$_SESSION[fvtool].png\"><img src=\"uploads/qrToolLink_$_SESSION[fvtool].png\"></a>
<p>Bookinglink: <a href=\"qr.php?f=qrToolBook_$_SESSION[fvtool].png\"> <img src=\"uploads/qrToolBook_$_SESSION[fvtool].png\"> </a>
$adminstuff
 <p><a href=\"https://play.google.com/store/apps/details?id=me.scan.android.client&hl=en\">Her finner du en bra QR-kodescanner</a>.<br>Etter installasjon kan du åpne innstillinger og ta bort \"Ask before opening\" så går det enda raskere.
</div>

";

?>
