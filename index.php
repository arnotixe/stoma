<?php

// STOMA
require("config.php");

// login in cookie
session_start(); // start/resume session
header('Content-Type: text/html; charset=utf-8');

//setcookie("teigseth.no/fv", "user", time()+60*60*24*30);


// Redirect to login if not

$usr=1; // right now assume user id 1




// when logged in,

// qr code parameter: Assign tool to yerself

$spectool = mysql_escape_string($_GET[t]);

// html scripts
$scripts = "";
// body content
$out="";

//echo "<html><head><meta http-equiv=\"Content-type\" content=\"text/html; charset=utf-8\" /></head><body>";

// Who is logged in?
$qs="SELECT persname,person.ix,division,company.ix as cix,mail,person.phone 
                        from person,division,company 
                        where person.ix=$usr and company.ix=division.company and division.ix=person.ix limit 1";

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
$phone = $reassign->phone;
$mail = $reassign->mail;

// Who has this tool?
if ($pq = $db->query("SELECT persname,person.ix from person,tool where person.ix=tool.person and tool.ix=$spectool limit 1")) {
    if ($person = $pq->fetch_object()){
    } else {
       logg(NULL,NULL,NULL,$spectool,"Tool has no holder");
       // LOG THIS
    }
}

// Who owns this tool?
if ($pq = $db->query("SELECT persname,person.ix from person,tool where person.ix=tool.owner and tool.ix=$spectool limit 1")) {
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
   while ($row = $result->fetch_object()){ // bør kun returnere 1...
// DEBUGGING
	$out .= "
<div class=\"contain\">
<div class=\"toolbox\">
		 Verktøyet: <b>$row->name</b> (#$spectool)<br>hører hjemme hos $owner->persname,
		 <br> og akkurat nå har $person->persname det.<p>
		 <a href=\"tel:$phone\">Ring</a> -
		 <a href=\"sms:$phone?body=Hei, kan jeg låne $row->name? Mvh $reassign->persname\">SMS</a> -
		 <a href=\"mailto:$mail?subject=$row->name&amp;body=Hei, kan jeg låne $row->name?%0D%0A%0D%0AMvh $reassign->persname\">Mail</a><p>";

	$out .= "
<a href=index.php?t=$spectool&a=r><img src=pix/give.png alt=Return to owner title=\"Returnér\"></a>
<a href=index.php?t=$spectool&a=t><img src=pix/take.png alt=I'll take it! title=\"Ta\"></a>
<a href=index.php?t=$spectool&a=p><img src=pix/push.png alt=Assign to... title=\"Gi til...\"></a>
</div>
<div class=\"toolbox\">
";

         // IF updating: take
	 if ($_GET["a"] == "t" ) { // take tool
	     if ($person->ix == $reassign->ix ) { // already have it?
	         $out .= "Du har allerede dette verktøyet.<p>";
  	     } else {
	       if ($upq = $db->query("update tool set person=$reassign->ix where ix=$spectool")) {
	         $out .= "OK verktøyet ble registrert på deg.<p>";
	         logg(NULL,NULL,NULL,$spectool,"$reassign->persname har det");
   	       } else {
	         logg(NULL,NULL,NULL,$spectool,"Error reassigning tool. SQL: update tool set person=$reassign->ix where ix=$spectool");
  	       }
             }
  	  }

         // IF updating: return
	 if ($_GET["a"] == "r" ) { // rutern tool
	     if ($owner->ix == $person->ix ) { // already have it?
	         $out .= "Verktøyet er allerede lagt på plass.<p>";
  	     } else {
  	       if ($upq = $db->query("update tool set person=$owner->ix where ix=$spectool")) {
	         $out .= "OK verktøyet ble lagt på plass. BRA!<p>";
   	         logg(NULL,NULL,NULL,$spectool,"$reassign->persname la verktøyet på plass");
   	       } else {
	         logg(NULL,NULL,NULL,$spectool,"Error returning tool. SQL: update tool set person=$owner->ix where ix=$spectool");
  	       }
             }
  	  }


         // IF updating: push to other person
	 if ($_GET["a"] == "pt" ) { // push to
		$pt = mysql_escape_string($_GET[pt]);
		// Who has this tool?
		if ($pq = $db->query("SELECT * from person where ix=$pt limit 1")) { // success?
		  $ptn=$pq->fetch_object(); // push to-name

	     if ($person->ix == $pt ) { // already have it?
	         $out .= "$ptn->persname har allerede dette verktøyet.<p>";
  	     } else {
	       if ($upq = $db->query("update tool set person=$pt where ix=$spectool")) {
	         $out .= "OK verktøyet ble registrert på $ptn->persname.<p>";
	         logg(NULL,NULL,NULL,$spectool,"$ptn->persname har det");
   	       } else {
	         logg(NULL,NULL,NULL,$spectool,"Error reassigning tool. SQL: update tool set person=$pt where ix=$spectool");
  	       }
             }

		    } else {
		       logg(NULL,NULL,$pt,NULL,"Push to-user does not exist");
		       // LOG THIS
		    }
		    $pq->close();
		} // action pt






         // IF updating: push to... (display selection box)
	 if ($_GET["a"] == "p" ) { // rutern tool
	    $scripts .= "";
	    $out .= "<form action=\"\" method=\"get\">
	    	     <input type=hidden name=t value=$spectool>
	    	     <input type=hidden name=a value=pt>
	    	     Overlat verktøyet til: <select name=pt onchange='this.form.submit()'>";
	    $qs= "select persname, person.ix from person,division,company
	    	  where active=1
	    	  and company.ix=division.company and division.ix=person.division
	    	  order by persname";
	    if ($eq = $db->query($qs)) {
	       while ($emp = $eq->fetch_object()){
                 $out .= "<option value=\"$emp->ix\">$emp->persname</option>";
               }
               $eq->close();
            }
	    $out .= "</select></form>";
  	  }

	   $out .= "<p><i>Historikk:</i><br>";
  	  // show recent tool history
	    $qs= "select * from log where tool=$spectool order by date desc limit 10";
	    if ($eq = $db->query($qs)) {
	       while ($emp = $eq->fetch_object()){
                 $out .= "$emp->date: $emp->text<br>";
               }
               $eq->close();
            }

$out .= "
</div>
<div>
<p>
linker: Bookingkalender - Verktøyliste - Personliste";
   } // fetch tool
   /* free result set */
   $result->close();
} else {
  $out .= "Hmmm no such tool ($spectool) found";
  logg(NULL,NULL,NULL,$spectool,"No such tool.");
  // LOG THIS
}

echo "<html>
<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
<title>Fellesverktøy</title>
<style>
.toolbox {
	border: solid;
	border-radius: 25px;
	padding: 15px;
	margin: 1px;
	width: 100%;
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
