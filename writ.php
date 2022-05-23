<?php

// Require the configuration before any PHP code as the configuration controls error reporting
require('./pw99-config.php');

// A settings page requires form functions
require_once('./includes/form_functions.inc.php');


// Include the header
$active_writs = '';
$active_blocks = '';
$active_notes = '';
$active_locker = '';
$active_admin = '';
$active_editor = '';
$active_observer = '';
$active_dash = 'activedash';
$page_title = "$siteTitle";
include('./includes/header.html');

// Make sure we're not here by accident
if (isset($_SESSION['user_id'])) {
	// Okay to view this page
	$userid = $_SESSION['user_id'];
	$q = "SELECT name, type, email FROM users WHERE id='$userid'";
	$r = mysqli_query ($dbc, $q);
	$row = mysqli_fetch_array($r, MYSQLI_NUM);
	$u_name = "$row[0]";
	$u_type = "$row[1]";
	$u_email = "$row[2]";

	if ($_SESSION['user_is_admin'] == true) {
		$usr_type = "Admin";
	} elseif ($_SESSION['user_is_supervisor'] == true) {
		$usr_type = "Supervisor";
	} elseif ($_SESSION['user_is_editor'] == true) {
		$usr_type = "Editor";
	} elseif ($_SESSION['user_is_observer'] == true) {
		header("Location: observer.php");
  	exit(); // Quit the script
	} elseif ($_SESSION['user_is_writer'] == true) {
	 $usr_type = "Writer";
	}

} else {
  header("Location: " . PW99_HOME);
  exit(); // Quit the script
}

// Dashboard
$dashgreeting = "Writ for $u_name";
include('./inserts/dash.ins.php');

// Content
include('./inserts/writ.ins.php');


// Include the footer file to complete the template
require('./includes/footer.html');
?>
