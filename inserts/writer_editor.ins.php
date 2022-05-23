<?php

// Logged in or not?
if (!isset($_SESSION['user_id'])) {
	return;
}

if (isset($_GET['u'])) {
	if (filter_var($_GET['u'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
		$writer_id = preg_replace("/[^0-9]/","", $_GET['u']);
	} else {
		echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
		exit(); // Quit the script
	}
}

// Okay to view this page
$userid = $_SESSION['user_id'];

// Get the Writer's name & email
$qw = "SELECT name, email FROM users WHERE id='$writer_id'";
$rw = mysqli_query($dbc, $qw);
$roww = mysqli_fetch_array($rw);
$writer_name = "$roww[0]";
$writer_email = "$roww[1]";
if (mysqli_num_rows($rw) == 0) {
	echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
	exit(); // Quit the script
}

// Block name
echo '<h3>Writs by: '.$writer_name.' <small>('.$writer_email.')</small></h3>';

// Writ table
$term_status = 'current';
$where_am_i = "writer_editor.php?u=$writer_id";
include('inserts/list_editor.ins.php');
