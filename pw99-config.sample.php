<?php

//In case you want to show errors
// ini_set('display_errors', '1');
// ini_set('display_startup_errors', '1');
// error_reporting(E_ALL);

// Start the session
session_start();

// Database
DEFINE ('DB_NAME', 'pinkwrite99database');
DEFINE ('DB_USER', 'pinkwrite99user');
DEFINE ('DB_PASSWORD', 'pinkwrite99password');
DEFINE ('DB_HOST', 'localhost');

// Home URL base
DEFINE ('PW99_HOME', 'https://99.pinkwrite.com');

// Site Title
DEFINE ('SITE_TITLE', 'PinkWrite 99');

// *** DANGER ZONE *** Developer & Webmin settings
$config = [
  'configured' => true,          // Configured (for install purposes)
  'allowcreateadmin' => false,   // Only set true for emergency recovery access to an admin account!
];

// Make the connection & character set
$dbc = mysqli_connect (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
mysqli_set_charset($dbc, 'utf8');

// Friendly variables
$pw99Home = PW99_HOME;
$siteTitle = SITE_TITLE;
