<?php

// Require the configuration before any PHP code as the configuration controls error reporting
require('./pw99-config.php');

// A settings page requires form functions
require_once('./includes/form_functions.inc.php');


// Include the header file
$active_writs = '';
$active_blocks = '';
$active_roll = '';
$active_locker = 'active';
$active_admin = '';
$active_editor = 'active';
$active_observer = '';
$active_dash = '';
$page_title = "Register :: $siteTitle";
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

		// Only admins and Main teachers for this registration page
		if ( ($_SESSION['user_is_editor'] != true) && ($_SESSION['user_is_supervisor'] != true) && ($_SESSION['user_is_admin'] != true) ) {
			header("Location: " . PW99_HOME);
			exit(); // Quit the script
		} elseif ($_SESSION['user_is_admin'] == true) {
			$usr_type = "Admin";
		} elseif ($_SESSION['user_is_supervisor'] == true) {
			$usr_type = "Supervisor";
		} elseif ($_SESSION['user_is_editor'] == true) {
			$usr_type = "Editor";
		}

		$dashgreeting = "Editor Locker for $u_name";
		include('./inserts/dash_editor.ins.php');

		// Insert the page content
		if (isset($_SESSION['user_id'])) {
			$editor_id = $_SESSION['user_id'];
			$rformaction = 'register.php'; // This must be set for the include to work
			include('inserts/register.ins.php');
		} else {
			header("Location: " . PW99_HOME);
			exit(); // Quit the script
		}

	} else {
		header("Location: " . PW99_HOME);
		exit(); // Quit the script
	}

// Include the HTML footer
include('./includes/footer.html');
?>
