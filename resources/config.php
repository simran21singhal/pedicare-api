<?php
ob_start();

session_start();

//define('TIMEZONE', 'Asia/Kolkata');

//date_default_timezone_set(TIMEZONE);

defined("DS") ? null : define("DS", DIRECTORY_SEPARATOR);

// defined("RESOURCES") ? null :define("RESOURCES", ($_SERVER["SERVER_NAME"] == "localhost")
//    ? "http:".DS.DS."localhost".DS."ombre".DS."public".DS."resources".DS
//    : "http:".DS.DS."ombre.com".DS."resources"
// );

defined("DB_HOST") ? null : define("DB_HOST", "localhost");

defined("DB_PASS") ? null : define("DB_PASS", "android44");

defined("DB_NAME") ? null : define("DB_NAME", "vaccine_db");

defined("DB_USER") ? null : define("DB_USER", "root");


$connection = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

require_once("functions.php");

?>
