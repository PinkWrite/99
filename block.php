<?php

// Require the configuration before any PHP code as the configuration controls error reporting
require('./pw99-config.php');

// A settings page requires form functions
require_once('./includes/form_functions.inc.php');


// Include the header
$active_writs = '';
$active_blocks = 'active';
$active_notes = '';
$active_binder = '';
$active_locker = '';
$active_admin = '';
$active_editor = '';
$active_observer = '';
$active_dash = 'active';
$page_title = "Blocks :: $siteTitle";
$page_title = "$siteTitle";
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
		header("Location: observer.php");
  	exit(); // Quit the script
	} elseif ($_SESSION['user_is_writer'] == true) {
	 $usr_type = "Writer";
	}

	// Dashboard
	$dashgreeting = "Block with $u_name";
	include('./inserts/dash.ins.php');

} else {
	header("Location: " . PW99_HOME);
	exit(); // Quit the script
}

// Memos for this block
if ((isset($_GET['v'])) && ((filter_var($_GET['v'], FILTER_VALIDATE_INT, array('min_range' => 0))) || ($_GET['v'] == '0'))) {
  $editor_set_block = preg_replace("/[^0-9]/","", $_GET['v']);
	include('inserts/list_notes_editor_10.ins.php');
}

// Content
$where_am_i = "blocks.php";
include('./inserts/block.ins.php');

// Include the footer file to complete the template
require('./includes/footer.html');
?>
