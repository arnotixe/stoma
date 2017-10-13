<?php

// STOMA config

$dbhost = "localhost";              // Host where database resides
$dbname = "db_name";                // Name of database
$dbuser = "db_user";                // Database username
$dbpassword = "db_password";        // Database password
$cronpasswd = "cron password";      // Sitewide password used for running cron.php?p=<cron password>
$siteurl = "http://example.com";    // domain incl type, http://example.com or https://example.com
$sitebase = "/"; // base dir
$mailsender = "Shared Tools Management System";
$mailsendermail = "stoma@example.com";  // these two form together "Shared Tools Management System <stoma@example.com>" but they must be utf-8 encoded separately in the header
$mailbest = "Shared Tools Management System"; // best, xxx
// ------------------------------------------------------------------------------------------------------------
// Don't edit below code
// ------------------------------------------------------------------------------------------------------------

require "functions.php";
?>
