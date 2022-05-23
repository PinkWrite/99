<?php

// Editing
if (isset($_GET['w'])) {
	if (filter_var($_GET['w'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
		$writ_id = preg_replace("/[^0-9]/","", $_GET['w']);
	} else {
		echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
		exit(); // Quit the script
	}

	// New switch
	set_switch("New writ +", "Start writing something new", "writ.php", "new_writ", $userid, "newNoteButton");

	// Writ information
	$q = "SELECT writer_id, block, work, title, notes, draft, draft_status, edits, edit_notes, correction, edits_status, scoring, score, outof FROM writs WHERE id='$writ_id'";
	$r = mysqli_query ($dbc, $q);
	$row = mysqli_fetch_array($r, MYSQLI_NUM);
	$writer_id = "$row[0]";
	$block_id = "$row[1]";
	$work = "$row[2]";
	$title = "$row[3]";
	$notes = "$row[4]";
	$draft = "$row[5]";
	$draft_status = "$row[6]";
	$edits = "$row[7]";
	$edit_notes = "$row[8]";
	$correction = "$row[9]";
	$edits_status = "$row[10]";
	$scoring = "$row[11]";
	$score = "$row[12]";
	$outof = "$row[13]";

	// BLock
	if ($block_id != 0) {
		$qb = "SELECT name, code FROM blocks WHERE id='$block_id'";
		$rb = mysqli_query ($dbc, $qb);
		$rowb = mysqli_fetch_array($rb);
		$block_name = "$rowb[0]";
		$block_code = "$rowb[1]";
		$block_listing = '<small title="'.$block_name.'">'.$block_code.'</small>';
	} else {
		$block_listing = 'Main';
	}

	// Quit if the user does not own the Writ
	if ($userid != $writer_id) {
		echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
		exit(); // Quit the script
	}

	// Mark Editor revision as viewed
	if ( ($draft_status == 'reviewed') && ($edits_status == 'drafting') ) {
		$q = "UPDATE writs SET edits_status='viewed', edits_viewed_date=NOW() WHERE writer_id='$userid' AND id='$writ_id'";
		$r = mysqli_query ($dbc, $q);
		if (mysqli_affected_rows($dbc) != 1) {
			echo "Database error.";
		}
	}

	// Message if the correction was submitted
	if ($draft_status == 'submitted') {
		echo "<p class=\"sans noticegreen\">The draft for \"$title\" for \"$work\" has been submitted and is waiting for review.</p>";
		set_switch("New Writ +", "Start writing something new", "writ.php", "new_writ", $writer_id, "set_gray");
		echo '<br>';
		set_switch("Return to your Dashboard", "My Dashboard", "home", "no_post_name", "no_post_value", "navDarkButton user");
		return; // Quit the script
	} elseif ($edits_status == 'submitted') {
		echo "<p class=\"sans noticegreen\">The corrected revision \"$title\" for \"$work\" has been submitted and is waiting to be scored.</p>";
		set_switch("New Writ +", "Start writing something new", "writ.php", "new_writ", $writer_id, "set_gray");
		echo '<br>';
		set_switch("Return to your Dashboard", "My Dashboard", "home", "no_post_name", "no_post_value", "navDarkButton user");
		return; // Quit the script
	} elseif ( ($draft_status == 'reviewed') && ($edits_status == 'scored') ) {
		echo '<p class="sans">Block: '.$block_listing.'</p>
		<h3 class="lt">Work: '.$work.'<br />Title: '.$title.'</h3>
		<h4 class="lt">Score: '.$score.'<small class="dk">/'.$outof.'</small></h4>
		<h4>Final scoring remarks:</h4>
		<section class="writcontent remarks">'.nl2br($scoring).'</section>
		<h4>First draft:</h4>
		<section class="writcontent draft">'.nl2br($draft).'</section>
		<h4>Edited</h4>
		<h5>Remarks:</h5>
		<section class="writcontent remarks" id="edits">'.nl2br($edit_notes).'</section>
		<h5>Diff:</h5>
		<section class="writcontent diff" id="outputDif"></section>
		<h5>Editor revision:</h5>
		<section class="writcontent revision" id="edits">'.nl2br($edits).'</section>
		<h4>Final corrected revision:</h4>
		<section class="writcontent correction">'.nl2br($correction).'</section>
		<h4>Notes:</h4>
		<section class="writcontent notes">'.nl2br($notes).'</section>';
		// HTMLdiff
		echo '
		<script src="js/htmldiff.min.js"></script>
		<script>
		let oldHTML = `'.nl2br($draft).'`;
		let curHTML = `'.nl2br($edits).'`;
		let difHTML = htmldiff(oldHTML, curHTML);
		document.getElementById("outputDif").innerHTML = difHTML;
		</script>
		';
		return; // Quit the script
	}

}

// Get the user's info
$q = "SELECT level, status, blocks FROM users WHERE id='$userid'";
$r = mysqli_query ($dbc, $q);
$row = mysqli_fetch_array($r, MYSQLI_NUM);
$level = "$row[0]";
$u_status = "$row[1]";
$u_blocks_array = json_decode($row[2], true);

// Only active writers
if ($u_status != "active") {
	echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
	exit(); // Quit the script
}

// Form submission
if ( ($_SERVER['REQUEST_METHOD'] == 'POST') && (isset($_POST['user_form'])) ) {
	/* Delete if everything works
	if (isset($_POST['block'])) { $block_id = preg_replace("/[^0-9]/","", $_POST['block']); } else { $block_id = NULL;}
	if (isset($_POST['title'])) { $title =  htmlspecialchars ($_POST['title']); $title = strip_tags($title); } else { $title = NULL;}
	if (isset($_POST['draft'])) { $draft =  htmlspecialchars ($_POST['draft']); $draft = strip_tags($draft); } else { $draft = NULL;}
	if (isset($_POST['notes'])) { $notes =  htmlspecialchars ($_POST['notes']); $notes = strip_tags($notes); } else { $notes = NULL;}
	if (isset($_POST['work'])) { $work =  htmlspecialchars ($_POST['work']); $work = strip_tags($work); } else { $work = NULL;}
	if (isset($_POST['edits'])) { $edits =  htmlspecialchars ($_POST['edits']);  $edits = strip_tags($edits); } else { $edits = NULL;}
	if (isset($_POST['correction'])) { $correction =  htmlspecialchars ($_POST['correction']); $correction = strip_tags($correction); } else { $correction = NULL;}
*/
	$block_id = (isset($_POST['block'])) ? preg_replace("/[^0-9]/","", $_POST['block']) : NULL;
	$title = (isset($_POST['title'])) ? strip_tags(htmlspecialchars(substr($_POST['title'],0,122))) : NULL;
	$draft = (isset($_POST['draft'])) ? strip_tags(htmlspecialchars($_POST['draft'])) : NULL;
	$notes = (isset($_POST['notes'])) ? strip_tags(htmlspecialchars($_POST['notes'])) : NULL;
	$work = (isset($_POST['work'])) ? strip_tags(htmlspecialchars(substr($_POST['work'],0,122))) : NULL;
	//$edits = (isset($_POST['edits'])) ? strip_tags(htmlspecialchars($_POST['edits'])) : NULL;
	$correction = (isset($_POST['correction'])) ? strip_tags(htmlspecialchars($_POST['correction'])) : NULL;

	// Trim extra space
	$title = trim(preg_replace('/\s+/', ' ', $title));
	$work = trim(preg_replace('/\s+/', ' ', $work));
	$notes = trim(preg_replace("/[\r\n]{2,}/", "\n\n", $notes)); // [\r\n]{2,} is three empty lines or more
	$draft = trim(preg_replace('/[ ]+/', ' ', preg_replace("/[\r\n]+/", "\n", $draft))); // \s is any whitespace; [ ] is charclass for single space
	$correction = trim(preg_replace('/[ ]+/', ' ', preg_replace("/[\r\n]+/", "\n", $correction)));

	// SQL mysqli_real_escape_string
	$sql_block_id = mysqli_real_escape_string($dbc, $block_id);
	$sql_title = mysqli_real_escape_string($dbc, $title);
	$sql_draft = mysqli_real_escape_string($dbc, $draft);
	$sql_notes = mysqli_real_escape_string($dbc, $notes);
	$sql_work = mysqli_real_escape_string($dbc, $work);
	//$sql_edits = mysqli_real_escape_string($dbc, $edits);
	$sql_correction = mysqli_real_escape_string($dbc, $correction);

	// BLock
	if ($block_id != 0) {
		$qb = "SELECT name, code FROM blocks WHERE id='$block_id'";
		$rb = mysqli_query ($dbc, $qb);
		$rowb = mysqli_fetch_array($rb);
		$block_name = "$rowb[0]";
		$block_code = "$rowb[1]";
		$block_listing = '<small title="'.$block_name.'">'.$block_code.'</small>';
	} else {
		$block_listing = 'Main';
	}

		// Submit a draft
		if (isset($_POST['submit_draft'])) {

		// New draft submission
		if (!isset($writ_id))	{
			$q = "INSERT INTO writs (writer_id, block, level, work, title, notes, draft, draft_status, draft_submit_date) VALUES ('$userid', '$sql_block_id', '$level', '$sql_work', '$sql_title', '$sql_notes', '$sql_draft', 'submitted', NOW())";
			$r = mysqli_query ($dbc, $q);
			if (mysqli_affected_rows($dbc) == 1) {
				$writ_id = $dbc->insert_id;
				echo '<script type="text/javascript"> window.location = "' . "writ.php?w=$writ_id" . '" </script>';
				exit();
			} else {
				echo '<p class="sans noticered">Database error, could not be submitted.</p>';
			}

		// Saved draft submission
		} elseif (isset($writ_id)) {
				$q = "UPDATE writs SET title='$sql_title', block='$sql_block_id', work='$sql_work', notes='$sql_notes', draft='$sql_draft', draft_status='submitted', draft_submit_date=NOW() WHERE writer_id='$userid' AND id='$writ_id'";
				$r = mysqli_query ($dbc, $q);
				if ($r) {
					echo '<script type="text/javascript"> window.location = "' . "writ.php?w=$writ_id" . '" </script>';
					exit();
				} else {
					echo '<p class="sans noticered">Database error, could not be submitted.</p>';
				}
			}

		// Saving a draft
		} elseif (isset($_POST['save_draft'])) {

			// New draft
			if (!isset($writ_id))	{
				$q = "INSERT INTO writs (writer_id, block, level, work, title, notes, draft, draft_status) VALUES ('$userid', '$sql_block_id', '$level', '$sql_work', '$sql_title', '$sql_notes', '$sql_draft', 'saved')";
				$r = mysqli_query ($dbc, $q);
				if (mysqli_affected_rows($dbc) == 1) {
					// Get the last id INSERTed, similar to SCOPE_IDENTITY() but with MySQLi
					$writ_id = $dbc->insert_id;
					echo '<script type="text/javascript"> window.location = "' . "writ.php?w=$writ_id" . '" </script>';
					exit();
				} else {
					echo '<p class="sans noticered">Database error, could not be saved.</p>';
				}

			// Continued draft
			} elseif (isset($writ_id)) {
				$q = "UPDATE writs SET title='$sql_title', block='$sql_block_id', work='$sql_work', notes='$sql_notes', draft='$sql_draft', draft_status='saved', draft_save_date=NOW() WHERE writer_id='$userid' AND id='$writ_id'";
				$r = mysqli_query ($dbc, $q);
				if ($r) {
					echo '<script type="text/javascript"> window.location = "' . "writ.php?w=$writ_id" . '" </script>';
					exit();
				} else {
					echo '<p class="sans noticered">Database error, could not be saved.</p>';
				}
			}

		} // End draft form

		// Submit a correction
		if (isset($_POST['submit_correction'])) {

			// Saved edit submission
				$q = "UPDATE writs SET block='$sql_block_id', notes='$sql_notes', correction='$sql_correction', edits_status='submitted', corrected_submit_date=NOW() WHERE writer_id='$userid' AND id='$writ_id'";
				$r = mysqli_query ($dbc, $q);
				if ($r) {
					echo '<script type="text/javascript"> window.location = "' . "writ.php?w=$writ_id" . '" </script>';
					exit();
				} else {
					echo '<p class="sans noticered">Database error, could not be submitted.</p>';
				}

		// Saving a correction
		} elseif (isset($_POST['save_correction'])) {

			// Continued edit
			$q = "UPDATE writs SET block='$sql_block_id', notes='$sql_notes', correction='$sql_correction', edits_status='saved', corrected_save_date=NOW() WHERE writer_id='$userid' AND id='$writ_id'";
			$r = mysqli_query ($dbc, $q);
			if ($r) {
				echo '<script type="text/javascript"> window.location = "' . "writ.php?w=$writ_id" . '" </script>';
				exit();
			} else {
				echo '<p class="sans noticered">Database error, could not be saved.</p>';
			}

		} // End edit form
} // End $_POST forms

// Created for specific block?
if ((isset($_GET['v'])) && (filter_var($_GET['v'], FILTER_VALIDATE_INT, array('min_range' => 0))) || ($_GET['v'] == '0')) {
	$block_id = preg_replace("/[^0-9]/","", $_GET['v']);
}

// Editing? (for AJAX)
$inputwritid = (isset($writ_id)) ?
'<input type="hidden" name="writ_id" value="'.$writ_id.'">' :
'';

// Double-check for "Submit"
?>
<script>
function showSubmit(showID) {
  var x = document.getElementById(showID);
  if (x.style.display === "inline") {
    x.style.display = "none";
  } else {
    x.style.display = "inline";
  }
}
</script>
<?php

// Draft form
if ( (!isset($writ_id)) || ($draft_status == 'saved') ) {
	echo '
	<form id="editform" class="userform" ';
	if (isset($writ_id)) {
		echo 'action="writ.php?w='.$writ_id.'"';
	} else {
		echo 'action="writ.php"';
	}
	echo ' method="post" accept-charset="utf-8" onsubmit="offNavWarn();">
	<input type="hidden" name="user_form" value="'.$userid.'" />'
	.$inputwritid;

	// Block
	if ( (!isset($writ_id)) || ($edits_status == 'drafting') ||  ( ($_SESSION['user_is_editor'] == true) || ($_SESSION['user_is_supervisor'] == true) || ($_SESSION['user_is_admin'] == true) ) ) {
		echo '<p><label class="sans" for="block">Block:</label>
		<select class="formselect small" name="block" id="block" onchange="onNavWarn();" onkeyup="onNavWarn();">
			<option value="0" hidden>Choose...</option>
			<option value="0"';

			if ( (isset($block_id)) && ($block_id == 0) ) {
				echo ' selected';
			}
			echo '>Main</option>';

		// List available blocks
		foreach ($u_blocks_array as $b_id) {
			// [""] could end up being the value, so see if this item is empty
			if (!filter_var($b_id, FILTER_VALIDATE_INT, array('min_range' => 1))) { continue; }

			$qb = "SELECT id, name, code FROM blocks WHERE status='open' AND id='$b_id'";
			$rb = mysqli_query ($dbc, $qb);
			$rowb = mysqli_fetch_array($rb);
			$block_id_form = "$rowb[0]";
			$block_name_form = "$rowb[1]";
			$block_code_form = "$rowb[2]";

			echo '<option value="'.$block_id_form.'"';

			if ( (isset($block_id)) && ($block_id == $block_id_form) ) {
				echo ' selected';
			}
			echo '>'.$block_name_form.' (<small>'.$block_code_form.'</small>)</option>';
		}
		echo '</select></label></p>';
	} elseif ($block_id != 0) {
		echo '<p class="lt sans">Block: <b>'.$block_name_form.'</b> (<small>'.$block_code_form.'</small>)</p>';
	} // End Block

	// Main fields
	echo '
	<label class ="sans" for="work">Work</label><br /><br />
	<input type="text" name="work" id="work" class="readBox" onchange="onNavWarn();" onkeyup="onNavWarn();" maxlength="122" required';

	// Work value
	if (isset($work)) {
		echo ' value="'.$work.'" />';
	} else {
	echo ' placeholder="Work" />';
	}

	echo '
	<br />
	<br />
	<input type="text" name="title" id="title" class="writingBox" onchange="onNavWarn();" onkeyup="onNavWarn();" maxlength="122" required';

	// Title value
	if (isset($title)) {
		echo ' value="'.$title.'" />';
	} else {
	echo ' placeholder="Title" />';
	}

	echo '<br /><br />';

	if (isset($writ_id)) {
		echo '
		<button type="button" title="Save (Ctrl + S)" class="lt_button" onclick="ajaxFormData(\'editform\', \'writ.ajax.php\', \'ajax_changes\'); offNavWarn();">Save</button>
		&nbsp;<span id="wordCount" class="wordCounter" ></span>
		<div id="ajax_changes" style="display: inline;"></div><br />
		<input type="hidden" name="save_draft" value="Save" id="save_draft" class="lt_button" /><br />';
	} else {
		echo '
		<input type="submit" name="save_draft" value="Save" id="save_draft" class="lt_button" onclick="var f=this; setTimeout ( function() {f.disabled=true;}, 0 ); return true;" /> <span id="wordCount" class="wordCounter" ></span><br />
		<br />';
	}
	echo '
	<textarea name="draft" id="writingArea" class="writingBox" onchange="onNavWarn();" onkeyup="onNavWarn();" rows="8" cols="82" onPaste="return false" onCut="return false" onDrag="return false" onDrop="return false" autocomplete=off placeholder="Draft contents...">';

	// Draft value
	if (isset($draft)) {
		echo $draft;
	}

	echo '</textarea>
	<br />
	<br />
	<textarea name="notes" class="readBox" onchange="onNavWarn();" onkeyup="onNavWarn();" rows="2" cols="82" placeholder="Notes...">';

	// Notes value
	if (isset($notes)) {
		echo $notes;
	}

	echo '</textarea>
	<br /><br />

	<button onclick="showSubmit(\'submit_draft\');" type="button" class="dk_sub_button">Submit draft</button> <!-- type="button" so it will not submit the form -->
	&nbsp;&nbsp;
	<input type="submit" name="submit_draft" value="Confirm" id="submit_draft" class="ln_button" style="display:none;" />';

	// Finish the form
	echo '
	</form>
	<br />

	<script src="js/jquery-1.7.1.min.js"></script>
	<script src="js/wordcount.js"></script>
	';

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

	// AJAX #save_draft
	if (isset($writ_id)) {
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
				    ajaxFormData('editform', 'writ.ajax.php', 'ajax_changes'); // Run our "Save" AJAX
				  }
				}, false); // Ctrl + S capture
			</script>
		<?php
	} else {
		?>
			<script>
				// Ctrl + S = submit Save
				document.addEventListener("keydown", function(cs) {
					if ( (window.navigator.platform.match("Mac") ? cs.metaKey : cs.ctrlKey) && (cs.keyCode == 83) ) {
						cs.preventDefault(); // Stop it from doing what it normally does
						document.getElementById('save_draft').click();
					}
				}, false); // Ctrl + S capture
			</script>
		<?php
	}

	return;


// Correction form
} elseif ( (isset($writ_id)) && ($draft_status == 'reviewed') ) {

	echo '
	<form id="editform" class="userform" action="writ.php?w='.$writ_id.'" method="post" accept-charset="utf-8" onsubmit="offNavWarn();">
	<input type="hidden" name="user_form" value="'.$userid.'" />'
	.$inputwritid;

	// Block
	if ( (!isset($writ_id)) || ($edits_status == 'drafting') ||  ( ($_SESSION['user_is_editor'] == true) || ($_SESSION['user_is_supervisor'] == true) || ($_SESSION['user_is_admin'] == true) ) ) {
		echo '<p><label class="sans" for="block">Block:</label>
		<select class="formselect small" name="block" id="block" onchange="onNavWarn();" onkeyup="onNavWarn();">
			<option value="0" hidden>Choose...</option>
			<option value="0"';

			if ( (isset($block_id)) && ($block_id == 0) ) {
				echo ' selected';
			}
			echo '>Main</option>';

		// List available blocks
		foreach ($u_blocks_array as $b_id) {
			$qb = "SELECT id, name, code FROM blocks WHERE status='open' AND id='$b_id'";
			$rb = mysqli_query ($dbc, $qb);
			$rowb = mysqli_fetch_array($rb);
			$block_id_form = "$rowb[0]";
			$block_name_form = "$rowb[1]";
			$block_code_form = "$rowb[2]";
			echo '<option value="'.$block_id_form.'"';

			if ( (isset($block_id)) && ($block_id == $block_id_form) ) {
				echo ' selected';
			}
			echo '>'.$block_name_form.' (<small>'.$block_code_form.'</small>)</option>';
		}
		echo '</select></label></p>';
	} elseif ( (isset($block_id)) && ($block_id != 0) ) {
		echo '<p class="lt sans">Block: <b>'.$block_name.'</b> (<small>'.$block_code.'</small>)</p>
		<input type="hidden" name="block" value="'.$block_id.'">';
	} elseif ($block_id == 0) {
		echo '<p class="lt sans">Block: <b>Main</b></p>
		<input type="hidden" name="block" value="0">';
	} // End Block

	// Main fields
	echo '<h3 class="lt">Work: '.$work.'<br />Title: '.$title.'</h3>
	<h4>Draft:</h4>
	<section class="writcontent draft" id="draft">'.nl2br($draft).'</section>
	<hr />
	<h4>Edited</h4>
	<h5>Remarks:</h5>
	<section class="writcontent remarks" id="edits">'.nl2br($edit_notes).'</section>
	<h5>Diff:</h5>
	<section class="writcontent diff" id="outputDif"></section>
	<h5>Editor revision:</h5>
	<section class="writcontent revision" id="edits">'.nl2br($edits).'</section>
	<br />
	<button type="button" title="Save (Ctrl + S)" class="lt_button" onclick="ajaxFormData(\'editform\', \'writ.ajax.php\', \'ajax_changes\'); offNavWarn();">Save</button>
	&nbsp;<span id="wordCount" class="wordCounter" ></span>
	<div id="ajax_changes" style="display: inline;"></div><br />
	<input type="hidden" name="save_correction" value="Save" id="save_correction" class="lt_button" /><br />
	<textarea name="correction" id="writingArea" class="writingBox" onchange="onNavWarn();" onkeyup="onNavWarn();" rows="8" cols="82" onPaste="return false" onCut="return false" onDrag="return false" onDrop="return false" autocomplete=off placeholder="Edited contents...">';

	// Draft value
	if (isset($correction)) {
		echo $correction;
	}

	echo '</textarea>
	<br />
	<br />
	<textarea name="notes" class="readBox" onchange="onNavWarn();" onkeyup="onNavWarn();" rows="2" cols="82" placeholder="Notes...">';

	// Notes value
	if (isset($notes)) {
		echo $notes;
	}

	echo '</textarea>
	<br /><br />

	<button onclick="showSubmit(\'submit_correction\');" type="button" class="dk_sub_button">Submit final correction</button> <!-- type="button" so it will not submit the form -->
	&nbsp;&nbsp;
	<input type="submit" name="submit_correction" value="Confirm" id="submit_correction" class="ln_button" style="display:none;" />
	</form>
	<br />

	<script src="js/jquery-1.7.1.min.js"></script>
	<script src="js/wordcount.js"></script>
	';

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

	// AJAX #save_correction
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
			    ajaxFormData('editform', 'writ.ajax.php', 'ajax_changes'); // Run our "Save" AJAX
			  }
			}, false); // Ctrl + S capture
		</script>
	<?php

	// HTMLdiff
	echo '
	<script src="js/htmldiff.min.js"></script>
	<script>
	let oldHTML = `'.nl2br($draft).'`;
	let curHTML = `'.nl2br($edits).'`;
	let difHTML = htmldiff(oldHTML, curHTML);
	document.getElementById("outputDif").innerHTML = difHTML;
	</script>
	';

	return;
}
