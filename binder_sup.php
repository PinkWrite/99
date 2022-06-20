<?php

// Require the configuration before any PHP code as the configuration controls error reporting
require('./pw99-config.php');

// A settings page requires form functions
require_once('./includes/form_functions.inc.php');


// Include the header file
$active_blocks = '';
$active_enrollment = '';
$active_observation = '';
$active_staffing = 'active';
$active_locker = '';
$active_admin = 'active';
$active_editor = '';
$active_observer = '';
$active_dash = '';
$page_title = "Admin Staffing :: $siteTitle";
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

	// Only admins for the delete page
	if ( ($_SESSION['user_is_supervisor'] != true) && ($_SESSION['user_is_admin'] != true) ) {
		header("Location: " . PW99_HOME);
		exit(); // Quit the script
	} elseif ($_SESSION['user_is_admin'] == true) {
		$usr_type = "Admin";
	} elseif ($_SESSION['user_is_supervisor'] == true) {
		$usr_type = "Supervisor";
	}

	// Dashboard
	$dashgreeting = "Admin memo view for $u_name";
	include('./inserts/dash_adminor.ins.php');

} else {
	header("Location: " . PW99_HOME);
	exit(); // Quit the script
}

// Heading
echo '<h2 class="lt">Memos</h2>';

// Note table
if (isset($_GET['w'])) {
	if (filter_var($_GET['w'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
		$writer_id = preg_replace("/[^0-9]/","", $_GET['w']);
		$where_am_i = "binder_editor.php?w=$writer_id";
	}
} elseif (isset($_GET['b'])) {
	if (filter_var($_GET['b'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
		$block = preg_replace("/[^0-9]/","", $_GET['b']);
		$where_am_i = "binder_editor.php?b=$block";
	}
} elseif (isset($_GET['m'])) {
	if (filter_var($_GET['m'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
		$editor_main_block = (preg_replace("/[^0-9]/","", $_GET['m']));
		$where_am_i = "binder_editor.php?m=$editor_main_block";
	}
} elseif (isset($_GET['v'])) {
	if (filter_var($_GET['v'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
		$editor_all_notes = (preg_replace("/[^0-9]/","", $_GET['v']));
		$where_am_i = "binder_editor.php?v=$editor_all_notes";
	}
} else {
	$where_am_i = "binder_sup.php";
}
include('inserts/list_notes_editor.ins.php');

// Include the footer file to complete the template
require('./includes/footer.html');
?>
