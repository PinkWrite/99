<?php

// Require the configuration before any PHP code as the configuration controls error reporting
require('./pw99-config.php');

// A settings page requires form functions
require_once('./includes/form_functions.inc.php');


// Include the header
$active_blocks = '';
$active_enrollment = '';
$active_observation = '';
$active_staffing = '';
$active_locker = 'active';
$active_admin = 'active';
$active_editor = '';
$active_observer = '';
$active_dash = '';
$page_title = "Admin Login Fails :: $siteTitle";
include('./includes/header.html');

// Logged in or not?
if (isset($_SESSION['user_id'])) {
	$userid = $_SESSION['user_id'];
	$q = "SELECT name, type, email FROM users WHERE id='$userid'";
	$r = mysqli_query ($dbc, $q);
	$row = mysqli_fetch_array($r, MYSQLI_NUM);
	$u_name = "$row[0]";
	$u_type = "$row[1]";
	$u_email = "$row[2]";

	// Only admins for this page
	if ( ($_SESSION['user_is_supervisor'] != true) && ($_SESSION['user_is_admin'] != true) ) {
		header("Location: " . PW99_HOME);
		exit(); // Quit the script
	} elseif ($_SESSION['user_is_admin'] == true) {
		$usr_type = "Admin";
	} elseif ($_SESSION['user_is_supervisor'] == true) {
		$usr_type = "Supervisor";
	}

	$dashgreeting = "Admin Locker for $u_name";
	include('./inserts/dash_adminor.ins.php');

} else {
	header("Location: " . PW99_HOME);
	exit(); // Quit the script
}

// Fails
echo '<h2 class="lt">Login fails</h2>';
echo '<p class="sans dk">IPs blocked after failing login five times from the same browser session in a short period of time</p>';
$where_am_i = "login_fails.php";
include('./inserts/list_login_fails_sup.ins.php');

// Include the footer file to complete the template
require('./includes/footer.html');
?>
