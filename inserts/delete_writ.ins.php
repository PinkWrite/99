<?php

// Logged in or not?
if (!isset($_SESSION['user_id'])) {
	return;
}

// Okay to view this page
$userid = $_SESSION['user_id'];
if ( ($_SERVER['REQUEST_METHOD'] === 'POST') && (isset($_POST['deleted_writ'])) && (filter_var($_POST['deleted_writ'], FILTER_VALIDATE_INT, array('min_range' => 1))) ) {
	$writ_id = preg_replace("/[^0-9]/","", $_POST['deleted_writ']);
	$writer_id = $userid;

	// $where_was_i ?
	if ((isset($_POST['where_was_i'])) && (filter_var($_POST['where_was_i'], FILTER_VALIDATE_URL))) {
		$where_was_i = filter_var($_POST['where_was_i'], FILTER_VALIDATE_URL);
	} elseif (isset($_SERVER['HTTP_REFERER'])) {
		$where_was_i = filter_var($_SERVER['HTTP_REFERER'], FILTER_VALIDATE_URL);
	} else {
		$where_was_i = 'no';
	}

	// Get the post info
	$q = "SELECT title, work, block FROM writs WHERE id='$writ_id' AND writer_id='$writer_id'";
	$r = mysqli_query ($dbc, $q);
	if (mysqli_num_rows($r) == 0) {
		echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
		exit(); // Quit the script
	}
	$row = mysqli_fetch_array($r);
	$writ_title = "$row[0]";
	$writ_work = "$row[1]";
	$block_id = "$row[2]";
	// Get the Block info
	$qb = "SELECT editor_id, name, code FROM blocks WHERE id='$block_id'";
	$rb = mysqli_query ($dbc, $qb);
	$rowe = mysqli_fetch_array($rb);
	$block_editor_id = "$rowe[0]";
	$block_name = "$rowe[1]";
	$block_code = "$rowe[2]";
	// Get the Editor name
	$qe = "SELECT name FROM users WHERE id='$block_editor_id'";
	$re = mysqli_query ($dbc, $qe);
	$rowe = mysqli_fetch_array($re);
	$editor_name = "$rowe[0]";

	// Delete varification form
	echo '<h2 class="lt">Delete: <i>"'.$writ_title.' ('.$writ_work.')" Block: '.$block_name.' ('.$block_code.') Editor: '.$editor_name.'</i></h2>
	<p class="sans">Are you sure you want to delete this?</p>
	<form id="editform" class="userform" action="delete_writ.php" method="post" accept-charset="utf-8">';
	// $where_was_i ?
	echo (isset($where_was_i)) ? '<input type="hidden" name="where_was_i" value="'.$where_was_i.'">' : false ;
	echo '
	<input type="hidden" name="yes_delete_writ" value="'.$writ_id.'" />
	<input type="checkbox" required />
	<input type="submit" name="delete_writ" value="Yes, delete!" id="delete_writ" class="dk_button" />
	</form>
	';
} elseif ( ($_SERVER['REQUEST_METHOD'] === 'POST') && (isset($_POST['yes_delete_writ'])) ) {
	$writ_id = $_POST['yes_delete_writ'];
	// Delete from the database
	$q = "DELETE FROM writs WHERE id='$writ_id'";
	$r = mysqli_query ($dbc, $q);
	if (mysqli_affected_rows($dbc) == 1) {
		set_switch("New +", "Start writing something new", "writ.php", "new_writ", $writer_id, "set_gray");
		echo '<h2 class="lt">Deleted!</h2>
		<p class="noticeorange sans">The writ has been permanently deleted.</p>';

		// Check for $where_was_i
		if ((isset($_POST['where_was_i'])) && (filter_var($_POST['where_was_i'], FILTER_VALIDATE_URL))) {
			$where_was_i = filter_var($_POST['where_was_i'], FILTER_VALIDATE_URL);
			set_button("&larr; Go back", "Return to the page that brought you here", $where_was_i, "newNoteButton");
		}

	} else {
		echo "Database error!";
	}
} else {
	echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
	exit(); // Quit the script
}
