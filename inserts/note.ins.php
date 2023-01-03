<?php

if (isset($_GET['v'])) {
	if (filter_var($_GET['v'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
		$note_id = preg_replace("/[^0-9]/","", $_GET['v']);
	} else {
		echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
		exit(); // Quit the script
	}
}

// New note?
if ( ($_SERVER['REQUEST_METHOD'] === 'POST') && (isset($_POST['new_note'])) && ($_POST['new_note'] == $userid) ) {
	$newnote = true;

// Save new note
} elseif ( ($_SERVER['REQUEST_METHOD'] === 'POST') && (isset($_POST['body'])) && (isset($_POST['new_note_save'])) && (!isset($note_id)) ) {
		// Sanitize the body
		$body = htmlspecialchars ($_POST['body']); $body = strip_tags($body);
		// Trim the body (Allow single-lines) // \s is any whitespace; [ ] is charclass for single space
		$body = trim(preg_replace("/[\r\n]{3,}/", "\n\n", $body)); // [\r\n]{3,} is three empty lines or more
		// SQL mysqli_real_escape_string
		$sql_body = mysqli_real_escape_string($dbc, $body);
		// Insert into database
		$q = "INSERT INTO notes (writer_id, body) VALUES ('$userid', '$sql_body')";
		$r = mysqli_query ($dbc, $q);
		if (mysqli_affected_rows($dbc) == 1) {
			// Get the last id INSERTed, similar to SCOPE_IDENTITY() but with MySQLi
			$note_id = $dbc->insert_id;
			// Done or Save?
			if ( (isset($_POST['done_note'])) && ($_POST['done_note'] == 'Done') ) {
				$_SESSION['done_note'] = $userid;
			} else {
				$_SESSION['saved'] = $userid;
				$_SESSION['opened_by'] = $userid;
			}
			echo '<script type="text/javascript"> window.location = "' . "note.php?v=$note_id" . '" </script>';
			exit();
		} else {
			echo '<span class="noticered sans">Database error, could not be saved.</span>';
		}

// Save edited note
} elseif ( ($_SERVER['REQUEST_METHOD'] == 'POST') && (isset($_POST['body'])) && (!isset($_POST['new_note'])) && (isset($note_id)) ) {
	// Sanitize the body
	$body = htmlspecialchars ($_POST['body']); $body = strip_tags($body);
	// Trim the body (Allow single-lines) // \s is any whitespace; [ ] is charclass for single space
	$body = trim(preg_replace('/[ ]+/', ' ', preg_replace("/[\r\n]{3,}/", "\n\n", $body))); // [\r\n]{3,} is three empty lines or more
	// SQL mysqli_real_escape_string
	$sql_body = mysqli_real_escape_string($dbc, $body);
	// Save note
	$q = "UPDATE notes SET body='$sql_body', save_date=NOW() WHERE writer_id='$userid' AND id='$note_id'";
	$r = mysqli_query ($dbc, $q);
	if ((mysqli_affected_rows($dbc) == 1) || ($r)) {
		// Done or Save?
		if ( (isset($_POST['done_note'])) && ($_POST['done_note'] == 'Done') ) {
			$_SESSION['done_note'] = $userid;
		} else {
			$_SESSION['saved'] = $userid;
			$_SESSION['opened_by'] = $userid;
		}
		echo '<script type="text/javascript"> window.location = "' . "note.php?v=$note_id" . '" </script>';
		exit();
	} else {
		echo '<span class="noticered sans">Database error, could not be saved.</span>';
	}

// Edit note
} elseif ( ($_SERVER['REQUEST_METHOD'] == 'POST') && ((isset($_POST['opened_by'])) && ($_POST['opened_by'] == $userid)) || ((isset($_SESSION['opened_by'])) && ($_SESSION['opened_by'] == $userid)) ) {
	if (isset($_SESSION['opened_by'])) {unset($_SESSION['opened_by']);}
	$editing = true;
// No new note, no save new note, no save edited note, no view note
} elseif (!isset($note_id)) {
	echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
	exit(); // Quit the script
}

if (isset($note_id)) {
	// Get the post info
	$q = "SELECT writer_id, body, save_date FROM notes WHERE writer_id='$userid' AND id='$note_id'";
	$r = mysqli_query ($dbc, $q);
	if (mysqli_num_rows($r) == 0) {
		echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
		exit(); // Quit the script
	}
	$row = mysqli_fetch_array($r);
	$writer_id = "$row[0]";
	$body = "$row[1]";
	$save_date = "$row[2]";
}

// Saved?
if ( (isset($_SESSION['saved'])) && ($_SESSION['saved'] == $userid) ) {
	unset($_SESSION['saved']);
	// New
	set_switch("New note +", "Start a new note", "note.php", "new_note", $writer_id, "newNoteButton");
	echo '<p class="noticegreen sans">Saved</p>';
	$editing = true;

// Done?
} elseif ( (isset($_SESSION['done_note'])) && ($_SESSION['done_note'] == $userid) ) {
	unset($_SESSION['done_note']);
	// New
	set_switch("New note +", "Start a new note", "note.php", "new_note", $writer_id, "newNoteButton");
	echo '<p class="noticegreen sans">Saved</p>';
}

// New / editing form
if ( (isset($editing)) || (isset($newnote)) ) {
	if (isset($newnote)) {

		// Form for new
		echo '<form id="editform" class="userform" action="note.php" method="post" accept-charset="utf-8" onsubmit="offNavWarn();">
		<input hidden name="new_note_save" value="true">';
	} elseif (isset($editing)) {

		// Delete button
		echo '<div style="display: inline; float:right;">';
		set_switch("Delete", "Delete this note", "delete_note.php", "deleted_note", $note_id, "editNoteButton");
		echo '</div>';

		// Form for editing
		echo '<form id="editform" class="userform" action="note.php?v='.$note_id.'" method="post" accept-charset="utf-8" onsubmit="offNavWarn();">
		<input hidden name="note_id" value="'.$note_id.'">
		<input hidden name="user_id" value="'.$userid.'">'; // AJAX will test these
	}
	// Note
	echo '
	<input type="submit" name="done_note" value="Done" id="done_note" class="lt_button" onclick="var f=this; setTimeout ( function() {f.disabled=true;}, 0 ); return true;" /> <span id="wordCount" class="wordCounter" ></span><br />
	<div id="result"><br /></div>
	<textarea name="body" id="writingArea" class="writingBox" rows="8" cols="82" placeholder="Note. First line is the title..." required onchange="onNavWarn();" onkeyup="onNavWarn();">';

	// Note body
	if (isset($body)) {
		echo $body;
	}

	// Finish the body and form
	//
	echo '</textarea>
	<br /><br />
	';

	//AJAX #save_note (editing existing)
	if (isset($editing)) {
		echo '
		<button type="button" title="Save (Ctrl + S)" onclick="ajaxFormData(\'editform\', \'note.ajax.php\', \'ajax_changes\'); offNavWarn();" name="save_note" id="save_note" class="lt_button small" style="display: inline;">Save</button>
		<div id="ajax_changes" style="display: inline;"></div>
		</form>';
		?>
			<script>
				function ajaxFormData(formID, postTo, ajaxUpdate) { // These arguments can be anything, same as used in this function
					// Bind a new event listener every time the <form> is changed:
					const FORM = document.getElementById( formID ); // <form> by ID to access, formID is the JS argument in the function
					const AJAX = new XMLHttpRequest(); // AJAX handler
					const FD = new FormData( FORM ); // Bind to-send data to form element

					AJAX.addEventListener( "load", function(event) { // This runs when AJAX responds
						document.getElementById(ajaxUpdate).innerHTML = event.target.responseText; // HTML element by ID to update, ajaxUpdate is the JS argument in the function
						offNavWarn(); // Turn off the nav away warning
					} );

					AJAX.addEventListener( "error", function(event) { // This runs if AJAX fails
						document.getElementById(ajaxUpdate).innerHTML =  'Oops! Something went wrong.';
					} );

					AJAX.open("POST", postTo); // Send data, postTo is the .php destination file, from the JS argument in the function

					AJAX.send(FD); // Data sent is from the form

				} // ajaxFormData() function

				// Ctrl + S = ajaxFormData();
				document.addEventListener("keydown", function(cs) {
				  if ( (window.navigator.platform.match("Mac") ? cs.metaKey : cs.ctrlKey) && (cs.keyCode == 83) ) {
				    cs.preventDefault(); // Stop it from doing what it normally does
				    ajaxFormData('editform', 'note.ajax.php', 'ajax_changes'); // Run our "Save" AJAX
				  }
				}, false); // Ctrl + S capture
			</script>
		<?php

	// Save (new note)
	} elseif (isset($newnote)) {
		echo '
		<input type="submit" name="save_note" id="save_note" value="Save" class="lt_button small" style="display: inline;" onclick="var f=this; setTimeout ( function() {f.disabled=true;}, 0 ); return true;">
		</form>';
		?>
			<script>
				// Ctrl + S = submit Save
				document.addEventListener("keydown", function(cs) {
					if ( (window.navigator.platform.match("Mac") ? cs.metaKey : cs.ctrlKey) && (cs.keyCode == 83) ) {
						cs.preventDefault(); // Stop it from doing what it normally does
						document.getElementById('save_note').click();
					}
				}, false); // Ctrl + S capture
			</script>
		<?php
	}

	// Navigate away warning
	?>
		<script>
			function onNavWarn() {
				window.onbeforeunload = function() {
				return true;
				};
			}
			function offNavWarn() {
				window.onbeforeunload = null;
			}
		</script>
	<?php

	// After the form
	echo '
	<br />

	<script src="js/jquery-1.7.1.min.js"></script>
	<script src="js/wordcount.js"></script>';

// View note
} else {
	$title = strtok($body, "\n"); // Get just the first line
	$mainBody = (strstr($body, "\n")) ? substr($body, strpos($body, "\n") + 1) : '';
	echo '<h1 class="view_note">'.$title.'</h1>';
	echo '<p class="view_note sans">'.nl2br($mainBody).'</p>';
	/*
	// Thanks: https://github.com/showdownjs/showdown, from: https://cdnjs.com/libraries/showdown (https://cdnjs.cloudflare.com/ajax/libs/showdown/1.9.1/showdown.min.js)
	//echo '<script src="js/showdown.min.js"></script>';
	echo '<script src="https://cdn.rawgit.com/showdownjs/showdown/1.9.1/dist/showdown.min.js"></script>';
	echo '<div class="view_note sans">';
	echo '<script type="text/javascript">
	var converter = new showdown.Converter(),
    text      = \''.$mainBody.'\',
    html      = converter.makeHtml(text);
		</script>';
	echo '</div>';
	*/
	if ($writer_id == $userid) {
		set_switch("Edit", "Edit this note", "note.php?v=$note_id", "opened_by", $userid, "editNoteButton");
	}
}
