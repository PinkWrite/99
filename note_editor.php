<?php

// Require the configuration before any PHP code as the configuration controls error reporting
require('./pw99-config.php');

// A settings page requires form functions
require_once('./includes/form_functions.inc.php');


// Include the header
$active_notes = 'active';
$active_writs = '';
$active_blocks = '';
$active_roll = '';
$active_locker = '';
$active_admin = '';
$active_editor = 'activedash';
$active_observer = '';
$active_dash = '';
$page_title = "Editor :: $siteTitle";
include('./includes/header.html');

// Logged in or not?
if (isset($_SESSION['user_id'])) {
	// Okay to view this page
	$userid = $_SESSION['user_id'];
	$q = "SELECT name, username, email, blocks, level, editor FROM users WHERE id='$userid'";
	$r = mysqli_query ($dbc, $q);
	$row = mysqli_fetch_array($r, MYSQLI_NUM);
	$u_name = "$row[0]";
	$u_usrn = "$row[1]";
	$u_email = "$row[2]";
	$u_class = "$row[3]";
	$u_level = "$row[4]";
	$u_editor = "$row[5]";

} else {
	header("Location: " . PW99_HOME);
	exit(); // Quit the script
}

// Dashboard
$dashgreeting = "Editor Notes for $u_name";
include('./inserts/dash_editor.ins.php');

// Content
include('./inserts/note_editor.ins.php');

// Include the footer file to complete the template
require('./includes/footer.html');
?>
