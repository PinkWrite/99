<?php

// AJAX only works when editing an existing Writ

// Require the configuration before any PHP code as the configuration controls error reporting
require('./pw99-config.php');


// Form submission
if ( ($_SERVER['REQUEST_METHOD'] == 'POST') && (isset($_POST['user_form'])) && (isset($_POST['writ_id'])) ) {

	// Logged in or not?
	if (isset($_SESSION['user_id'])) {
		// Okay to view this page
		$userid = $_SESSION['user_id'];
	} else {
		exit(); // Quit the script
	}

	if (filter_var($_POST['writ_id'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
		$writ_id = $_POST['writ_id'];
	} else {
		exit(); // Quit the script
	}

	$block_id = (isset($_POST['block'])) ? filter_var($_POST['block'], FILTER_VALIDATE_INT, array('min_range' => 1)) : NULL;
	$title = (isset($_POST['title'])) ? strip_tags(htmlspecialchars(substr($_POST['title'],0,122))) : NULL;
	$draft = (isset($_POST['draft'])) ? strip_tags(htmlspecialchars($_POST['draft'])) : NULL;
	$notes = (isset($_POST['notes'])) ? strip_tags(htmlspecialchars($_POST['notes'])) : NULL;
	$work = (isset($_POST['work'])) ? strip_tags(htmlspecialchars(substr($_POST['work'],0,122))) : NULL;
	//$edits = (isset($_POST['edits'])) ? strip_tags(htmlspecialchars($_POST['edits'])) : NULL;
	$correction = (isset($_POST['correction'])) ? strip_tags(htmlspecialchars($_POST['correction'])) : NULL;

	// Trim extra space
	$title = trim(preg_replace('/\s+/', ' ', $title));
	$work = trim(preg_replace('/\s+/', ' ', $work));
	$notes = trim(preg_replace("/[\r\n]{3,}/", "\n\n", $notes)); // [\r\n]{3,} is three empty lines or more
	$draft = trim(str_replace("\n", "\n\n", preg_replace('/[ ]+/', ' ', preg_replace("/[\r\n]+/", "\n", $draft)))); // \s is any whitespace; [ ] is charclass for single space
	$correction = trim(str_replace("\n", "\n\n", preg_replace('/[ ]+/', ' ', preg_replace("/[\r\n]+/", "\n", $correction))));

	// SQL mysqli_real_escape_string
	$sql_block_id = mysqli_real_escape_string($dbc, $block_id);
	$sql_title = mysqli_real_escape_string($dbc, $title);
	$sql_draft = mysqli_real_escape_string($dbc, $draft);
	$sql_notes = mysqli_real_escape_string($dbc, $notes);
	$sql_work = mysqli_real_escape_string($dbc, $work);
	$sql_edits = mysqli_real_escape_string($dbc, $edits);
	$sql_correction = mysqli_real_escape_string($dbc, $correction);

	// Saving a draft
	if (isset($_POST['save_draft'])) {
		$q = "UPDATE writs SET title='$sql_title', block='$sql_block_id', work='$sql_work', notes='$sql_notes', draft='$sql_draft', draft_status='saved', draft_save_date=NOW() WHERE writer_id='$userid' AND id='$writ_id'";
		$r = mysqli_query ($dbc, $q);
		if ($r) {
			echo '<span class="noticegreen noticehide sans">Saved</span>';
			exit();
		} else {
			echo '<span class="sans noticered">Database error, could not be saved.</span>';
		}

		// Saving a correction
	} elseif (isset($_POST['save_correction'])) {
			// Continued edit
			$q = "UPDATE writs SET block='$sql_block_id', notes='$sql_notes', correction='$sql_correction', edits_status='saved', corrected_save_date=NOW() WHERE writer_id='$userid' AND id='$writ_id'";
			$r = mysqli_query ($dbc, $q);
			if ($r) {
				echo '<span class="noticegreen noticehide sans">Saved</span>';
				exit();
			} else {
				echo '<span class="sans noticered">Database error, could not be saved.</span>';
			}
	}
} else {
	header("Location: " . PW99_HOME);
	exit(); // Quit the script
}
