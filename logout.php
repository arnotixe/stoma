<?php

// STOMA
require("config.php");

header('Content-Type: text/html; charset=utf-8');

   $usr=$_SESSION['fvuser'];
//   $db->query("delete from auth_tokens where userid=$usr");

   unset($_SESSION["fvuser"]); // delete session user
   unset($_SESSION["fvwh"]); // delete session warehouse
   unset($_SESSION["fvtool"]); // delete session tool
   setcookie('remembertools', '',  time() -3600, '/fv', 'teigseth.no', false, true);
   logg(NULL,NULL,$usr,NULL,"User logged out");
// should delete all entries in auth_tokens as well

//echo "Logged in user is now \"" . $_SESSION['fvuser'] . "\"<br> and remembertools cookie is: ";
//var_dump($_COOKIE['remembertools']);
//echo "<p><a href=../?t=1>tool1</a>";
   header("Location:index.php");

?>

