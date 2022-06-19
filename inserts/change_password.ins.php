<?php

// For storing errors
$reg_errors = array();

// If it's a POST request, handle the form submission
if ( ($_SERVER['REQUEST_METHOD'] == 'POST') && (isset($_POST['pass_update'])) ) {

	// Check for the existing password
	if (!empty($_POST['current'])) {
		$current = mysqli_real_escape_string ($dbc, $_POST['current']);
	} else {
		$reg_errors['current'] = 'Please enter your current password!';
	}

	// Check for a password and match against the confirmed password
	if (preg_match ('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])[0-9A-Za-z!@#$%+-]{6,32}$/', $_POST['pass1']) ) {
		if ($_POST['pass1'] == $_POST['pass2']) {
			$p = mysqli_real_escape_string ($dbc, $_POST['pass1']);
		} else {
			$reg_errors['pass2'] = 'Your password did not match the confirmed password!';
		}
	} else {
		$reg_errors['pass1'] = 'Please enter a valid password!';
	}

	if (empty($reg_errors)) { // If everything is OK

			// Current password
			$q = "SELECT pass FROM users WHERE id={$_SESSION['user_id']}";
			$r = mysqli_query ($dbc, $q);
			$row = mysqli_fetch_array ($r, MYSQLI_NUM);
			$hp = $row[0];
			if (password_verify($current, $hp)) { // Correct
				$allwell_dochange = true;
			} else {
				$reg_errors['current'] = 'Your current password is incorrect!';
			} // End of current password ELSE

		if ($allwell_dochange == true) {
			// Update the password
			$q = "UPDATE users SET pass='"  .  password_hash($p, PASSWORD_BCRYPT) .  "' WHERE id={$_SESSION['user_id']} LIMIT 1";
			if ($r = mysqli_query ($dbc, $q)) { // If it ran OK.

				// Send changed info email
				include('includes/confirm_change.inc.php');

				// Let the user know the password has been changed
				echo '<h3 class="noticegreen">Your password has been changed.</h3>';
				// Process the email confirmation
				include('includes/confirm_password.inc.php');
				// Include the HTML footer
				include('./includes/footer.html');
				exit();

			} else { // If it did not run OK

				trigger_error('Darnitall! We had a database issue on our end and your password could not be changed. Try again some other time.');

			} // End of the SQL new password update

		} // End of $allwell_dochange

	} // End of error checks

} // End of the form submission conditional

if ( (isset($_SESSION['user_id'])) && ($_SERVER['REQUEST_METHOD'] == 'POST') && (isset($_POST['user'])) && ($_POST['user'] == $userid)  ) {

// Need the form functions script, which defines create_form_input()
require_once('./includes/form_functions.inc.php');
echo "
<form action=\"$rformaction\" method=\"post\" accept-charset=\"utf-8\">
<input type=\"hidden\" name=\"pass_update\" value=\"submitted\" />
<input type=\"hidden\" name=\"user\" value=\"$userid\" />";
	// Current password
	echo "<p><label class =\"sans\" for=\"pass1\"><b>Current Password</b></label><br /><br />";
	create_form_input('current', 'password', $reg_errors, '');
	echo "</p>";
	// New password
	echo "<p><label class =\"sans\" for=\"pass1\"><b>New Password</b><br /><small class=\"sans lt\">6-32 characters, one lowercase letter, one uppercase letter, one number, special characters allowed: +-!@#$%</small></label><br /><br />";
	create_form_input('pass1', 'password', $reg_errors, '');
	echo "</p>
	<p><label class =\"sans\" for=\"pass2\"><b>Confirm New Password</b></label><br /><br />";
	create_form_input('pass2', 'password', $reg_errors, '');
	echo "</p>
	<input type=\"submit\" name=\"submit_button\" value=\"Change &rarr;\" id=\"submit_button\" class=\"formbutton\" />
</form>";
} else {
	echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
	exit(); // Quit the script
}
