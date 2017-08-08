<?php

// STOMA
   setcookie('remembertools', 'logout',  time() - 3600, $siteurl, $sitebase, false, true);
// cookie must be deleted BEFORE functions.php, or it is re-logged-in... Too effective persistent login...

require("config.php");
//
//var_dump($_COOKIE['remembertools']);
//return;
header('Content-Type: text/html; charset=utf-8');

   $usr=$_SESSION['fvuser'];
//   $db->query("delete from auth_tokens where userid=$usr");


//$db->query("delete from auth_tokens where userid=$usr"); // delete auth tokens FIXME this logs user out of all machines

   unset($_SESSION["fvuser"]); // delete session user
   unset($_SESSION["fvwh"]); // delete session warehouse
   unset($_SESSION["fvtool"]); // delete session tool
//   setcookie('remembertools', 'logout',  time() - 3600, $siteurl, $sitebase, false, true);

   logg(NULL,NULL,$usr,NULL,"User logged out");
// should delete all entries in auth_tokens as well

//echo "Logged in user is now \"" . $_SESSION['fvuser'] . "\"<br> and remembertools cookie is: ";
//var_dump($_COOKIE['remembertools']);
//echo "<p><a href=../?t=1>tool1</a>";


//var_dump($_COOKIE['remembertools']);

//DEBUG
// var_dump($_SESSION);
   header("Location:index.php");

?>

