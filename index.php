<?php

// Require the configuration before any PHP code as the configuration controls error reporting
require('./pw99-config.php');

// A settings page requires form functions
require_once('./includes/form_functions.inc.php');


// If it's a POST request, handle the login attempt
if (($_SERVER['REQUEST_METHOD'] == 'POST') && (isset($_SESSION['username'])) && (isset($_SESSION['pass']))) {
	include('./includes/login.inc.php');
}

// Logged in or not?
if (isset($_SESSION['user_id'])) {
	// Okay to view this page
	$userid = $_SESSION['user_id'];
	$q = "SELECT name, username, email, blocks, level, editor, type FROM users WHERE id='$userid'";
	$r = mysqli_query ($dbc, $q);
	$row = mysqli_fetch_array($r, MYSQLI_NUM);
	$u_name = "$row[0]";
	$u_usrn = "$row[1]";
	$u_email = "$row[2]";
	$u_class = "$row[3]";
	$u_level = "$row[4]";
	$u_editor = "$row[5]";
	$u_type = "$row[6]";
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

	// Include the header file
	$active_writs = '';
	$active_blocks = '';
	$active_notes = '';
	$active_binder = '';
	$active_locker = '';
	$active_admin = '';
	$active_editor = '';
 	$active_observer = '';
 	$active_dash = 'activedash';
	$page_title = "$siteTitle";
	include('./includes/header.html');

	// Dashboard
	$dashgreeting = "Dash for $u_name";
	include('./inserts/dash.ins.php');

} else {
	echo '<table style="clear: both; float: left; display: block; position: relative; width: auto;" class="plain"><tbody><tr><td><span class="sans dk"><a href="88">Typing practice: 88 Word Hanon</a></span></td><td><span class="sans dk"><a href="https://github.com/PinkWrite/99">GitHub Source</a></span></td><td><span class="sans dk"><a href="in">Login</a></span></td></tr></tbody></table>
	<h1 style="clear: both; display: block;">'.$siteTitle.'</h1>
	<p class="dk sans"><b>Typing and Editing for Learners and Teachers</b>, <a href="https://pinkwrite.com"><small><i>powered by PinkWrite 99</i></small></a></p>';
	// Typing sheet
	include('inserts/type_file.ins.php');
}

// Pinned Notes
include('inserts/list_pins.ins.php');

// Editor Notes
$editor_set_writer_id = $userid;
include('inserts/list_notes_editor_10.ins.php');
unset($editor_set_writer_id);
include('inserts/list_notes_editor_10.ins.php');
echo '<br>';
set_button("All editor notes", "View all notes from your editor and blocks", "binder.php", "editNoteButton");

// We need space
echo '<br><br>';

// Writ table
$term_status = 'current';
$where_am_i = "index.php";
include('inserts/view_writs.ins.php');

// Include the footer file to complete the template
require('./includes/footer.html');
?>
