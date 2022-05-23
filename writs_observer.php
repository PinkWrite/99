<?php

// Require the configuration before any PHP code as the configuration controls error reporting
require('./pw99-config.php');

// A settings page requires form functions
require_once('./includes/form_functions.inc.php');


// Include the header file
$active_obsvwrits = 'active';
$active_observees = '';
$active_locker = '';
$active_admin = '';
$active_editor = '';
$active_observer = 'active';
$active_dash = '';
$page_title = "Observed Writs :: $siteTitle";
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

	// Proper user types
	if ( ($_SESSION['user_is_editor'] != true) && ($_SESSION['user_is_supervisor'] != true) && ($_SESSION['user_is_admin'] != true) && ($_SESSION['user_is_observer'] != true) ) {
		header("Location: " . PW99_HOME);
		exit(); // Quit the script
	} elseif ($_SESSION['user_is_admin'] == true) {
		$usr_type = "Admin";
	} elseif ($_SESSION['user_is_supervisor'] == true) {
		$usr_type = "Supervisor";
	} elseif ($_SESSION['user_is_editor'] == true) {
		$usr_type = "Editor";
	}	elseif ($_SESSION['user_is_observer'] == true) {
	 $usr_type = "Observer";
 	}

	// Dashboard
	$dashgreeting = "Observation for $u_name";
	include('./inserts/dash_observer.ins.php');

} else {
	header("Location: " . PW99_HOME);
	exit(); // Quit the script
}

// Heading
echo '<h2 class="lt">All Writs</h2>';

// Observees tables
$term_status = 'current';
$where_am_i = "writs_observer.php";
include('inserts/list_observees.ins.php');

// Include the footer file to complete the template
require('./includes/footer.html');
?>