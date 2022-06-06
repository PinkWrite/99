<?php

// Logged in or not?
if (!isset($_SESSION['user_id'])) {
	return;
}

// Okay to view this page
$userid = $_SESSION['user_id'];


// List notes
$sql_cols = 'id, body, save_date, editor_id, editor_set_block';
if (isset($editor_set_block)) {
	$sql_where = "editor_set_block='$editor_set_block'";
} elseif (isset($editor_set_writer_id)) {
  $sql_where = "editor_set_writer_id='$userid'";
} elseif ((isset($by_main_block)) && ($by_main_block == true)) { // Writer's Main block
	$q = "SELECT editor FROM users WHERE id='$userid'";
	$r = mysqli_query ($dbc, $q);
	$row = mysqli_fetch_array($r, MYSQLI_NUM);
	$u_editor = "$row[0]";
	$sql_where = "editor_id='$u_editor' AND editor_set_writer_id='0' AND editor_set_block='0'";
} else {
	// All notes from all non-Main blocks
	//JSON_HELP
	$sql_where = "EXISTS (SELECT 1 FROM users u WHERE JSON_CONTAINS(u.blocks, CONCAT('\"', n.editor_set_block, '\"')) AND u.id = '$userid')";
}
$q = "SELECT $sql_cols FROM notes n WHERE $sql_where ORDER BY save_date DESC LIMIT 10";
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
		$editor_set_block = "$row[3]";
		$editor_set_writer_id = "$row[4]";
		$title = strtok($body, "\n"); // Get just the first line

		echo '<tr class="'.$cc.'">';
		echo "<td><a class=\"listed_note\" href=\"note_view.php?v=$note_id\">$title</a></td>";

		// Writer note
		if ($editor_set_writer_id > 0) {
			$qwn = "SELECT name, email FROM users WHERE id='$editor_set_writer_id'";
			$rwn = mysqli_query ($dbc, $qwn);
			$rowwn = mysqli_fetch_array($rwn, MYSQLI_NUM);
			$w_name = "$rowwn[0]";
			$w_email = "$rowwn[1]";
			echo "<td>Writer: $w_name <small>$w_email</small></td>";
		// Block note
		} elseif ($editor_set_block > 0) {
			$qbn = "SELECT name, code FROM blocks WHERE id='$editor_set_block'";
			$rbn = mysqli_query ($dbc, $qbn);
			$rowbn = mysqli_fetch_array($rbn, MYSQLI_NUM);
			$b_name = "$rowbn[0]";
			$b_code = "$rowbn[1]";
			echo "<td>Block: $b_name <small>$b_code</small></td>";
		// Main block note
		} else {
			echo "<td>Block: Main</td>";
		}

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
