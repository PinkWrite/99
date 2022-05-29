<?php

// Logged in or not?
if (!isset($_SESSION['user_id'])) {
	return;
}

if ((isset($_GET['v'])) && ((filter_var($_GET['v'], FILTER_VALIDATE_INT, array('min_range' => 0))) || ($_GET['v'] == '0'))) {
	$list_block_id = preg_replace("/[^0-9]/","", $_GET['v']);;
	$block_type = ($list_block_id == 0) ? 'main' : 'block';
} else {
	echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
	exit(); // Quit the script
}


// Okay to view this page
$userid = $_SESSION['user_id'];

// Build page according to block type
// Main Block
if ($block_type == 'main') {

	// Get the Editor name
	$qe = "SELECT name FROM users WHERE id='$userid'";
	$re = mysqli_query ($dbc, $qe);
	$rowe = mysqli_fetch_array($re);
	$editor_name = "$rowe[0]";
	if (mysqli_num_rows($re) == 0) {
		echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
		exit(); // Quit the script
	}

	// Block name
	echo '<h3>Main Block: '.$editor_name.'</h3>';

	// Tasks query
	$qt = "SELECT id, name, code FROM tasks WHERE block='0' AND type='open' AND editor_id='$editor_id'";

// Normal Block
} elseif ($block_type == 'block') {
	// Get the Block info
	$qb = "SELECT editor_id, name, code FROM blocks WHERE id='$list_block_id'";
	$rb = mysqli_query ($dbc, $qb);
	$rowb = mysqli_fetch_array($rb);
	$editor_id = "$rowb[0]";
	$block_name = "$rowb[1]";
	$block_code = "$rowb[2]";
	if (mysqli_num_rows($rb) == 0) {
		echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
		exit(); // Quit the script
	}
	// Get the Editor name
	$qe = "SELECT name FROM users WHERE id='$editor_id'";
	$re = mysqli_query ($dbc, $qe);
	$rowe = mysqli_fetch_array($re);
	$editor_name = "$rowe[0]";
	if (mysqli_num_rows($re) == 0) {
		echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
		exit(); // Quit the script
	}

	// Block name
	echo '<h3>Writs for block: '.$block_name.' <small>('.$block_code.') <i>'.$editor_name.'</i></small></h3>';
}

// Writ table
include('inserts/list_editor.ins.php');
