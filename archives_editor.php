<?php

// Require the configuration before any PHP code as the configuration controls error reporting
require('./pw99-config.php');

// A settings page requires form functions
require_once('./includes/form_functions.inc.php');


// Include the header file
$active_binder = '';
$active_writs = '';
$active_blocks = '';
$active_roll = '';
$active_locker = 'active';
$active_admin = '';
$active_editor = 'active';
$active_observer = '';
$active_dash = '';
$page_title = "Editor Archives :: $siteTitle";
include('./includes/header.html');

// Make sure we're not here by accident
if (isset($_SESSION['user_id'])) {
	// Okay to view this page
	$userid = $_SESSION['user_id'];
	$q = "SELECT name, type FROM users WHERE id='$userid'";
	$r = mysqli_query ($dbc, $q);
	$row = mysqli_fetch_array($r, MYSQLI_NUM);
	$u_name = "$row[0]";
	$u_type = "$row[1]";

	// Only admins and editors for this page
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

	// Dashboard
	$dashgreeting = "Editor Locker for $u_name";
	include('./inserts/dash_editor.ins.php');

} else {
  header("Location: " . PW99_HOME);
  exit(); // Quit the script
}

// Action message
echo (isset($_SESSION['act_message'])) ? $_SESSION['act_message'] : false ;
if (isset($_SESSION['act_message'])) {unset($_SESSION['act_message']);}

// Header
echo '<h2>Editor Archives</h2>';

// Writ table
$review_status = 'archived';
$where_am_i = "archives_editor.php";
include('inserts/list_editor.ins.php');

// Include the HTML footer
include('./includes/footer.html');
?>
