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
	$q = "SELECT writer_id, block, work, title, notes, draft, draft_status, edits, edit_notes, correction, edits_status, scoring, score, outof FROM writs WHERE id='$writ_id'";
	$r = mysqli_query ($dbc, $q);
	$row = mysqli_fetch_array($r);
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
	<section class="writcontent remarks" id="edits">'.nl2br($scoring).'</section>
	<h4>Draft: '.$draft_status.'</h4>
	<section class="writcontent draft" id="draft">'.nl2br($draft).'</section>
	<hr />
	<h4>Edited</h4>
	<h5>Remarks:</h5>
	<section class="writcontent remarks" id="edits">'.nl2br($edit_notes).'</section>
	<h5>Diff:</h5>
	<section class="writcontent diff" id="outputDif"></section>
	<h5>Editor revision:</h5>
	<section class="writcontent revision" id="edits">'.nl2br($edits).'</section>
	<h4>Writer correction: '.$edits_status.'</h4>
	<section class="writcontent correction" id="edits">'.nl2br($correction).'</section>
	<h4>Editor scoring:</h4>
	<section class="writcontent scoring" id="edits">'.nl2br($correction).'</section>
	<h4>Notes:</h4>
	<section class="writcontent notes" id="edits">'.nl2br($notes).'</section>
	';

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
