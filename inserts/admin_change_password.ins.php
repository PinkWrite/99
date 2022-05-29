<?php

// Making sure we got here the right way
if ( ($_SERVER['REQUEST_METHOD'] === 'POST') && (isset($_POST['opened_by'])) && ($_POST['opened_by'] == $userid) ) {

	// For storing errors
	$pass_errors = array();

	// GET the user being edited
	if ( (isset($_GET['v'])) && (filter_var($_GET['v'], FILTER_VALIDATE_INT, array('min_range' => 1))) ) {
		$u_id = preg_replace("/[^0-9]/","", $_GET['v']);
	}

// If it's a POST request, handle the form submission
} elseif ( ($_SERVER['REQUEST_METHOD'] === 'POST') && (!isset($_POST['opened_by'])) ) {

	// Check for a password and match against the confirmed password
	if ( (isset($_POST['pass1'])) && (isset($_POST['pass2'])) && ($_POST['pass1'] != '') && ($_POST['pass2'] != '') ) {
		if (preg_match ('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])[0-9A-Za-z!@#$%&*+-]{6,32}$/', $_POST['pass1']) ) {
			if ($_POST['pass1'] == $_POST['pass2']) {
				$password = mysqli_real_escape_string ($dbc, $_POST['pass1']);
			} else {
				$reg_errors['pass2'] = 'Your passwords did not match!';
			}
		} else {
			$reg_errors['pass1'] = 'Please enter a valid password!';
		}
	} else {
		$reg_errors['pass1'] = 'Please enter a valid password!';
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

		// Update the password
		$q = "UPDATE users SET pass='"  .  password_hash($p, PASSWORD_BCRYPT) .  "' WHERE id='$u_id' LIMIT 1";
		if ($r = mysqli_query ($dbc, $q)) { // If it ran OK.

			// Fetch user info
			$q = "SELECT username, name FROM users WHERE id='$u_id'";
			$r = mysqli_query ($dbc, $q);
			if (mysqli_num_rows($r) == 1) { // Valid username
				$row = mysqli_fetch_array ($r, MYSQLI_NUM);
				$username = $row[0];
				$name = $row[1];
			} else {
				echo $q; exit;
				echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
				exit(); // Quit the script
			}

			// Let the user know the password has been changed
			echo "<p class=\"sans noticegreen\">Password changed for $name <small>($username)</small>.</p>";
			// Include the HTML footer
			include('./includes/footer.html');
			exit();

		} else { // If it did not run OK

			echo 'We had a database issue on our end. Contact the website IT team.';

		} // End of the SQL new password update

	} // End of error checks

} // End of the form submission conditional

if ( (isset($_SESSION['user_id'])) && ($_SERVER['REQUEST_METHOD'] === 'POST') ) {
// Need the form functions script, which defines create_form_input()
require_once('./includes/form_functions.inc.php');

if ($usr_type == "Supervisor") {
	$sql_where = "id='$u_id' AND NOT (type='supervisor' OR type='admin')";
} elseif ($usr_type == "Admin") {
	$sql_where = "id='$u_id'";
} else {
	echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
	exit(); // Quit the script
}

// Fetch user info
$q = "SELECT username, email, name FROM users WHERE $sql_where";
$r = mysqli_query ($dbc, $q);
if (mysqli_num_rows($r) == 1) { // Valid username
	$row = mysqli_fetch_array ($r, MYSQLI_NUM);
	$username = $row[0];
	$email = $row[1];
	$name = $row[2];
} else {
	echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
	exit(); // Quit the script
}

echo "<h3>Change User Password for: $name <small>($username - $email)</small></h3>
<form action=\"$rformaction\" method=\"post\" accept-charset=\"utf-8\">
<input type=\"hidden\" name=\"user_id\" value=\"$u_id\">";

	echo "<p><label class =\"sans\" for=\"pass1\">New Password<br /><small class =\"sans\">6-32 characters, one lowercase letter, one uppercase letter, one number, special characters allowed: ! @ # $ % ! & * + -</small></label><br /><br />";
	create_form_input('pass1', 'password', $pass_errors, '');
	echo "</p>
	<p><label class =\"sans\" for=\"pass2\">Confirm New Password</label><br /><br />";
	create_form_input('pass2', 'password', $pass_errors, '');
	echo "</p>
	<input type=\"submit\" name=\"submit_button\" value=\"Change &rarr;\" id=\"submit_button\" class=\"formbutton\" />
</form>";
} else {
	echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
	exit(); // Quit the script
}
