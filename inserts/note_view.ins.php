<?php

if (isset($_GET['v'])) {
	if (filter_var($_GET['v'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
		$note_id = preg_replace("/[^0-9]/","", $_GET['v']);
	} else {
		echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
		exit(); // Quit the script
	}
}

if (isset($note_id)) {
	// Get the post info
	$q = "SELECT editor_id, editor_set_writer_id, editor_set_block, body, save_date FROM notes WHERE editor_id='$userid' AND id='$note_id'";
	$r = mysqli_query ($dbc, $q);
	if (mysqli_num_rows($r) == 0) {
		echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
		exit(); // Quit the script
	}
	$row = mysqli_fetch_array($r);
	$editor_id = "$row[0]";
	$editor_set_writer_id = "$row[1]";
	$editor_set_block = "$row[2]";
	$body = "$row[3]";
	$save_date = "$row[4]";
}

// Display meta
if ((isset($editor_set_writer_id)) && ($editor_set_writer_id != 0)) {
	// Writer
	$qw = "SELECT name, email FROM users WHERE id='$editor_set_writer_id'";
	$rw = mysqli_query ($dbc, $qw);
	$roww = mysqli_fetch_array($rw, MYSQLI_NUM);
	$writer_name = "$roww[0]";
	$writer_email = "$roww[1]";
	echo '<h3 class="sans dk">For writer: '.$writer_name.' <small>('.$writer_email.')</small></h4>';
} elseif ((isset($editor_set_block)) && ($editor_set_block != 0)) {
	// Block
	$qb = "SELECT name, code FROM blocks WHERE id='$editor_set_block'";
	$rb = mysqli_query ($dbc, $qb);
	$rowb = mysqli_fetch_array($rb, MYSQLI_NUM);
	$block_name = "$rowb[0]";
	$block_code = "$rowb[1]";
	echo '<h3 class="sans dk">For block: '.$block_name.' <small>('.$block_code.')</small></h4>';
} else {
	echo '<h3 class="sans dk">No writer set</h4>';
}
// Note info
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
