<?php

// Logged in or not?
if (!isset($_SESSION['user_id'])) {
	return;
}

// Okay to view this page
$userid = $_SESSION['user_id'];
if ( ($_SERVER['REQUEST_METHOD'] === 'POST') && (isset($_POST['deleted_user'])) && (filter_var($_POST['deleted_user'], FILTER_VALIDATE_INT, array('min_range' => 1))) ) {
	$u_id = preg_replace("/[^0-9]/","", $_POST['deleted_user']);

	// $where_was_i ?
	if ((isset($_POST['where_was_i'])) && (filter_var($_POST['where_was_i'], FILTER_VALIDATE_URL))) {
		$where_was_i = filter_var($_POST['where_was_i'], FILTER_VALIDATE_URL);
	} elseif (isset($_SERVER['HTTP_REFERER'])) {
		$where_was_i = filter_var($_SERVER['HTTP_REFERER'], FILTER_VALIDATE_URL);
	} else {
		$where_was_i = 'no';
	}

	// Get the post info
	$q = "SELECT name, username, email, editor, type FROM users WHERE id='$u_id'";
	$r = mysqli_query ($dbc, $q);
	if (mysqli_num_rows($r) == 0) {
		echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
		exit(); // Quit the script
	}
	$row = mysqli_fetch_array($r);
	$u_name = "$row[0]";
	$u_username = "$row[1]";
	$u_email = "$row[2]";
	$u_editor_id = "$row[3]";
	$u_type = "$row[4]";

	// Get the Editor name
	if ($u_editor_id != NULL) {
		$qe = "SELECT name, username, type FROM users WHERE id='$u_editor_id'";
		$re = mysqli_query ($dbc, $qe);
		$rowe = mysqli_fetch_array($re);
		$editor_name = "$rowe[0]";
		$editor_username = "$rowe[1]";
	}

	// User type pretty
	switch ($u_type) {
		case "writer":
				$delete_user_type = "Writer";
				break;
		case "observer":
				$delete_user_type = "Observer";
				break;
		case "editor":
				$delete_user_type = "Editor";
				break;
		case "supervisor":
				$delete_user_type = "Supervisor";
				break;
		case "admin":
				$delete_user_type = "Admin";
				break;
	}

	// Delete varification form
	$editor_description = ($u_editor_id != NULL) ? 'Editor: '.$editor_name.' <small>('.$editor_username.')</small>' : '(No editor!)';
	echo '<h2 class="lt">Delete '.$delete_user_type.': <i>"'.$u_name.' <small>('.$u_username.' - '.$u_email.')</small>" '.$editor_description.'</i></h2>
	<p class="sans">Are you sure you want to delete this user?</p>
	<form id="editform" class="userform" action="delete_user_sup.php" method="post" accept-charset="utf-8">';
	// $where_was_i ?
	echo (isset($where_was_i)) ? '<input type="hidden" name="where_was_i" value="'.$where_was_i.'">' : false ;
	echo '
	<input type="hidden" name="yes_delete_user" value="'.$u_id.'" />
	<input type="checkbox" required />
	<input type="submit" name="delete_user" value="Yes, delete!" id="delete_user" class="dk_button" />
	</form>
	';
} elseif ( ($_SERVER['REQUEST_METHOD'] === 'POST') && (isset($_POST['yes_delete_user'])) ) {
	$u_id = $_POST['yes_delete_user'];
	// Delete from the database
	$q = "DELETE FROM users WHERE id='$u_id'";
	$r = mysqli_query ($dbc, $q);
	if (mysqli_affected_rows($dbc) == 1) {
		echo '<h2 class="lt">User deleted!</h2>
		<p class="noticeorange sans">The user has been permanently deleted.</p>';

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
