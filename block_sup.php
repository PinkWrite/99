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
$active_locker = '';
$active_admin = 'active';
$active_editor = '';
$active_observer = '';
$active_dash = '';
$page_title = "Admin Blocks :: $siteTitle";
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

} else {
  header("Location: " . PW99_HOME);
  exit(); // Quit the script
}

// Only admins for the editor page
if ( ($_SESSION['user_is_admin'] != true) && ($_SESSION['user_is_supervisor'] != true) ) {
	header("Location: " . PW99_HOME);
	exit(); // Quit the script
}

// Dashboard
$dashgreeting = "Admin Blocks for $u_name";
include('./inserts/dash_adminor.ins.php');

// Content
set_button("Open blocks &rarr;", "Return to open blocks", "blocks_sup.php", "newNoteButton");
echo '<br><br>';
include('./inserts/block_sup.ins.php');

// Include the footer file to complete the template
require('./includes/footer.html');
?>
