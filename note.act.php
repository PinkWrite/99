<?php

// Require the configuration before any PHP code as the configuration controls error reporting
require('./pw99-config.php');

// A settings page requires form functions
require_once('./includes/form_functions.inc.php');


// Include the header
$page_title = "$siteTitle";
include('./includes/header.html');

// Logged in or not?
if (isset($_SESSION['user_id'])) {
	$userid = $_SESSION['user_id'];

	// Writer archiving selected
	if ( ($_SERVER['REQUEST_METHOD'] === 'POST') && (isset($_POST['pin'])) ) {
		$note_id = $_POST['pin'];
		$q = "UPDATE notes SET pinned=true WHERE writer_id='$userid' AND id='$note_id'";
		$r = mysqli_query ($dbc, $q);
		if (!$r) {
			echo "Database error, give to tech support: <pre>$q</pre>"; exit();
		} else {
			header("Location: notes.php");
			exit(); // Quit the script
		}

	// Writer restoring selected
	} elseif ( ($_SERVER['REQUEST_METHOD'] === 'POST') && (isset($_POST['unpin'])) ) {
			$note_id = $_POST['unpin'];
			$q = "UPDATE notes SET pinned=false WHERE writer_id='$userid' AND id='$note_id'";
			$r = mysqli_query ($dbc, $q);
			if (!$r) {
				echo "Database error, give to tech support: <pre>$q</pre>"; exit();
			} else {
				header("Location: notes.php");
				exit(); // Quit the script
			}

	// Writer restoring selected
	} elseif ( ($_SERVER['REQUEST_METHOD'] === 'POST') && (isset($_POST['undash'])) ) {
			$note_id = $_POST['undash'];
			$q = "UPDATE notes SET pinned=false WHERE writer_id='$userid' AND id='$note_id'";
			$r = mysqli_query ($dbc, $q);
			if (!$r) {
				echo "Database error, give to tech support: <pre>$q</pre>"; exit();
			} else {
				header("Location: " . PW99_HOME);
				exit(); // Quit the script
			}

	} else {
		header("Location: " . PW99_HOME);
		exit(); // Quit the script
	}


} else {
	header("Location: " . PW99_HOME);
	exit(); // Quit the script
}

// Include the footer file to complete the template
require('./includes/footer.html');
?>
