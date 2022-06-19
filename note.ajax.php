<?php

// AJAX only works when editing an existing Note

// Require the configuration before any PHP code as the configuration controls error reporting
require('./pw99-config.php');


// Proper POST?
if ( ($_SERVER['REQUEST_METHOD'] === 'POST') && (isset($_POST['user_id'])) && (isset($_POST['note_id'])) ) {

	// Logged in or not?
	if ( (isset($_SESSION['user_id'])) && ($_POST['user_id'] == $_SESSION['user_id']) ) {
		// Okay to view this page
		$userid = $_SESSION['user_id'];
	} else {
		exit(); // Quit the script
	}

		// Note ID
	if (filter_var($_POST['note_id'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
		$note_id = $_POST['note_id'];
	} else {
		exit(); // Quit the script
	}
	// Sanitize the body
	$body = htmlspecialchars ($_POST['body']); $body = strip_tags($body);
	// Trim the body (Allow single-lines) // \s is any whitespace; [ ] is charclass for single space
	$body = trim(preg_replace("/[\r\n]{3,}/", "\n\n", $body)); // [\r\n]{3,} is three empty lines or more
	// SQL mysqli_real_escape_string
	$sql_body = mysqli_real_escape_string($dbc, $body);
	// Editor settings
	if ((isset($_POST['editor_set_block'])) || (isset($_POST['editor_set_writer_id']))) {
		$sql_editor_set_block = (isset($_POST['editor_set_block'])) ? mysqli_real_escape_string($dbc, $_POST['editor_set_block']) : 0;
		$sql_editor_set_writer_id = (isset($_POST['editor_set_writer_id'])) ? mysqli_real_escape_string($dbc, $_POST['editor_set_writer_id']) : 0;
		$who_sql_cols = ", editor_set_writer_id='$sql_editor_set_writer_id', editor_set_block='$sql_editor_set_block' WHERE editor_id='$userid'";
	} else {
		$who_sql_cols = "WHERE writer_id='$userid'";
	}
	// Save note
	$q = "UPDATE notes SET body='$sql_body', save_date=NOW() $who_sql_cols AND id='$note_id'";
	$r = mysqli_query ($dbc, $q);
	if ($r) {
		// echo AJAX response
		echo '<span class="noticegreen noticehide small sans">Saved</span>';
		exit();
	} else {
		echo '<span class="noticered sans">Database error, could not be saved.</span>';
		exit();
	}
} else {
	header("Location: " . PW99_HOME);
	exit(); // Quit the script
}
