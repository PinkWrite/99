<?php

// Logged in or not?
if (!isset($_SESSION['user_id'])) {
	return;
}

// Okay to view this page
$userid = $_SESSION['user_id'];


// List notes
$sql_cols = 'id, body, save_date';
if (isset($editor_set_block)) {
	$sql_where = "editor_set_block='$editor_set_block'";
} elseif (isset($editor_set_writer_id)) {
  $sql_where = "editor_set_writer_id='$userid'";
} else { // Writer's Main block
	$q = "SELECT editor FROM users WHERE id='$userid'";
	$r = mysqli_query ($dbc, $q);
	$row = mysqli_fetch_array($r, MYSQLI_NUM);
	$u_editor = "$row[0]";
	$sql_where = "editor_id='$u_editor' AND editor_set_writer_id='0' AND editor_set_block='0'";
}
$q = "SELECT $sql_cols FROM notes WHERE $sql_where ORDER BY save_date DESC LIMIT 10";
$r = mysqli_query ($dbc, $q);

// Start our row color class
$cc = 'lr';

// If has rows
if (mysqli_num_rows($r) > 0) {

	// Head message only once
	echo (!isset($has_editor_notes)) ? '<h4>Editor notes</h4>' : false;
	$has_editor_notes = true; // Because we incllude this same file multiple times

	// Start the table
	echo '
	<table class="list lt notes sans"><tbody>';

	// Iterate each entry
	while ($row = mysqli_fetch_array($r)) {
		$note_id = "$row[0]";
		$body = "$row[1]";
		$save_date = "$row[2]";
		$title = strtok($body, "\n"); // Get just the first line

		echo '<tr class="'.$cc.'">';
		echo "<td><a class=\"listed_note\" href=\"note.php?v=$note_id\">$title</a></td>";
		echo '<td><i class="listed_note">'.$save_date.'</i></td><td>
		<div style="display: inline; float:right;">';
		get_switch("Read", "Read this note", "note_editor.php", "v", "$note_id", "act_blue editNoteButton");
		echo '</div>
			</td>
		</tr>';

		// Rotate our row color class
		$cc = ($cc == 'lr') ? 'dr' : 'lr';
	}
	echo '</tbody></table>';
}
