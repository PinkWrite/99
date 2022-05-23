<?php

// Logged in or not?
if (!isset($_SESSION['user_id'])) {
	return;
}

// Okay to view this page
$userid = $_SESSION['user_id'];

// New
set_switch("New note +", "Start a new note", "note.php", "new_note", $userid, "newNoteButton");
echo '<br>';

// List pinned notes
$q = "SELECT id, body, save_date FROM notes WHERE pinned=true AND writer_id='$userid' ORDER BY id DESC LIMIT 10";
$r = mysqli_query ($dbc, $q);

// Empty?
if (mysqli_num_rows($r) > 0) {

	// Start our row color class
	$cc = 'lr';

	// Start the table
	echo '
	<table class="list lt notes sans">';

	// Iterate each entry
	while ($row = mysqli_fetch_array($r)) {
		$note_id = "$row[0]";
		$body = "$row[1]";
		$save_date = "$row[2]";
		$title = strtok($body, "\n"); // Get just the first line

		echo '<tr class="'.$cc.'">';
		echo "
			<td><a class=\"listed_note\" href=\"note.php?v=$note_id\">$title</a></td>";
			echo '<td><i class="listed_note">'.$save_date.'</i><div style="display: inline; float:right;">';
			set_switch("Read", "Read this note", "note.php?v=$note_id", "no_post_value", "no_post_value", "act_blue editNoteButton");
			echo '</div>
				</td>
				<td><div style="display: inline; float:right;">';
			set_switch("Unpin", "Unpin from Dashboard", "note.act.php", "undash", $note_id, "editNoteButton");
			echo '</div>
				</td>
				</tr>';

			// Rotate our row color class
			$cc = ($cc == 'lr') ? 'dr' : 'lr';
	}
	echo "
	</table>";
}
