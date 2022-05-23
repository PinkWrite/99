<?php

// Require the configuration before any PHP code as the configuration controls error reporting
require('./pw99-config.php');

// A settings page requires form functions
require_once('./includes/form_functions.inc.php');


// Include the header
$active_writs = '';
$active_blocks = '';
$active_roll = '';
$active_locker = '';
$active_admin = '';
$active_editor = 'active';
$active_observer = '';
$active_dash = '';
$page_title = "Editor Blocks :: $siteTitle";
include('./includes/header.html');

// Logged in or not?
if (isset($_SESSION['user_id'])) {
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

} else {
	header("Location: " . PW99_HOME);
	exit(); // Quit the script
}

if ((isset($_GET['v'])) && (filter_var($_GET['v'], FILTER_VALIDATE_INT, array('min_range' => 0))) || ($_GET['v'] == '0')) {
	$v = preg_replace("/[^0-9]/","", $_GET['v']);
} else {
	header("Location: editor.php");
	exit(); // Quit the script
}

// Dashboard
$dashgreeting = "Editor Blocks for $u_name";
include('./inserts/dash_editor.ins.php');

// Content
$term_status = 'current';
$where_am_i = "block_editor.php?v=$v";
$editor_id = $userid; // block_editor.ins.php needs this
include('./inserts/block_editor.ins.php');

// Include the footer file to complete the template
require('./includes/footer.html');
?>
