<?php

// Require the configuration before any PHP code as the configuration controls error reporting
require('./pw99-config.php');

// Destroy the session
$_SESSION = array(); // Destroy the variables
session_destroy(); // Destroy the session itself
setcookie(session_name(), null, 86401); // Set any _SESSION cookies to expire in Jan 1970

// Restart the session
session_start();
$_SESSION['logout'] = true;
// Redirect
header("Location: in");
exit(); // Quit the script

?>
