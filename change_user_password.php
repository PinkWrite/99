<?php

// Require the configuration before any PHP code as the configuration controls error reporting
require('./pw99-config.php');

// A settings page requires form functions
require_once('./includes/form_functions.inc.php');


// Include the header file
$active_blocks = '';
$active_enrollment = '';
$active_observation = '';
$active_staffing = '';
$active_locker = '';
$active_admin = 'active';
$active_editor = '';
$active_observer = '';
$active_dash = '';
$page_title = "Admin Locker :: $siteTitle";
include('./includes/header.html');

// Logged in or not?
if (isset($_SESSION['user_id'])) {
	$userid = $_SESSION['user_id'];
	$q = "SELECT name, type FROM users WHERE id='$userid'";
	$r = mysqli_query ($dbc, $q);
	$row = mysqli_fetch_array($r, MYSQLI_NUM);
	$u_name = "$row[0]";
	$u_type = "$row[1]";

	// Only admins for this page
	if ( ($_SESSION['user_is_supervisor'] != true) && ($_SESSION['user_is_admin'] != true) ) {
		header("Location: " . PW99_HOME);
		exit(); // Quit the script
	} elseif ($_SESSION['user_is_admin'] == true) {
		$usr_type = "Admin";
	} elseif ($_SESSION['user_is_supervisor'] == true) {
		$usr_type = "Supervisor";
	}

} else {
	header("Location: " . PW99_HOME);
	exit(); // Quit the script
}

// Dashboard
$dashgreeting = "Admin Locker for $u_name";
include('./inserts/dash_adminor.ins.php');

// Insert the page content
$rformaction = 'change_user_password.php'; // This must be set for the include to work
// Insert the page content
include('inserts/admin_change_password.ins.php');

// Include the HTML footer
include('./includes/footer.html');
?>
