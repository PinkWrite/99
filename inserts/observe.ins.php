<?php

// Editing
if (isset($_GET['w'])) {
	if (filter_var($_GET['w'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
		$writ_id = preg_replace("/[^0-9]/","", $_GET['w']);
	} else {
		echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
		exit(); // Quit the script
	}
}

	// Writ information
	$q = "SELECT writer_id, block, work, title, notes, draft, draft_wordcount, draft_status, edits, edits_wordcount, edit_notes, correction, correction_wordcount, edits_status, scoring, score, outof, draft_submit_date, corrected_submit_date FROM writs WHERE id='$writ_id'";
	$r = mysqli_query ($dbc, $q);
	$row = mysqli_fetch_array($r);
	$writer_id = "$row[0]";
	$block_id = "$row[1]";
	$work = "$row[2]";
	$title = "$row[3]";
	$notes = "$row[4]";
	$draft = "$row[5]";
	$draft_wordcount = "$row[6]";
	$draft_status = "$row[7]";
	$edits = "$row[8]";
	$edits_wordcount = "$row[9]";
	$edit_notes = "$row[10]";
	$correction = "$row[11]";
	$correction_wordcount = "$row[12]";
	$edits_status = "$row[13]";
	$scoring = "$row[14]";
	$score = "$row[15]";
	$outof = "$row[16]";
	$draft_submit_date = "$row[17]";
	$corrected_submit_date = "$row[18]";

	// Block
	if ($block_id == 0) {
		// Get the Editor name
		$qu = "SELECT editor FROM users WHERE id='$writer_id'";
		$ru = mysqli_query($dbc, $qu);
		$rowu = mysqli_fetch_array($ru);
		$editor_id = "$rowu[0]";

		// Get the Editor name
		$qe = "SELECT name FROM users WHERE id='$editor_id'";
		$re = mysqli_query($dbc, $qe);
		$rowe = mysqli_fetch_array($re);
		$editor_name = "$rowe[0]";

		$list_block = '<h4>Block: Main <small>('.$editor_name.')</small></h4>';

	} else {
		$qb = "SELECT name, code, editor_id FROM blocks WHERE id='$block_id'";
		$rb = mysqli_query($dbc, $qb);
		$rowb = mysqli_fetch_array($rb);
		$block_name = "$rowb[0]";
		$block_code = "$rowb[1]";
		$editor_id = "$rowb[0]";

		// Get the Editor name
		$qe = "SELECT name FROM users WHERE id='$editor_id'";
		$re = mysqli_query($dbc, $qe);
		$rowe = mysqli_fetch_array($re);
		$editor_name = "$rowe[0]";

		$list_block = '<h4>Block: '.$block_name.' <small>('.$block_code.')</small> with '.$editor_name.'</h4>';
	}

	// Writer information
	$q = "SELECT name, email FROM users WHERE id='$writer_id'";
	$r = mysqli_query ($dbc, $q);
	$row = mysqli_fetch_array($r, MYSQLI_NUM);
	$name = "$row[0]";
	$email = "$row[1]";
	echo "<p class=\"lt sans\">Writer: $name ($email)</p>";

	// Main fields
	echo '<h3 class="lt">Work: '.$work.'<br />Title: '.$title.'</h3>
	'.$list_block.'
	<h4>Score:</h4>
	<section class="writcontent score" id="edits">'.$score.'<small class="dk">/'.$outof.'</small></section>
	<h4>Scoring remarks:</h4>
	<section class="writcontent remarks" id="edits">'.nl2br(preg_replace("/[\r\n]{2,}/", "\n", $scoring)).'</section>
	<h4>Draft: <i class="dk sans">'.$draft_status.' '.$draft_submit_date.'</i></h4>
	<p class="sans lt">Word count: <span class="wordCountDisplay">'.$draft_wordcount.'</span></p>
	<section class="writcontent draft" id="draft">'.nl2br(preg_replace("/[\r\n]{2,}/", "\n", $draft)).'</section>
	<hr />
	<h4>Edited</h4>
	<h5>Remarks:</h5>
	<section class="writcontent remarks" id="edits">'.nl2br(preg_replace("/[\r\n]{2,}/", "\n", $edit_notes)).'</section>
	<h5>Edited diff:</h5>
	<section class="writcontent diff" id="diffDraftEdits"></section>
	<h5>Editor revision:</h5>
	<p class="sans lt">Word count: <span class="wordCountDisplay">'.$edits_wordcount.'</span></p>
	<section class="writcontent revision" id="edits">'.nl2br(preg_replace("/[\r\n]{2,}/", "\n", $edits)).'</section>
	<h4>Writer correction: <i class="dk sans">'.$edits_status.' '.$draft_submit_date.'</i></h4>
	<p class="sans lt">Word count: <span class="wordCountDisplay">'.$correction_wordcount.'</span></p>
	<section class="writcontent correction" id="edits">'.nl2br(preg_replace("/[\r\n]{2,}/", "\n", $correction)).'</section>
	<h5>Scored diff:</h5>
	<section class="writcontent diff" id="diffEditsFinal"></section>
	<h4>Scoring remarks: <small><i>(same as above, for reference)</i></small></h4>
	<section class="writcontent scoring" id="edits">'.nl2br(preg_replace("/[\r\n]{2,}/", "\n", $scoring)).'</section>
	<h4>Notes:</h4>
	<section class="writcontent notes" id="edits">'.nl2br(preg_replace("/[\r\n]{2,}/", "\n", $notes)).'</section>
	';

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
