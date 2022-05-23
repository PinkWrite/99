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
$page_title = "Admin Register :: $siteTitle";
include('./includes/header.html');

// Logged-in redirect
	// We need this first to check errors
	include('./includes/login_check.inc.php');

	// Logged in or not?
	if (isset($_SESSION['user_id'])) {
		$userid = $_SESSION['user_id'];
		$q = "SELECT name, type FROM users WHERE id='$userid'";
		$r = mysqli_query ($dbc, $q);
		$row = mysqli_fetch_array($r, MYSQLI_NUM);
		$u_name = "$row[0]";
		$u_type = "$row[1]";

		// Only admins for this registration page
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

		// Heading
		echo '<h2 class="lt">Admin Register</h2>';

		// Insert the page content
		$rformaction = 'register_admin.php'; // This must be set for the include to work
		include('inserts/register_admin.ins.php');

	} else {
		header("Location: " . PW99_HOME);
		exit(); // Quit the script
	}

// Include the HTML footer
include('./includes/footer.html');
?>
