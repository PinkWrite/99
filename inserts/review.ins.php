<?php

// Editing
if (!isset($_GET['w'])) {
	header("Location: " . PW99_HOME);
	exit(); // Quit the script
}

if (filter_var($_GET['w'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
	$writ_id = preg_replace("/[^0-9]/","", $_GET['w']);
} else {
	header("Location: " . PW99_HOME);
	exit(); // Quit the script
}

// Editor revision & Scoring form submission
if (($_SERVER['REQUEST_METHOD'] == 'POST') && (isset($_POST['reviewed_writer_id']))) {

	$writer_id = $_POST['reviewed_writer_id'];
	$block_id = (isset($_POST['block'])) ? filter_var($_POST['block'], FILTER_VALIDATE_INT, array('min_range' => 1)) : NULL;
	$title = (isset($_POST['title'])) ? strip_tags(htmlspecialchars(substr($_POST['title'],0,122))) : NULL;
	//$draft = (isset($_POST['draft'])) ? strip_tags(htmlspecialchars($_POST['draft'])) : NULL;
	$draft_status = (isset($_POST['draft_status'])) ? strip_tags(htmlspecialchars($_POST['draft_status'])) : NULL;
	$notes = (isset($_POST['notes'])) ? strip_tags(htmlspecialchars($_POST['notes'])) : NULL;
	$work = (isset($_POST['work'])) ? strip_tags(htmlspecialchars(substr($_POST['work'],0,122))) : NULL;
	$edits = (isset($_POST['edits'])) ? strip_tags(htmlspecialchars($_POST['edits'])) : NULL;
	$edits_wordcount = (isset($_POST['edits_wordcount'])) ? filter_var($_POST['edits_wordcount'], FILTER_VALIDATE_INT) : 0;
	$edit_notes = (isset($_POST['edit_notes'])) ? strip_tags(htmlspecialchars($_POST['edit_notes'])) : NULL;
	$edits_status = (isset($_POST['edits_status'])) ? strip_tags(htmlspecialchars($_POST['edits_status'])) : NULL;
	//$correction = (isset($_POST['correction'])) ? strip_tags(htmlspecialchars($_POST['correction'])) : NULL;
	$scoring = (isset($_POST['scoring'])) ? strip_tags(htmlspecialchars($_POST['scoring'])) : NULL;
	$score = (isset($_POST['score'])) ? filter_var($_POST['score'], FILTER_VALIDATE_INT, array('min_range' => 0, 'max_range' => 1000)) : NULL;
	$outof = ( (isset($_POST['outof'])) && ($_POST['outof'] != '') && ($_POST['outof'] != NULL) ) ? filter_var($_POST['outof'], FILTER_VALIDATE_INT, array('min_range' => 0, 'max_range' => 1000)) : 100;

	$title = trim(preg_replace('/\s+/', ' ', $title));
	$work = trim(preg_replace('/\s+/', ' ', $work));
	$notes = trim(preg_replace("/[\r\n]{2,}/", "\n\n", $notes)); // [\r\n]{2,} is three empty lines or more
	$edit_notes = trim(preg_replace("/[\r\n]{2,}/", "\n\n", $edit_notes));
	$scoring = trim(preg_replace('/[ ]+/', ' ', preg_replace("/[\r\n]{2,}/", "\n\n", $scoring)));
	$edits = trim(preg_replace('/[ ]+/', ' ', preg_replace("/[\r\n]+/", "\n", $edits)));

	$sql_block_id = mysqli_real_escape_string($dbc, $block_id);
	$sql_title = mysqli_real_escape_string($dbc, $title);
	//$sql_draft = mysqli_real_escape_string($dbc, $draft);
	$sql_notes = mysqli_real_escape_string($dbc, $notes);
	$sql_work = mysqli_real_escape_string($dbc, $work);
	$sql_edits = mysqli_real_escape_string($dbc, $edits);
	$sql_edits_wordcount = mysqli_real_escape_string($dbc, $edits_wordcount);
	$sql_edit_notes = mysqli_real_escape_string($dbc, $edit_notes);
	//$sql_correction = mysqli_real_escape_string($dbc, $correction);
	$sql_scoring = mysqli_real_escape_string($dbc, $scoring);
	$sql_score = mysqli_real_escape_string($dbc, $score);
	$sql_outof = mysqli_real_escape_string($dbc, $outof);

	// From single-writer list?
	(isset($_SESSION['list_writer'])) ? $list_writer = $_SESSION['list_writer'] : false;

	// Submit edits
	if (isset($_POST['submit_edits'])) {
		if ( ($score == '') || ($score == NULL) ) {
			$q = "UPDATE writs SET block='$sql_block_id', title='$sql_title', work='$sql_work', notes='$sql_notes', scoring='$sql_scoring', score=NULL, outof='$sql_outof', edits='$sql_edits', edits_wordcount='$sql_edits_wordcount', edit_notes='$sql_edit_notes', draft_status='reviewed', edits_date=NOW() WHERE writer_id='$writer_id' AND id='$writ_id'";
		} else {
			$q = "UPDATE writs SET block='$sql_block_id', title='$sql_title', work='$sql_work', notes='$sql_notes', scoring='$sql_scoring', score='$sql_score', outof='$sql_outof', edits='$sql_edits', edits_wordcount='$sql_edits_wordcount', edit_notes='$sql_edit_notes', draft_status='reviewed', edits_date=NOW() WHERE writer_id='$writer_id' AND id='$writ_id'";
		}
		$r = mysqli_query ($dbc, $q);
		if ($r) {
			(isset($list_writer)) ? header("Location: writer_editor.php?u=$list_writer") : header("Location: editor.php");
			exit(); // Quit the script
		} else {
			echo "<div class=\"noticered sans\">Database error, could not be submitted.</div>";
		}

	// Submit score
	} elseif (isset($_POST['submit_scoring'])) {
		if ( ($score == '') || ($score == NULL) ) {
			$q = "UPDATE writs SET block='$sql_block_id', title='$sql_title', work='$sql_work', notes='$sql_notes', scoring='$sql_scoring', score=NULL, outof='$sql_outof', edits_status='scored', scoring_date=NOW() WHERE writer_id='$writer_id' AND id='$writ_id'";
		} else {
			$q = "UPDATE writs SET block='$sql_block_id', title='$sql_title', work='$sql_work', notes='$sql_notes', scoring='$sql_scoring', score='$sql_score', outof='$sql_outof', edits_status='scored', scoring_date=NOW() WHERE writer_id='$writer_id' AND id='$writ_id'";
		}
		$r = mysqli_query ($dbc, $q);
		if ($r) {
			(isset($list_writer)) ? header("Location: writer_editor.php?u=$list_writer") : header("Location: editor.php");
			exit(); // Quit the script
		} else {
			echo "<div class=\"noticered sans\">Database error, score could not be submitted.</div>";
		}
		// Submit score NOW
	} elseif (isset($_POST['submit_scoring_now'])) {
		if ( ($score == '') || ($score == NULL) ) {
			$q = "UPDATE writs SET block='$sql_block_id', title='$sql_title', work='$sql_work', notes='$sql_notes', edits='$sql_edits', edits_wordcount='$sql_edits_wordcount', edit_notes='$sql_edit_notes', scoring='$ssql_coring', score=NULL, outof='$sql_outof', correction='NO NEED', draft_status='reviewed', edits_status='scored', scoring_date=NOW() WHERE writer_id='$writer_id' AND id='$writ_id'";
		} else {
			$q = "UPDATE writs SET block='$sql_block_id', title='$sql_title', work='$sql_work', notes='$sql_notes', edits='$sql_edits', edits_wordcount='$sql_edits_wordcount', edit_notes='$sql_edit_notes', scoring='$ssql_coring', score='$sql_score', outof='$sql_outof', correction='NO NEED', draft_status='reviewed', edits_status='scored', scoring_date=NOW() WHERE writer_id='$writer_id' AND id='$writ_id'";
		}
		$r = mysqli_query ($dbc, $q);
		if ($r) {
			(isset($list_writer)) ? header("Location: writer_editor.php?u=$list_writer") : header("Location: editor.php");
			exit(); // Quit the script
		} else {
			echo "<div class=\"noticered sans\">Database error, score could not be submitted.</div>";
		}

	// Save edits
	} elseif (isset($_POST['save_edit'])) {
		if ( ($score == '') || ($score == NULL) ) {
			$q = "UPDATE writs SET block='$sql_block_id', title='$sql_title', work='$sql_work', notes='$sql_notes', edits='$sql_edits', edits_wordcount='$sql_edits_wordcount', edit_notes='$sql_edit_notes', scoring='$sql_scoring', score=NULL, outof='$sql_outof' WHERE writer_id='$writer_id' AND id='$writ_id'";
		} else {
			$q = "UPDATE writs SET block='$sql_block_id', title='$sql_title', work='$sql_work', notes='$sql_notes', edits='$sql_edits', edits_wordcount='$sql_edits_wordcount', edit_notes='$sql_edit_notes', scoring='$sql_scoring', score='$sql_score', outof='$sql_outof' WHERE writer_id='$writer_id' AND id='$writ_id'";
		}

		$r = mysqli_query ($dbc, $q);
		if ($r) {
			echo "<div class=\"noticegreen sans\">Editor revision saved, not finalized.</div>";
		} else {
			echo "<div class=\"noticered sans\">Database error, edits could not be saved.</div>";
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
			echo "<div class=\"noticegreen sans\">Scoring saved, not finalized.</div>";
		} else {
			echo "<div class=\"noticered sans\">Database error, score could not be saved.</div>";
		}

	} // End edit/submit form submission options
} // End $_POST submission

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

// Editor form functions

// Editor revision for correction form
function edit_form() {
	// Make our variables work
	global $dbc, $userid, $writ_id, $block_id, $u_blocks_array, $writer_id, $work, $title, $notes, $draft, $draft_status, $correction, $edits, $edit_notes, $edits_status, $scoring, $score, $outof, $draft_save_date, $draft_submit_date, $edits_date, $corrected_save_date, $corrected_submit_date, $scoring_date, $name, $email, $block_listing, $rformaction;

	// Create the form
	echo '
	<form id="editsform" class="userform" action="'.$rformaction.'?w='.$writ_id.'" method="post" accept-charset="utf-8" onsubmit="offNavWarn();">
	<input type="hidden" name="reviewed_writer_id" value="'.$writer_id.'" />
	<input type="hidden" name="writ_id" value="'.$writ_id.'">

	<button type="button" title="Save (Ctrl + S)" class="lt_button" onclick="ajaxFormData(\'editsform\', \'review.ajax.php\', \'ajax_changes\'); offNavWarn();">Save</button>
	&nbsp;<span id="wordCount" class="wordCounter" ></span>
	<div id="ajax_changes" style="display: inline;"></div><br />
	<input type="hidden" name="save_edit" value="Save" id="save_edit" class="lt_button" /><br />

	<h2>Editor revision:</h2>
	<textarea name="edits" id="writingArea" class="writingBox" onchange="onNavWarn();" onkeyup="onNavWarn();" rows="8" cols="82" placeholder="Draft edit contents...">';

	// Editor revision value
	if (isset($edits)) {
		echo $edits;
	}

	echo '</textarea>
	<input type="hidden" name="edits_wordcount" id="wordCountInput" value="0">
	<h3>Editor remarks:</h3>
	<textarea name="edit_notes" id="edit_notes" class="writingBox" onchange="onNavWarn();" onkeyup="onNavWarn();" rows="3" cols="82" placeholder="Draft edit contents...">';

	// Editor remarks value
	if (isset($edit_notes)) {
		echo $edit_notes;
	}

	echo '</textarea>
	<br />
	<br />
	<button onclick="showSubmit(\'submit_edits\');" type="button" class="dk_sub_button">Submit edits</button> <!-- type="button" so it will not submit the form -->
	&nbsp;&nbsp;
	<input type="submit" name="submit_edits" value="Confirm" id="submit_edits" class="ln_button" style="display:none;" />
	<div class="right">
	<p class="sans dk">
		<input type="submit" name="submit_scoring_now" value="Confirm" id="submit_scoring_now" class="ln_button" style="display:none;" />
		&nbsp;&nbsp;
		<input type="text" name="scoring" id="scoring" class="lt_text" onchange="onNavWarn();" onkeyup="onNavWarn();"';

		// Score now
		if (isset($scoring)) {
			echo ' value="'.$scoring.'"';
		}
		echo ' placeholder="Scoring..." />&nbsp;&nbsp;';

		echo '
		<button onclick="showSubmit(\'submit_scoring_now\');" type="button" class="dk_sub_button">Score now</button> <!-- type="button" so it will not submit the form -->
		&nbsp;
		<input type="number" name="score" id="score" class="score_lt" onchange="onNavWarn();" onkeyup="onNavWarn();" value="'.$score.'" step="1" min="0" max="1000" />&nbsp;
		/&nbsp;<input type="number" name="outof" id="outof" class="outof_dk" onchange="onNavWarn();" onkeyup="onNavWarn();" value="'.$outof.'" step="1" min="0" max="1000" />&nbsp;
	</p>
	</div>
	<br />
	<hr />';

	// Writer info
	echo "<p class=\"lt sans\">Writer: $name ($email)<br />Block: $block_listing</p>";
	echo '
	<hr />
	<br />
	<br />
	<label class ="sans" for="notes">Notes</label><br /><br />
	<textarea name="notes" class="readBox" onchange="onNavWarn();" onkeyup="onNavWarn();" rows="2" cols="82" placeholder="No notes">';

	// Notes value
	if (isset($notes)) {
		echo $notes;
	}

	echo '</textarea>
	<br />
	<br />
	<label class ="sans" for="title">Title</label><br /><br />

	<input type="text" name="title" id="title" class="readBox" onchange="onNavWarn();" onkeyup="onNavWarn();"';

	// Title value
	if (isset($title)) {
		echo ' value="'.$title.'"';
	}
	echo ' placeholder="NO TITLE" />';

	echo '<br /><br />
	<label class ="sans" for="work">Work</label><br /><br />
	<input type="text" name="work" id="work" class="readBox" onchange="onNavWarn();" onkeyup="onNavWarn();" required';

	// Work value
	if (isset($work)) {
		echo ' value="'.$work.'"';
	}
	echo ' placeholder="NO WORK LABEL" />';

	// Block
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

	// Finish the form
	echo '
	</form>

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

	// AJAX #save_edit
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
			  if ((window.navigator.platform.match("Mac") ? cs.metaKey : cs.ctrlKey)  && cs.keyCode == 83) {
			    cs.preventDefault(); // Stop it from doing what it normally does
			    ajaxFormData('editsform', 'review.ajax.php', 'ajax_changes'); // Run our "Save" AJAX
			  }
			}, false); // Ctrl + S capture
		</script>
	<?php

} // Editor revision form function

// Scoring form
 function score_form() {
	 // Make our variables work
		global $dbc, $userid, $writ_id, $block_id, $u_blocks_array, $writer_id, $work, $title, $notes, $draft, $draft_status, $correction, $edits, $edit_notes, $edits_status, $scoring, $score, $outof, $draft_save_date, $draft_submit_date, $edits_date, $corrected_save_date, $corrected_submit_date, $scoring_date, $name, $email, $block_listing, $rformaction;

		// Create the form
	echo '
	<form id="scoringform" class="userform" action="'.$rformaction.'?w='.$writ_id.'" method="post" accept-charset="utf-8" onsubmit="offNavWarn();">
	<input type="hidden" name="reviewed_writer_id" value="'.$writer_id.'" />
	<input type="hidden" name="writ_id" value="'.$writ_id.'">

	<h2>Final scoring:</h2>

	<button type="button" title="Save (Ctrl + S)" class="lt_button" onclick="ajaxFormData(\'scoringform\', \'review.ajax.php\', \'ajax_changes\'); offNavWarn();">Save</button>
	&nbsp;<span id="wordCount" class="wordCounter" ></span>
	<div id="ajax_changes" style="display: inline;"></div><br />
	<input type="hidden" name="save_scoring" value="Save" id="save_scoring" class="lt_button" /><br />

	<textarea name="scoring" id="writingArea" class="writingBox" onchange="onNavWarn();" onkeyup="onNavWarn();" rows="3" cols="82" placeholder="Final scoring comments...">';

	// Scoring value
	if (isset($scoring)) {
		echo $scoring;
	}

	echo '</textarea>
	<p class="sans dk">
	<input type="number" name="score" id="score" class="score_dk" onchange="onNavWarn();" onkeyup="onNavWarn();" required value="'.$score.'" step="1" min="0" max="1000" />&nbsp;
	/&nbsp;<input type="number" name="outof" id="outof" class="outof_dk" onchange="onNavWarn();" onkeyup="onNavWarn();" value="'.$outof.'" step="1" min="0" max="1000" />&nbsp;
	<button onclick="showSubmit(\'submit_scoring\');" type="button" class="dk_sub_button">Submit score</button> <!-- type="button" so it will not submit the form -->
	&nbsp;&nbsp;
	<input type="submit" name="submit_scoring" value="Confirm" id="submit_scoring" class="ln_button" style="display:none;" />
	</p>
	<br />
	<hr />';

	// Writer info
	echo "<p class=\"lt sans\">Writer: $name ($email)<br />Block: $block_listing</p>";
	echo '
	<hr />
	<br />
	<br />
	<label class ="sans" for="notes">Notes</label><br /><br />
	<textarea name="notes" class="readBox" onchange="onNavWarn();" onkeyup="onNavWarn();" rows="2" cols="82" placeholder="No notes">';

	// Notes value
	if (isset($notes)) {
		echo $notes;
	}

	echo '</textarea>
	<br />
	<br />
	<label class ="sans" for="title">Title</label><br /><br />

	<input type="text" name="title" id="title" class="readBox" onchange="onNavWarn();" onkeyup="onNavWarn();"';

	// Title value
	if (isset($title)) {
		echo ' value="'.$title.'"';
	}
	echo ' placeholder="NO TITLE" />';

	echo '<br /><br />
	<label class ="sans" for="work">Work</label><br /><br />
	<input type="text" name="work" id="work" class="readBox" onchange="onNavWarn();" onkeyup="onNavWarn();" required';

	// Work value
	if (isset($work)) {
		echo ' value="'.$work.'"';
	}
	echo ' placeholder="NO WORK LABEL" />';

	// Block
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

	// Finish the form
	echo '
	</form>

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

	// AJAX #save_scoring
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
			  if ((window.navigator.platform.match("Mac") ? cs.metaKey : cs.ctrlKey)  && cs.keyCode == 83) {
			    cs.preventDefault(); // Stop it from doing what it normally does
			    ajaxFormData('scoringform', 'review.ajax.php', 'ajax_changes'); // Run our "Save" AJAX
			  }
			}, false); // Ctrl + S capture
		</script>
	<?php


} // Score form function

// Work information
$q = "SELECT writer_id, block, work, title, notes, draft, draft_wordcount, draft_status, correction, correction_wordcount, edits, edit_notes, edits_status, scoring, score, outof, draft_save_date, draft_submit_date, edits_date, corrected_save_date, corrected_submit_date, scoring_date FROM writs WHERE id='$writ_id'";
$r = mysqli_query ($dbc, $q);
$row = mysqli_fetch_array($r, MYSQLI_NUM);
$writer_id = "$row[0]";
$block_id = "$row[1]";
$work = "$row[2]";
$title = "$row[3]";
$notes = "$row[4]";
$draft = "$row[5]";
$draft_wordcount = "$row[6]";
$draft_status = "$row[7]";
$correction = "$row[8]";
$correction_wordcount = "$row[9]";
$edits = "$row[10]";
$edit_notes = "$row[11]";
$edits_status = "$row[12]";
$scoring = "$row[13]";
$score = "$row[14]";
$outof = "$row[15]";
$draft_save_date = "$row[16]";
$draft_submit_date = "$row[17]";
$edits_date = "$row[18]";
$corrected_save_date = "$row[19]";
$corrected_submit_date = "$row[20]";
$scoring_date = "$row[21]";

// Current status

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

	// Writer information
	$q = "SELECT name, email, blocks FROM users WHERE id='$writer_id'";
	$r = mysqli_query ($dbc, $q);
	$row = mysqli_fetch_array($r, MYSQLI_NUM);
	$name = "$row[0]";
	$email = "$row[1]";
	$u_blocks_array = json_decode($row[2], true);
	echo "<p class=\"lt sans\">Writer: $name ($email)<br />Block: $block_listing</p>";

	// Work & Title
	echo "<h3 class=\"lt\">Work: $work<br />Title: $title</h3>";

	// Draft in-progress
	if ($draft_status == 'saved') {
		echo "<hr class=\"review\" />";
		echo "<h4>First draft: (In-progress)<br /><i class=\"dk sans\">(<b>Saved</b> $draft_save_date)</i></h4>
					<section class='writcontent draft'>".nl2br(preg_replace("/[\r\n]{2,}/", "\n", $draft))."</section>";
		// Nothing if there are no notes
		$show_notes = ( (!empty($notes)) && ($notes != '') ) ? "<h4 class='lt'>Notes:</h4><section class='writcontent notes'>$notes</section>" : '';
		echo $show_notes;
		return;
	// Draft submitted
} elseif ($draft_status == 'submitted') {
		echo "<hr class=\"review\" />";
		echo "<h4>First draft: (Submitted for review)<br /><i class=\"dk sans\">(<b>Submitted</b> $draft_submit_date)</i></h4>
					<p class=\"sans lt\">Word count: <span class=\"wordCountDisplay\">$draft_wordcount</span></p>
					<section class='writcontent draft'>".nl2br(preg_replace("/[\r\n]{2,}/", "\n", $draft))."</section>";
		echo "<hr class=\"review\" />";
					edit_form();
		return;
	// Draft reviewed
}	elseif (($draft_status == 'reviewed') && ($edits_status == 'drafting')) {
		echo "<hr class=\"review\" />";
		echo "<h4>First draft: (Reviewed)<br /><i class=\"dk sans\">(<b>Submitted</b> $draft_submit_date)</i></h4>
					<p class=\"sans lt\">Word count: <span class=\"wordCountDisplay\">$draft_wordcount</span></p>
					<section class='writcontent draft'>".nl2br(preg_replace("/[\r\n]{2,}/", "\n", $draft))."</section>";
		echo "<hr class=\"review\" />
					<h3 class=\"note_blue\">Editor revision complete!<br /><i class=\"note_blue sans\">(<b>Reviewed</b> $edits_date)</i></h3>
					<h4>Editor revision:</h4>
					<section class='writcontent revision' id='diffDraftEdits'></section>";
		// HTMLdiff
		echo '
					<script src="js/htmldiff.min.js"></script>
					<script>
					let oldHTML = `'.nl2br(preg_replace("/[\r\n]{2,}/", "\n", $draft)).'`;
					let curHTML = `'.nl2br(preg_replace("/[\r\n]{2,}/", "\n", $edits)).'`;
					let difHTML = htmldiff(oldHTML, curHTML);
					document.getElementById("diffDraftEdits").innerHTML = difHTML;
					</script>
					';
					edit_form();
		return;
	// Final in-progress
}	elseif (($draft_status == 'reviewed') && (($edits_status == 'viewed') || ($edits_status == 'saved'))) {
		echo "<h4>First draft: (Reviewed)<br /><i class=\"dk sans\">(<b>Submitted</b> $draft_submit_date)</i></h4>
					<section class='writcontent draft'>".nl2br(preg_replace("/[\r\n]{2,}/", "\n", $draft))."</section>
					<h4>Editor revision:<br /><i class=\"dk sans\">(<b>Reviewed</b> $edits_date)</i></h4>
					<p class=\"sans lt\">Word count: <span class=\"wordCountDisplay\">$edits_wordcount</span></p>
					<section class='writcontent revision'>".nl2br(preg_replace("/[\r\n]{2,}/", "\n", $edits))."</section>
					<h5>Edited diff:</h5>
					<section class='writcontent diff' id='diffDraftEdits'></section>
					<h5>Editor remarks:</h5>
					<section class='writcontent remarks'>".nl2br(preg_replace("/[\r\n]{2,}/", "\n", $edit_notes))."</section>";
		echo "<hr class=\"review\" />";
		echo "<h4>Final corrected revision: (In-Progress)<br /><i class=\"dk sans\">(<b>Saved</b> $corrected_save_date)</i></h4>
					<p class=\"sans lt\">Word count: <span class=\"wordCountDisplay\">$correction_wordcount</span></p>
					<section class='writcontent correction'>".nl2br(preg_replace("/[\r\n]{2,}/", "\n", $correction))."</section>";
		echo "<h4>Notes:</h4>
					<section class='writcontent notes'>".nl2br(preg_replace("/[\r\n]{2,}/", "\n", $notes))."</section>";
		// HTMLdiff
		echo '
					<script src="js/htmldiff.min.js"></script>
					<script>
					let oldHTML = `'.nl2br(preg_replace("/[\r\n]{2,}/", "\n", $draft)).'`;
					let curHTML = `'.nl2br(preg_replace("/[\r\n]{2,}/", "\n", $edits)).'`;
					let difHTML = htmldiff(oldHTML, curHTML);
					document.getElementById("diffDraftEdits").innerHTML = difHTML;
					</script>
					';
		return;
	// Final submitted
}	elseif (($draft_status == 'reviewed') && ($edits_status == 'submitted')) {
		echo '<h4>First draft: (Reviewed)<br /><i class="dk sans">(<b>Submitted</b> '.$draft_submit_date.')</i></h4>
					<h4>Edited</h4>
					<h5>Remarks:</h5>
					<section class="writcontent remarks">'.nl2br(preg_replace("/[\r\n]{2,}/", "\n", $edit_notes)).'</section>
					<h5>Edited diff:</h5>
					<section class="writcontent diff" id="diffDraftEdits"></section>
					<section class="writcontent draft">'.nl2br(preg_replace("/[\r\n]{2,}/", "\n", $draft)).'</section>
					<h5>Editor revision:<br /><i class="dk sans">(<b>Submitted</b> '.$corrected_submit_date.')</i></h5>
					<section class="writcontent revision">'.nl2br(preg_replace("/[\r\n]{2,}/", "\n", $edits)).'</section>';
		echo '<h4>Final corrected revision: (Submitted for scoring)<br /><i class="dk sans">(<b>Submitted</b> '.$corrected_submit_date.')</i></h4>
					<p class="sans lt">Word count: <span class="wordCountDisplay">'.$correction_wordcount.'</span></p>
					<section class="writcontent correction">'.nl2br(preg_replace("/[\r\n]{2,}/", "\n", $correction)).'</section>
					<h5>Scored diff:</h5>
					<section class="writcontent diff" id="diffEditsFinal"></section>
					<h5>Remarks: (again)</h5>
					<section class="writcontent remarks">'.nl2br(preg_replace("/[\r\n]{2,}/", "\n", $edit_notes)).'</section>';
		echo '<h4>Notes:</h4>
					<section class="writcontent notes">'.nl2br(preg_replace("/[\r\n]{2,}/", "\n", $notes)).'</section>';
		echo '<hr class="review" />';
					score_form();
		// HTMLdiff
		echo '
					<script src="js/htmldiff.min.js"></script>
					<script>
					let draftHTML = `'.nl2br(preg_replace("/[\r\n]{2,}/", "\n", $draft)).'`;
					let editsHTML = `'.nl2br(preg_replace("/[\r\n]{2,}/", "\n", $edits)).'`;
					let finalHTML = `'.nl2br(preg_replace("/[\r\n]{2,}/", "\n", $correction)).'`;
					let difDraftEditsHTML = htmldiff(draftHTML, editsHTML);
					let difEditsFinalHTML = htmldiff(editsHTML, finalHTML);
					document.getElementById("diffDraftEdits").innerHTML = difDraftEditsHTML;
					document.getElementById("diffEditsFinal").innerHTML = difEditsFinalHTML;
					</script>
					';
		return;
	// Scored
} elseif (($draft_status == 'reviewed') && ($edits_status == 'scored')) {
		echo '<h3 class="lt">Score: '.$score.'<small class="dk">/'.$outof.'</small></h3>
					<h4>First draft:<br /><i class="dk sans">(<b>Submitted</b> '.$draft_submit_date.')</i></h4>
					<section class="writcontent draft">'.nl2br(preg_replace("/[\r\n]{2,}/", "\n", $draft)).'</section>
					<h4>Edited</h4>
					<h5>Remarks:</h5>
					<section class="writcontent remarks">'.nl2br(preg_replace("/[\r\n]{2,}/", "\n", $edit_notes)).'</section>
					<h5>Edited diff:</h5>
					<section class="writcontent diff" id="diffDraftEdits"></section>
					<h5>Editor revision:<br /><i class=\"dk sans\">(<b>Submitted</b> '.$corrected_submit_date.')</i></h5>
					<section class="writcontent revision">'.nl2br(preg_replace("/[\r\n]{2,}/", "\n", $edits)).'</section>';
		echo '<h4>Final corrected revision:</h4>
					<p class="sans lt">Word count: <span class="wordCountDisplay">'.$correction_wordcount.'</span></p>
					<section class="writcontent correction">'.nl2br(preg_replace("/[\r\n]{2,}/", "\n", $correction)).'</section>
					<h5>Scored diff:</h5>
					<section class="writcontent diff" id="diffEditsFinal"></section>
					<h5>Remarks: (again)</h5>
					<section class="writcontent remarks">'.nl2br(preg_replace("/[\r\n]{2,}/", "\n", $edit_notes)).'</section>';
		echo '<h4>Notes:</h4>
					<section class="writcontent notes">'.nl2br(preg_replace("/[\r\n]{2,}/", "\n", $notes)).'</section>';
		echo '<hr class="review" />
					<h3 class="note_blue">Score complete!<br /><i class="note_blue sans">(<b>Scored</b> '.$scoring_date.')</i></h3>';
					score_form();
		// HTMLdiff
		echo '
					<script src="js/htmldiff.min.js"></script>
					<script>
					let draftHTML = `'.nl2br(preg_replace("/[\r\n]{2,}/", "\n", $draft)).'`;
					let editsHTML = `'.nl2br(preg_replace("/[\r\n]{2,}/", "\n", $edits)).'`;
					let finalHTML = `'.nl2br(preg_replace("/[\r\n]{2,}/", "\n", $correction)).'`;
					let difDraftEditsHTML = htmldiff(draftHTML, editsHTML);
					let difEditsFinalHTML = htmldiff(editsHTML, finalHTML);
					document.getElementById("diffDraftEdits").innerHTML = difDraftEditsHTML;
					document.getElementById("diffEditsFinal").innerHTML = difEditsFinalHTML;
					</script>
					';
		return;
}
