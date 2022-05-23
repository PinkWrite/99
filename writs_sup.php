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
$page_title = "Admin Writs :: $siteTitle";
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

	// Only admins and editors for this page
	if ( ($_SESSION['user_is_supervisor'] != true) && ($_SESSION['user_is_admin'] != true) ) {
		header("Location: " . PW99_HOME);
		exit(); // Quit the script
	} elseif ($_SESSION['user_is_admin'] == true) {
		$usr_type = "Admin";
	} elseif ($_SESSION['user_is_supervisor'] == true) {
		$usr_type = "Supervisor";
	}

	// Dashboard
	$dashgreeting = "Admin Locker for $u_name";
	include('./inserts/dash_adminor.ins.php');

} else {
	header("Location: " . PW99_HOME);
	exit(); // Quit the script
}

// Heading
echo '<h2 class="lt">All Writs</h2>';

// Action message
echo (isset($_SESSION['act_message'])) ? $_SESSION['act_message'] : false ;
if (isset($_SESSION['act_message'])) {unset($_SESSION['act_message']);}

// $where_was_i ?
if (isset($_SERVER['HTTP_REFERER'])) {
	$where_was_i = filter_var($_SERVER['HTTP_REFERER'], FILTER_VALIDATE_URL);
	set_button("&larr; Go back", "Return to the page that brought you here", $where_was_i, "newNoteButton");
}

// Specific writer?
if ((isset($_GET['w'])) && (filter_var($_GET['w'], FILTER_VALIDATE_INT, array('min_range' => 1)))) {
	$w_id = preg_replace("/[^0-9]/","", $_GET['w']);
	// Writer info
	$q = "SELECT name, email FROM users WHERE id='$w_id'";
	$r = mysqli_query ($dbc, $q);
	if (mysqli_num_rows($r) == 1) {
		$row = mysqli_fetch_array($r, MYSQLI_NUM);
		$name = "$row[0]";
		$email = "$row[1]";
		echo '<h3 class="lt sans">For writer: '.$name.' <small>('.$email.')</small></h3>';
	} else {
		echo '<h3 class="lt sans">For all writers</h3>';
	}
} else {
	echo '<h3 class="lt sans">For all writers</h3>';
}

// Shortcuts
set_button("Blocks &rarr;", "Filter writs through blocks list", "blocks_sup.php", "newNoteButton"); // Staffing
echo '&nbsp;&nbsp;&nbsp;';
set_button("Enrollment &rarr;", "Filter writs through writer enrollment list", "enrollment_sup.php", "newNoteButton"); // Enrollment
echo '&nbsp;&nbsp;&nbsp;';
set_button("Staffing &rarr;", "Filter writs through editor list", "staffing_sup.php", "newNoteButton"); // Staffing

// Writ table
$where_am_i = "writs_sup.php";
include('inserts/list_sup.ins.php');

// Include the footer file to complete the template
require('./includes/footer.html');
?>
