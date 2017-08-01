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

$out .="<p>STEP1: Check tool calibration status<p>";
        // CRON STEP 1
	// check calibration dates
        $qs = "select *,datediff(nextcalibration, date(convert_tz(now(),@@session.time_zone,'CET'))) as dtc
               from tool
               where not isnull(nextcalibration)"; // selects all tools with calibration date set
        if ($eq = $db->query($qs)){
                while ($extool = $eq->fetch_object()) {
//			var_dump($extool);
                        $out .= "$extool->ix expirydate $extool->name, in $extool->dtc day(s):";
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
                }
                // build 1 mail per each owner and 1 per division manager and send them
        }

$out .="<p>STEP2: Trouble with any bookings tomorrow?<p>";
        // CRON STEP 2
	// check bookings for tomorrow and see if the tools have been returned. If not, notify both owner and booker
	$qs = "select tool.name,owner.persname as owner,booker.persname as booker,holder.persname as holder,booking.date
	       from tool,booking,person as owner,person as booker,person as holder
	       where booker.ix=booking.person and holder.ix=tool.person and tool.owner=owner.ix and tool.ix=booking.tool and booking.date=date(convert_tz(now(),@@session.time_zone,'CET')) + interval 1 day";
//	$out .= "<br>$qs<br>";
        if ($eq = $db->query($qs)){
                while ($bt = $eq->fetch_object()) {
//			var_dump($bt);
                        $out .= "tool $bt->name is booked tomorrow by $bt->booker, held by $bt->holder and owned by $bt->owner: ";
                        if ($bt->holder <> $bt->owner) {
                                $out .= " ADVICE both $bt->booker and $bt->holder<br>";
                                // add to expired/to-expire array, and do the sending below
                                // need tool owner mail and division manager mail
                        } else {
                                $out .= " should be OK<br>";
                                // add to expired/to-expire array, and do the sending below
                                // need tool owner mail and division manager mail
                        }
                }
        }

} else {
	$out .= "login not ok. GET parameter p needs to be set to the same as in config.php";
}

echo $out;

?>
