<?php

// STOMA site-wide functions

// This file is called from config.php

// Connect to database
//   $dbconn = mysql_connect($dbhost, $dbuser, $dbpassword) or die(mysql_error());
//   $db = mysql_select_db($dbname) or die(mysql_error());

  $db = new mysqli($dbhost, $dbuser, $dbpassword, $dbname);

  /* check connection */
  if ($db->connect_errno) {
    printf("Database connection failed: %s\n", $db->connect_error);
    exit();
  }
  mysqli_set_charset($db,"utf8");

	function logg($comp, $div, $pers, $tool, $txt) {
		global $db;
$logsql = "insert into log
	   (ip, date, company, division, person, tool, text) values 
           ( \"$_SERVER[REMOTE_ADDR]\", convert_tz(now(),@@session.time_zone,'CET'), 
	   NULLIF('$comp',''), NULLIF('$div',''), NULLIF('$person',''), NULLIF('$tool',''), \"$txt\")";
if ($pq = $db->query($logsql)) {
	// all is ok
} else {
echo "log sql failed: $logsql ";
}

		return true;
	}

?>
