<?php

// AJAX only works when editing an existing Writ

// Require the configuration before any PHP code as the configuration controls error reporting
require('./pw99-config.php');



// Editor revision & Scoring form submission
if ( ($_SERVER['REQUEST_METHOD'] == 'POST') && (isset($_POST['reviewed_writer_id'])) && (isset($_POST['writ_id'])) ) {

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

	// Get the user's info
	$q = "SELECT status FROM users WHERE id='$userid'";
	$r = mysqli_query ($dbc, $q);
	$row = mysqli_fetch_array($r, MYSQLI_NUM);
	$u_status = "$row[0]";

	// Only active writers
	if (($u_status != "active") || (($_SESSION['user_is_editor'] != true) && ($_SESSION['user_is_supervisor'] != true) && ($_SESSION['user_is_admin'] != true))) {
		header("Location: " . PW99_HOME);
		exit(); // Quit the script
	}

	$writer_id = $_POST['reviewed_writer_id'];
	$block_id = (isset($_POST['block'])) ? filter_var($_POST['block'], FILTER_VALIDATE_INT, array('min_range' => 1)) : NULL;
	$title = (isset($_POST['title'])) ? strip_tags(htmlspecialchars(substr($_POST['title'],0,122))) : NULL;
	//$draft = (isset($_POST['draft'])) ? strip_tags(htmlspecialchars($_POST['draft'])) : NULL;
	$draft_status = (isset($_POST['draft_status'])) ? strip_tags(htmlspecialchars($_POST['draft_status'])) : NULL;
	$notes = (isset($_POST['notes'])) ? strip_tags(htmlspecialchars($_POST['notes'])) : NULL;
	$work = (isset($_POST['work'])) ? strip_tags(htmlspecialchars(substr($_POST['work'],0,122))) : NULL;
	$edits = (isset($_POST['edits'])) ? strip_tags(htmlspecialchars($_POST['edits'])) : NULL;
	$edit_notes = (isset($_POST['edit_notes'])) ? strip_tags(htmlspecialchars($_POST['edit_notes'])) : NULL;
	$edits_status = (isset($_POST['edits_status'])) ? strip_tags(htmlspecialchars($_POST['edits_status'])) : NULL;
	//$correction = (isset($_POST['correction'])) ? strip_tags(htmlspecialchars($_POST['correction'])) : NULL;
	$scoring = (isset($_POST['scoring'])) ? strip_tags(htmlspecialchars($_POST['scoring'])) : NULL;
	$score = (isset($_POST['score'])) ? filter_var($_POST['score'], FILTER_VALIDATE_INT, array('min_range' => 0, 'max_range' => 1000)) : NULL;
	$outof = ( (isset($_POST['outof'])) && ($_POST['outof'] != '') && ($_POST['outof'] != NULL) ) ? filter_var($_POST['outof'], FILTER_VALIDATE_INT, array('min_range' => 0, 'max_range' => 1000)) : 100;

	$title = trim(preg_replace('/\s+/', ' ', $title));
	$work = trim(preg_replace('/\s+/', ' ', $work));
	$notes = trim(preg_replace("/[\r\n]{3,}/", "\n\n", $notes)); // [\r\n]{3,} is three empty lines or more
	$edit_notes = trim(preg_replace("/[\r\n]{3,}/", "\n\n", $edit_notes));
	$scoring = trim(preg_replace('/[ ]+/', ' ', preg_replace("/[\r\n]{3,}/", "\n\n", $scoring)));
	$edits = trim(str_replace("\n", "\n\n", preg_replace('/[ ]+/', ' ', preg_replace("/[\r\n]+/", "\n", $edits))));

	$sql_block_id = mysqli_real_escape_string($dbc, $block_id);
	$sql_title = mysqli_real_escape_string($dbc, $title);
	//$sql_draft = mysqli_real_escape_string($dbc, $draft);
	$sql_notes = mysqli_real_escape_string($dbc, $notes);
	$sql_work = mysqli_real_escape_string($dbc, $work);
	$sql_edits = mysqli_real_escape_string($dbc, $edits);
	$sql_edit_notes = mysqli_real_escape_string($dbc, $edit_notes);
	//$sql_correction = mysqli_real_escape_string($dbc, $correction);
	$sql_scoring = mysqli_real_escape_string($dbc, $scoring);
	$sql_score = mysqli_real_escape_string($dbc, $score);
	$sql_outof = mysqli_real_escape_string($dbc, $outof);

		// Save edits
		if (isset($_POST['save_edit'])) {
			if ( ($score == '') || ($score == NULL) ) {
				$q = "UPDATE writs SET block='$sql_block_id', title='$sql_title', work='$sql_work', notes='$sql_notes', edits='$sql_edits', edit_notes='$sql_edit_notes', scoring='$sql_scoring', score=NULL, outof='$sql_outof' WHERE writer_id='$writer_id' AND id='$writ_id'";
			} else {
				$q = "UPDATE writs SET block='$sql_block_id', title='$sql_title', work='$sql_work', notes='$sql_notes', edits='$sql_edits', edit_notes='$sql_edit_notes', scoring='$sql_scoring', score='$sql_score', outof='$sql_outof' WHERE writer_id='$writer_id' AND id='$writ_id'";
			}
			$r = mysqli_query ($dbc, $q);
			if ($r) {
				echo "<span class=\"noticegreen noticehide sans\">Editor revision saved, not finalized.</span>";
			} else {
				echo "<span class=\"noticered sans\">Database error, edits could not be saved.</span>";
			}

		// Save score
		} elseif (isset($_POST['save_scoring'])) {
			if ( ($score == '') || ($score == NULL) ) {
				$q = "UPDATE writs SET block='$sql_block_id', title='$sql_title', work='$sql_work', notes='$sql_notes', scoring='$sql_scoring', score=NULL, outof='$sql_outof' WHERE writer_id='$writer_id' AND id='$writ_id'";
			} else {
				$q = "UPDATE writs SET block='$sql_block_id', title='$sql_title', work='$sql_work', notes='$sql_notes', scoring='$sql_scoring', score='$sql_score', outof='$sql_outof' WHERE writer_id='$writer_id' AND id='$writ_id'";
			}
				$r = mysqli_query ($dbc, $q);
				if ($r) {
					echo "<span class=\"noticegreen noticehide sans\">Scoring saved, not finalized.</span>";
				} else {
					echo "<span class=\"noticered sans\">Database error, score could not be saved.</span>";
				}

		} // End Save

} else {
	header("Location: " . PW99_HOME);
	exit(); // Quit the script
}
