<?php

// Require the configuration before any PHP code as the configuration controls error reporting
require('./pw99-config.php');

// A settings page requires form functions
require_once('./includes/form_functions.inc.php');

// Include the header file
$active_writs = '';
$active_blocks = '';
$active_notes = '';
$active_binder = 'active';
$active_locker = '';
$active_admin = '';
$active_editor = '';
$active_observer = '';
$active_dash = 'active';
$page_title = "Binder :: $siteTitle";
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

	if ($_SESSION['user_is_admin'] == true) {
		$usr_type = "Admin";
	} elseif ($_SESSION['user_is_supervisor'] == true) {
		$usr_type = "Supervisor";
	} elseif ($_SESSION['user_is_editor'] == true) {
		$usr_type = "Editor";
	} elseif ($_SESSION['user_is_observer'] == true) {
		$usr_type = "Observer";
	} elseif ($_SESSION['user_is_writer'] == true) {
	  $usr_type = "Writer";
	}

	// Dashboard
	$dashgreeting = "Binder for $u_name";
	include('./inserts/dash.ins.php');

} else {
	header("Location: " . PW99_HOME);
	exit(); // Quit the script
}

// Heading
if ((isset($_GET['w'])) && (filter_var($_GET['w'], FILTER_VALIDATE_INT)) ) {
	// Before setting an ID
	// Snippet of notes from blocks, incl Main
	$limit_rows = 5;
	$has_editor_notes = true; // So we don't get a header message
	echo '<h4>Memos for me</h4>';
	include('inserts/list_notes_editor_10.ins.php');

	// Now set ID so it doesn't interfere with what's happening above
	$editor_set_writer_id = preg_replace("/[^0-9]/","", $_GET['w']);
	$where_am_i = "binder.php?w=$editor_set_writer_id";
} else {
	$where_am_i = "binder.php";

	// Snippet of notes only for user
	$limit_rows = 5;
	$editor_set_writer_id = $userid;
	$has_editor_notes = true; // So we don't get a header message
	echo '<h4>Memos for me</h4>';
	include('inserts/list_notes_editor_10.ins.php');
}
// Binder table
include('inserts/list_notes_editor_view.ins.php');

// Include the footer file to complete the template
require('./includes/footer.html');
?>
