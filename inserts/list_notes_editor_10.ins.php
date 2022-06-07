<?php

// When including, these can be set, but this will NOT detect them from GET
// $editor_set_writer_id = GET w
// $editor_set_block = GET b
// $by_main_block = GET m
// $by_user_all = GET u

// $limit_rows

// Logged in or not?
if (!isset($_SESSION['user_id'])) {
	return;
}

// Okay to view this page
$userid = $_SESSION['user_id'];

$limit_rows = (isset($limit_rows)) ? $limit_rows : 10 ;

// List notes
$sql_cols = 'n.id, n.body, n.save_date, n.editor_set_writer_id, n.editor_set_block';
if (isset($editor_set_block)) {
	// Make sure we should be here
	if ($usr_type == "Writer") {
		$q = "SELECT id FROM users WHERE JSON_CONTAINS(blocks, CONCAT('\"', $editor_set_block, '\"')) AND id='$userid'";
		$r = mysqli_query ($dbc, $q);
		if (mysqli_num_rows($r) == 0) {
			unset($editor_set_block);
		}
	} elseif ($usr_type == "Observer") {
		$q = "SELECT observing FROM users WHERE id='$userid'";
		$r = mysqli_query ($dbc, $q);
		$row = mysqli_fetch_array($r, MYSQLI_NUM);
		$observing_array = json_decode($rowo[0], true);
		$observes_block = false; // Preset for our test
		foreach ($observing_array as $u_id) {
			$q = "SELECT id FROM users WHERE JSON_CONTAINS(blocks, CONCAT('\"', $editor_set_block, '\"')) AND id='$u_id'";
			$r = mysqli_query ($dbc, $q);
			if (mysqli_num_rows($r) == 1) {
				$observes_block = true;
			}
		}
		if ($observes_block != true) {
			unset($editor_set_block);
		}
	}
	// Payload
	$sql_where = "n.editor_set_block='$editor_set_block'";
} elseif (isset($editor_set_writer_id)) {
	// Make sure we should be here
	if ($usr_type == "Writer") {
		if ($editor_set_writer_id != $userid) {
			unset($editor_set_writer_id);
		}
	} elseif ($usr_type == "Observer") {
		$q = "SELECT id FROM users WHERE JSON_CONTAINS(observing, CONCAT('\"', $editor_set_writer_id, '\"')) AND id='$userid'";
		$r = mysqli_query ($dbc, $q);
		if (mysqli_num_rows($r) == 0) {
			unset($editor_set_writer_id);
		}
	}
	// Payload
  $sql_where = "n.editor_set_writer_id='$userid'";

} elseif (isset($by_main_block)) { // Writer's Main block
	// Make sure we should be here
	if ($usr_type == "Writer") {
		if ($by_main_block != $userid) {
			unset($by_main_block);
		}
	} elseif ($usr_type == "Observer") {
		$q = "SELECT id FROM users WHERE JSON_CONTAINS(observing, CONCAT('\"', $by_main_block, '\"')) AND id='$userid'";
		$r = mysqli_query ($dbc, $q);
		if (mysqli_num_rows($r) == 0) {
			unset($by_main_block);
		}
	}

	$q = "SELECT editor FROM users WHERE id='$by_main_block'";
	$r = mysqli_query ($dbc, $q);
	$row = mysqli_fetch_array($r, MYSQLI_NUM);
	$u_editor = "$row[0]";
	// Payload
	$sql_where = "n.editor_id='$u_editor' AND n.editor_set_writer_id='0' AND n.editor_set_block='0'";

} elseif (isset($by_user_all)) { // Writer's all blocks (including Main and personal)
	// Make sure we should be here
	if ($usr_type == "Writer") {
		if ($by_user_all != $userid) {
			unset($by_user_all);
			echo '<h2 class="sans dk">Editor notes <small>(all)</small></h2>';
		}
	} elseif ($usr_type == "Observer") {
		$q = "SELECT id FROM users WHERE JSON_CONTAINS(observing, CONCAT('\"', $by_user_all, '\"')) AND id='$userid'";
		$r = mysqli_query ($dbc, $q);
		if (mysqli_num_rows($r) == 0) {
			unset($by_user_all);
			echo '<h2 class="sans dk">Editor notes <small>(all)</small></h2>';
		}
	}
	// Payload
	$sql_where = "EXISTS (SELECT 1 FROM users u WHERE JSON_CONTAINS(u.blocks, CONCAT('\"', n.editor_set_block, '\"')) AND u.id = '$by_user_all') AND n.editor_set_writer_id='0' OR (n.editor_set_writer_id='0' AND n.editor_set_block='0' AND n.writer_id='0') OR n.editor_set_writer_id='$by_user_all'";

} else {
	$by_user_all = $userid;
	// Payload
	$sql_where = "EXISTS (SELECT 1 FROM users u WHERE JSON_CONTAINS(u.blocks, CONCAT('\"', n.editor_set_block, '\"')) AND u.id = '$by_user_all') AND n.editor_set_writer_id='0' OR (n.editor_set_writer_id='0' AND n.editor_set_block='0' AND n.writer_id='0')";
}
$q = "SELECT $sql_cols FROM notes n WHERE $sql_where ORDER BY save_date DESC LIMIT $limit_rows";
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
		$editor_set_writer_id = "$row[3]";
		$editor_set_block = "$row[4]";
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
		get_switch("Read", "Read this note", "note_view.php", "v", "$note_id", "act_blue editNoteButton");
		echo '</div>
			</td>
		</tr>';

		// Rotate our row color class
		$cc = ($cc == 'lr') ? 'dr' : 'lr';
	}
	echo '</tbody></table>';
}

// We don't want this messing with other stuff
unset($editor_id);
unset($editor_set_writer_id);
unset($editor_set_block);
unset($by_main_block);
unset($by_user_all);
