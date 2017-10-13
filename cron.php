<?php

// STOMA
require("config.php");

header('Content-Type: text/html; charset=utf-8');

/*
if (empty($_SESSION['fvuser'])) {
//echo "You must login.";
	header('Location:login.php');
return;
}

// INSTEAD of user login to this page, a sitewide cron password is used here, see config.php

*/

// THIS PAGE SHOULD NOT OUTPUT ANY CONTENT WHATSOEVER (cron will barf). Errors and such should be logged instead
// This script should be run once each day, for example at 19:00 or at whatever time by which all tools should have been returned

$out = "";


// define run-parts, if applicable ?
if ($_GET["p"] == $cronpasswd) {
	$out .= "login ok. Possibly depending on run-parts, check:<br>
		1 Calibration date, send emails to each tool owner and division and/or company admin (group tools per owner and admin to send only one mail per person)<br>
		2 Bookings active for tomorrow but tool is not returned, send warning emails to both booker and current holder<p>
		";

$specifictoolonlytext == "";
if ( $_GET["st"] <> "") { // check specific tool only?
	$specifictoolonlytext = "and tool.ix=" . mysql_escape_string($_GET["st"]); // WARNING this deprecates
	$out .= "<p>Checking only one tool: $specifictoolonlytext";
}

if ( $_GET["c_c"] == "1") { // calibration status check+mail?
$out .="<p>STEP1: Check tool calibration status<p>";
        // CRON STEP 1
	// check calibration dates
        $qs = "select *,datediff(nextcalibration, date(convert_tz(now(),@@session.time_zone,'CET'))) as dtc
               from tool
               where not isnull(nextcalibration)
                 $specifictoolonlytext
                 and calibrationperiod > 0"; // selects all tools with calibration date set
        if ($eq = $db->query($qs)){
                while ($extool = $eq->fetch_object()) {
//			var_dump($extool);
                        $out .= "$extool->ix expirydate $extool->name, in $extool->dtc day(s):";
			if ($extool->calibrationperiod > 0 ) { // should this tool be calibrated?
                        	if ($extool->dtc > 30 ) { // still got time
                                	$out .= " still got time.<br>";
                        	}
                        	if ($extool->dtc <= 0 ) { // expired
                                	$out .= " EXPIRED! needs calibration<br>";
                                	// add to expired/to-expire array, and do the sending below
                                	// need tool owner mail and division manager mail
                        	} else {
                                	if ($extool->dtc <= 14 ) { // expired, FIXME should be user definable
                                        	$out .= " expires SOON<br>";
                                        	// add to expired/to-expire array, and do the sending below
                                        	// need tool owner mail and division manager mail
                                	}
                        	}
			} else {
                        	$out .= " … but calibration is turned off for this tool. Nevermind. <br>";
			}
                }
                // build 1 mail per each owner (and optionally 1 per division manager) and send them

                // find different owners with at least one tool in need of calibration (if less than 14 days before date)
                $oqs = "select owner,persname,mail from
                	   (select owner,persname,mail,calibrationperiod,datediff(nextcalibration, date(convert_tz(now(),@@session.time_zone,'CET'))) as dtc
                	    from tool, person
                	    where calibrationperiod > 0
		              $specifictoolonlytext
                	      and person.ix = tool.owner) as txtend
                	where dtc <= 14
                	group by owner";
	        if ($oq = $db->query($oqs)){
        	        while ($owid = $oq->fetch_object()) {
		         	$mailbody = "Hei $owid->persname,\n\nEtt eller flere av dine verktøy trenger kalibrering:\n";
        	        	$out .= "<br>owner $owid->owner has some tool(s) in need of calibration:";
		                $tqs = "select *,datediff(nextcalibration, date(convert_tz(now(),@@session.time_zone,'CET'))) as dtc
                		        from tool
                		        where calibrationperiod > 0
					  $specifictoolonlytext
                	      		  and owner=$owid->owner";

	        		if ($tq = $db->query($tqs)){
	        			$ulist = 1;
        	        		while ($tlexp = $tq->fetch_object()) {
		        	        	$out .= "<br>--$tlexp->name";
		        	        	if ($tlexp->dtc < 0 ) {
		        	        		$mailbody .="$ulist) Verktøyet \"$tlexp->name\" (serienr \"$tlexp->serialno\") skulle vært kalibrert $tlexp->nextcalibration, for $tlexp->dtc" . "- dager siden.\n";
		        	        	} else {
		        	        		$mailbody .="$ulist) Verktøyet \"$tlexp->name\" (serienr \"$tlexp->serialno\") skal kalibreres $tlexp->nextcalibration (om $tlexp->dtc dager).\n";
		        	        	}
		        	        	$ulist++;
        	        		}
	       			}
	       			$mailbody .= "\n\nVennligst ta kontakt med verktøyansvarlig.\n\nmvh\n$mailbest\n";
	       			$out .= "Send følgende mail til $owid->mail:$mailbody\n";

				$headers = "Content-type: text/plain; charset=utf-8\r\n" .
					   "From: =?UTF-8?B?".base64_encode($mailsender)."?= <$mailsendermail>\r\n" .
					   "Reply-To: $mailsendermail\r\n" .
					   "X-Mailer: PHP/" . phpversion();
				mail($owid->mail,
				     "=?UTF-8?B?".base64_encode("Verktøy som må kalibreres")."?=",
				     $mailbody,
				     $headers); // Actually send it
        	        }
        	}// list all owners with expired tools
        }
} else {
	$out .="<p>STEP1: Check tool calibration status was NOT selected  (GET parameter ?c_c=1 missing) <p>";
}


if ( $_GET["c_b"] == "1") { // booking status check+mail?
$out .="<p>STEP2: Trouble with any bookings tomorrow?<p>";
        // CRON STEP 2
	// check bookings for tomorrow and see if the tools have been returned. If not, notify both owner and booker
	$qs = "select tool.name,owner.persname as owner,
	              booker.persname as booker, booker.mail as bookermail, booker.phone as bookerphone,
	              holder.persname as holder, holder.mail as holdermail, holder.phone as holderphone,
	              booking.date
	       from tool, booking, person as owner, person as booker, person as holder
	       where booker.ix=booking.person
                 $specifictoolonlytext
 	         and holder.ix=tool.person
	         and tool.owner=owner.ix
	         and tool.ix=booking.tool
	         and booking.date=date(convert_tz(now(),@@session.time_zone,'CET')) + interval 1 day";
//	$out .= "<br>$qs<br>";
        if ($eq = $db->query($qs)){
                while ($bt = $eq->fetch_object()) {
//			var_dump($bt);
                        $out .= "$bt->name is booked tomorrow by $bt->booker, held by $bt->holder and owned by $bt->owner: ";
                        if ($bt->holder <> $bt->booker) {
                                $out .= " ADVICE both $bt->booker and $bt->holder<br>";
                                // add to expired/to-expire array, and do the sending below
                                // need tool owner mail and division manager mail
				$headers = "Content-type: text/plain; charset=utf-8\r\n" .
					   "From: =?UTF-8?B?".base64_encode($mailsender)."?= <$mailsendermail>\r\n" .
					   "Reply-To: $mailsendermail\r\n" .
					   "X-Mailer: PHP/" . phpversion();

		         	$mailbody = "Hei $bt->holder,
\nDet ser ut til at du fremdeles har verktøyet \"$bt->name\", men dette er booket i morgen av $bt->booker.
\nVennligst sørg for å legge det på plass innen i morgen, eller ta kontakt på $bt->bookerphone for å finne ut hvordan det kan leveres.
\n$bt->booker har også fått beskjed om dette.
\nmvh
$mailbest";
				mail($bt->holdermail,
				     "=?UTF-8?B?".base64_encode("$bt->booker trenger $bt->name i morgen")."?=",
				     $mailbody,
				     $headers); // Actually send it */

		         	$mailbody = "Hei $bt->booker,
\nDu har booket verktøyet \"$bt->name\" i morgen, men $bt->holder har ikke levert det tilbake ennå.
\nDu kan ta kontakt på $bt->holderphone for å finne ut hvordan det kan leveres deg.
\n$bt->holder har også fått beskjed om at du trenger det i morgen.
\nmvh
$mailbest";
				mail($bt->bookermail,
				     "=?UTF-8?B?".base64_encode("Din booking av $bt->name")."?=",
				     $mailbody,
				     $headers); // Actually send it */

//                                var_dump($bt);
                        } else {
                                $out .= " should be OK<br>";
                        }
                }
        }


} else {
	$out .="<p>STEP2: Check tool booking status was NOT selected  (GET parameter ?c_b=1 missing) <p>";
}



} else {
	$out .= "login not ok. GET parameter p needs to be set to the same as in config.php";
}

echo $out;

?>
