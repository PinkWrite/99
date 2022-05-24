<?php

// Making sure we got here the right way
if ( ($_SERVER['REQUEST_METHOD'] === 'POST') && (isset($_POST['opened_by'])) && ($_POST['opened_by'] == $userid) ) {

	// $where_was_i ?
	if (isset($_SERVER['HTTP_REFERER'])) {
		$where_was_i = filter_var($_SERVER['HTTP_REFERER'], FILTER_VALIDATE_URL);
	}

	// For storing errors
	$pass_errors = array();

	// GET the user being edited
	if ( (isset($_GET['v'])) && (filter_var($_GET['v'], FILTER_VALIDATE_INT, array('min_range' => 1))) ) {
		$u_id = preg_replace("/[^0-9]/","", $_GET['v']);
	}

// If it's a POST request, handle the form submission
} elseif (($_SERVER['REQUEST_METHOD'] === 'POST') && (!isset($_POST['opened_by']))) {

	// For storing errors
	$pass_errors = array();

	// Check for a username
	if (isset($_POST['username'])) {
		if (preg_match('/^[A-Za-z0-9]{6,32}$/i', $_POST['username'])) {
			$username = mysqli_real_escape_string($dbc, $_POST['username']);
		} else {
			$username = "";
			$pass_errors['username'] = 'Please enter a username!';
		}
	} else {
		$pass_errors['username'] = 'Please enter a username!';
	}

	// Check for a name
	if ( (isset($_POST['name'])) && ($_POST['name'] != '') ) {
		if (preg_match('/^[A-Z \'.-]{1,80}$/i', $_POST['name'])) {
			$name = mysqli_real_escape_string($dbc, $_POST['name']);
		} else {
			$name = "";
			$pass_errors['name'] = 'Please enter a username, 6-32 characters!';
		}
	} else {
		$pass_errors['name'] = 'Please enter a username, 6-32 characters!';
	}

	// Check for an email and match against the confirmed email
	if ( ($_POST['email1'] != '') || ($_POST['email2'] != '') ) {
		if (filter_var($_POST['email1'], FILTER_VALIDATE_EMAIL)) {
			if ($_POST['email1'] == $_POST['email2']) {
				$email1 = mysqli_real_escape_string($dbc, $_POST['email1']);
				$email2 = mysqli_real_escape_string($dbc, $_POST['email1']);
			} else {
				$pass_errors['email2'] = 'Your email addresses did not match!';
			}
		} else {
			$pass_errors['email1'] = 'Please enter a valid email address, 90 characters max!';
		}
	} else {
		$pass_errors['email1'] = 'Please enter a valid email address, 90 characters max!';
	}

	// Check for an editor
	if ( (isset($_POST['editor'])) && ($_POST['editor'] != '') ) {
		if (filter_var($_POST['editor'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
			$editor = mysqli_real_escape_string($dbc, preg_replace("/[^0-9]/","", $_POST['editor']));
		} else {
			$pass_errors['editor'] = 'Please enter a username, 6-32 characters!';
		}
	}

	// Check for a user type
	if ( ($usr_type == "Admin") && (isset($_POST['type'])) && ($_POST['type'] != '') ) {
		if ($_POST['type'] == 'writer') {
			$type_sql = ", type='writer'";
		} elseif ($_POST['type'] == 'observer') {
			$type_sql = ", type='observer'";
		} elseif ($_POST['type'] == 'editor') {
			$type_sql = ", type='editor'";
		} elseif ($_POST['type'] == 'supervisor') {
			$type_sql = ", type='supervisor'";
		} elseif ($_POST['type'] == 'admin') {
			$type_sql = ", type='admin'";
		} else {
			$type_sql = "";
		}
	} else {
		$type_sql = "";
	}

	// Check for a user id
	if ( (isset($_POST['user_id'])) && ($_POST['user_id'] != '') ) {
		if (filter_var($_POST['user_id'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
			$u_id = mysqli_real_escape_string($dbc, preg_replace("/[^0-9]/","", $_POST['user_id']));
		} else {
			echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
			exit(); // Quit the script
		}
	} else {
		echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
		exit(); // Quit the script
	}

	if (empty($pass_errors)) { // If everything is OK

		// Update the database
		$q = "UPDATE users SET email='$email1', name='$name', username='$username', editor='$editor' $type_sql WHERE id='$u_id'";
		if ($r = mysqli_query ($dbc, $q)) { // If it ran OK.

			// Let the user know the email has been changed
			echo "<p class=\"sans noticegreen\">Account saved.</p>";

			// Check for $where_was_i
			if ((isset($_POST['where_was_i'])) && (filter_var($_POST['where_was_i'], FILTER_VALIDATE_URL))) {
				$where_was_i = filter_var($_POST['where_was_i'], FILTER_VALIDATE_URL);
				set_button("&larr; Go back", "Return to the page that brought you here", $where_was_i, "newNoteButton");
			}

		} else { // If it did not run OK

			echo 'We had a database issue on our end. Contact the website IT team.';

		} // End of the SQL

	} // End of error checks

} // End of the form submission conditional

if ($usr_type == "Supervisor") {
	$sql_where = "id='$u_id' AND NOT (type='supervisor' OR type='admin')";
} elseif ($usr_type == "Admin") {
	$sql_where = "id='$u_id'";
} else {
	echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
	exit(); // Quit the script
}

// Fetch user info
$q = "SELECT username, email, name, editor, type FROM users WHERE $sql_where";
$r = mysqli_query ($dbc, $q);
if (mysqli_num_rows($r) == 1) { // Valid username
	$row = mysqli_fetch_array ($r, MYSQLI_NUM);
	$username = $row[0];
	$email1 = $row[1];
	$email2 = $row[1];
	$name = $row[2];
	$editor = $row[3];
	$type = $row[4];
} else {
	echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
	exit(); // Quit the script
}

if ( (isset($_SESSION['user_id'])) && ($_SERVER['REQUEST_METHOD'] === 'POST') ) {
// Need the form functions script, which defines create_form_input()
require_once('./includes/form_functions.inc.php');
echo "<h3>Change user account info</h3>
<form action=\"$rformaction\" method=\"post\" accept-charset=\"utf-8\">
<input type=\"hidden\" name=\"user_id\" value=\"$u_id\">";
	// $where_was_i ?
	echo (isset($where_was_i)) ? "<input type=\"hidden\" name=\"where_was_i\" value=\"$where_was_i\">" : false ;
	// Username
	echo "<p><label class =\"sans\" for=\"username\">Username</label><br /><br />";
	create_form_input('username', 'text', $pass_errors, $username);
	echo "</p>";

	// Name
	echo "<p><label class =\"sans\" for=\"name\">Name</label><br /><br />";
	create_form_input('name__o', 'text', $pass_errors, $name);
	echo "</p>";

	// Email
	echo "<p><label class =\"sans\" for=\"email1\">Email</label><br /><br />";
	create_form_input('email1__o', 'email', $pass_errors, $email1);
	echo "</p>
	<p><label class =\"sans\" for=\"email2\">Double-Check Email</label><br /><br />";
	create_form_input('email2__o', 'email', $pass_errors, $email2);
	echo "</p>";

	// Editor
	echo '<p><label class="sans" for="editor"';
	if (array_key_exists("editor", $pass_errors)) {
		echo 'class="error noticered sans" >Editor: <span class="error noticered sans">' . $pass_errors['editor'] . '</span></label>';
	} else {
		echo '>Editor</label><br /><br />';
	}
	echo '
	<select class="formselect" name="editor" id="editor" onchange="onNavWarn();" onkeyup="onNavWarn();">';
	// List available editors
	$qe = "SELECT type, username, name, id FROM users WHERE type='editor' OR type='supervisor' OR type='admin' ORDER BY type='editor', type='supervisor', type='admin'";
	$re = mysqli_query ($dbc, $qe);
	while ($rowe = mysqli_fetch_array($re)) {
		$editor_type = "$rowe[0]";
		$editor_username = "$rowe[1]";
		$editor_name = "$rowe[2]";
		$editor_id = $rowe[3];

		echo '<option value="'.$editor_id.'"';

		if ( (isset($editor_id)) && ($editor_id == $editor) ) {
			echo ' selected';
		}
		echo '>'.$editor_name.' ('.$editor_type.' - <small>'.$editor_username.'</small>)</option>';

	}
	echo '</select></p>';

	// Type
	if (($usr_type == "Admin") && ($u_id != $userid)) { // Admins can't change themselves
		echo '<p><label class="sans" for="type"';
		if (array_key_exists("type", $pass_errors)) {
			echo 'class="error noticered sans" >Editor: <span class="error noticered sans">' . $pass_errors['type'] . '</span></label>';
		} else {
			echo '>User type</label><br /><br />';
		}
		echo '
		<select class="formselect" name="type" id="type" onchange="onNavWarn();" onkeyup="onNavWarn();">';
			echo '<option value="writer"';
			echo ( (isset($type)) && ($type == 'writer') ) ? ' selected' : false ;
			echo '>Writer</option>';
			echo '<option value="observer"';
			echo ( (isset($type)) && ($type == 'observer') ) ? ' selected' : false ;
			echo '>Observer</option>';
			echo '<option value="editor"';
			echo ( (isset($type)) && ($type == 'editor') ) ? ' selected' : false ;
			echo '>Editor</option>';
			echo '<option value="supervisor"';
			echo ( (isset($type)) && ($type == 'supervisor') ) ? ' selected' : false ;
			echo '>Supervisor</option>';
			echo '<option value="admin"';
			echo ( (isset($type)) && ($type == 'admin') ) ? ' selected' : false ;
			echo '>Admin</option>';
		echo '</select>';
	}

	// Finish the form
	echo '
	<p><input type="submit" name="submit_button" value="Save" id="submit_button" class="formbutton" /></p>
</form>';

// Delete button
echo '<br><br>';
set_switch("Delete &rarr;", "Delete this user", "delete_user_sup.php", "deleted_user", $u_id, "editNoteButton");

} else {
	echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
	exit(); // Quit the script
}
