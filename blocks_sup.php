<?php

// Require the configuration before any PHP code as the configuration controls error reporting
require('./pw99-config.php');

// A settings page requires form functions
require_once('./includes/form_functions.inc.php');


// Include the header
$active_blocks = 'active';
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

} else {
	header("Location: " . PW99_HOME);
	exit(); // Quit the script
}

// Dashboard
$dashgreeting = "Admin Blocks for $u_name";
include('./inserts/dash_adminor.ins.php');

// Specific editor?
if ((isset($_GET['e'])) && (filter_var($_GET['e'], FILTER_VALIDATE_INT, array('min_range' => 1)))) {
	$e_id = preg_replace("/[^0-9]/","", $_GET['e']);
	$editor_suffix = "?e=$e_id";
} else {
	$editor_suffix = "";
}

// Heading
echo '<h2 class="lt">Open Blocks</h2>';

// Editor confirm & information
if (isset($e_id)) {
	$q = "SELECT name, email FROM users WHERE id='$e_id' AND NOT type='writer' AND NOT type='observer'";
	$r = mysqli_query ($dbc, $q);
	if (mysqli_num_rows($r) == 1) {
		$row = mysqli_fetch_array($r, MYSQLI_NUM);
		$name = "$row[0]";
		$email = "$row[1]";
		echo '<h3 class="lt sans">For editor: '.$name.' <small>('.$email.')</small></h3>';
	} else {
		$editor_suffix = "";
		unset($e_id);
	}
}

// Actions
set_switch("New block +", "Create new block", "block_sup.php", "new_block", $userid, "newNoteButton");
echo '<br>';
set_button("Closed blocks &rarr;", "Manage closed blocks", "blocks_closed_sup.php${editor_suffix}", "newNoteButton");
echo '<br>';

// Blocks table
$where_am_i = "blocks_sup.php";
include('./inserts/list_blocks_sup.ins.php');

// Include the footer file to complete the template
require('./includes/footer.html');
?>
