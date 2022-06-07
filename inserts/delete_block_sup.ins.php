<?php

// Logged in or not?
if (!isset($_SESSION['user_id'])) {
	return;
}

// Okay to view this page
$userid = $_SESSION['user_id'];
if ( ($_SERVER['REQUEST_METHOD'] === 'POST') && (isset($_POST['deleted_block'])) && (filter_var($_POST['deleted_block'], FILTER_VALIDATE_INT, array('min_range' => 1))) ) {
	$block_id = preg_replace("/[^0-9]/","", $_POST['deleted_block']);

	// $where_was_i ?
	if ((isset($_POST['where_was_i'])) && (filter_var($_POST['where_was_i'], FILTER_VALIDATE_URL))) {
		$where_was_i = filter_var($_POST['where_was_i'], FILTER_VALIDATE_URL);
	} elseif (isset($_SERVER['HTTP_REFERER'])) {
		$where_was_i = filter_var($_SERVER['HTTP_REFERER'], FILTER_VALIDATE_URL);
	} else {
		$where_was_i = 'no';
	}

	// Get the post info
	$q = "SELECT editor_id, name, code FROM blocks WHERE id='$block_id'";
	$r = mysqli_query ($dbc, $q);
	if (mysqli_num_rows($r) == 0) {
		echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
		exit(); // Quit the script
	}
	$row = mysqli_fetch_array($r);
	$editor_id = "$row[0]";
	$block_name = "$row[1]";
	$block_code = "$row[2]";
	// Get the Editor name
	$qe = "SELECT name, username FROM users WHERE id='$editor_id'";
	$re = mysqli_query ($dbc, $qe);
	$rowe = mysqli_fetch_array($re);
	$editor_name = "$rowe[0]";
	$editor_username = "$rowe[1]";

	// Delete varification form
	echo '<h2 class="lt">Delete: <i>"'.$block_name.' <small>('.$block_code.')</small>" Editor: '.$editor_name.' <small>('.$editor_username.')</small></i></h2>
	<p class="sans">Are you sure you want to delete this?</p>
	<form id="editform" class="userform" action="delete_block_sup.php" method="post" accept-charset="utf-8">';
	// $where_was_i ?
	echo (isset($where_was_i)) ? '<input type="hidden" name="where_was_i" value="'.$where_was_i.'">' : false ;
	echo '
	<input type="hidden" name="yes_delete_block" value="'.$block_id.'" />
	<input type="checkbox" required />
	<input type="submit" name="delete_block" value="Yes, delete!" id="delete_block" class="dk_button" />
	</form>
	';
} elseif ( ($_SERVER['REQUEST_METHOD'] === 'POST') && (isset($_POST['yes_delete_block'])) ) {
	$block_id = $_POST['yes_delete_block'];
	// Delete from the database
	$q = "DELETE FROM blocks WHERE id='$block_id'";
	$r = mysqli_query ($dbc, $q);
	if (mysqli_affected_rows($dbc) == 1) {
		set_switch("New block +", "Create new block", "block.php", "new_block", $userid, "newNoteButton");
		echo '<h2 class="lt">Deleted!</h2>
		<p class="noticeorange sans">The block has been permanently deleted.</p>';

	} else {
		echo "Database error!";
	}
} else {
	echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
	exit(); // Quit the script
}

// Check for $where_was_i
if ((isset($_POST['where_was_i'])) && (filter_var($_POST['where_was_i'], FILTER_VALIDATE_URL))) {
	$where_was_i = filter_var($_POST['where_was_i'], FILTER_VALIDATE_URL);
	set_button("&larr; Go back", "Return to the page that brought you here", $where_was_i, "newNoteButton");
}
