<?php

// Logged in or not?
if (!isset($_SESSION['user_id'])) {
	return;
}

// Okay to view this page
$userid = $_SESSION['user_id'];
if ( ($_SERVER['REQUEST_METHOD'] === 'POST') && (isset($_POST['deleted_note'])) && (filter_var($_POST['deleted_note'], FILTER_VALIDATE_INT, array('min_range' => 1))) ) {
	$note_id = preg_replace("/[^0-9]/","", $_POST['deleted_note']);

	// Get the post info
	$q = "SELECT writer_id, body, save_date FROM notes WHERE id='$note_id'";
	$r = mysqli_query ($dbc, $q);
	if (mysqli_num_rows($r) == 0) {
		echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
		exit(); // Quit the script
	}
	$row = mysqli_fetch_array($r);
	$writer_id = "$row[0]";
	$body = "$row[1]";
	$save_date = "$row[2]";
	$title = strtok($body, "\n"); // Get just the first line
	// Should we be here?
	if ($userid != $writer_id) {
		echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
		exit(); // Quit the script
	}

	// Delete varification form
	echo '<h2 class="lt">Delete: <i>"'.$title.'" (last saved '.$save_date.')</i></h2>
	<p class="sans">Are you sure you want to delete this?</p>
	<form id="editform" class="userform" action="delete_note.php" method="post" accept-charset="utf-8">
	<input type="hidden" name="yes_delete_note" value="'.$note_id.'" />
	<input type="checkbox" required />
	<input type="submit" name="delete_note" value="Yes, delete!" id="delete_note" class="dk_button" />
	</form>
	';
} elseif (($_SERVER['REQUEST_METHOD'] == 'POST') && (isset($_POST['yes_delete_note']))) {
	$note_id = $_POST['yes_delete_note'];
	// Delete from the database
	$q = "DELETE FROM notes WHERE id='$note_id'";
	$r = mysqli_query ($dbc, $q);
	if (mysqli_affected_rows($dbc) == 1) {
		set_switch("New note +", "Start a new note", "note.php", "new_note", $userid, "newNoteButton");
		echo '<h2 class="lt">Deleted!</h2>
		<p class="noticeorange sans">The note has been permanently deleted.</p>';

		// Check for $where_was_i
		set_button("&larr; Back to Notes", "Return to the Notes page", "notes.php", "newNoteButton");

	} else {
		echo "Database error!";
	}
} else {
	echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
	exit(); // Quit the script
}
