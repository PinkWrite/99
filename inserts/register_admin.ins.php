<?php

// For storing registration errors
$reg_errors = array();

// Check for a form submission
if ( ($_SERVER['REQUEST_METHOD'] == 'POST') && (isset($_POST['register_new'])) ) {

	// User type
	if (isset($_POST['utype'])) {
		$type = $_POST['utype'];
	} else {
		$reg_errors['utype'] = 'Please choose a type!';
	}

	// Check for a name
	if (preg_match ('/^[A-Z \'.-]{1,80}$/i', $_POST['name'])) {
		$name = mysqli_real_escape_string ($dbc, $_POST['name']);
	} else {
		$reg_errors['name'] = 'Please enter your name, only letters and hyphens, 80 characters max!';
	}

	// Check for a username
	if (preg_match ('/^[A-Z0-9]{6,32}$/i', $_POST['username'])) {
		$username = mysqli_real_escape_string ($dbc, $_POST['username']);
	} else {
		$reg_errors['username'] = 'Please enter a valid username, 6-32 characters!';
	}

	// Check for an email and match against the confirmed email
	if (filter_var($_POST['email1'], FILTER_VALIDATE_EMAIL)) {
		if ($_POST['email1'] == $_POST['email2']) {
			$email = mysqli_real_escape_string ($dbc, $_POST['email1']);
		} else {
			$reg_errors['email2'] = 'Your email addresses did not match!';
		}
	} else {
		$reg_errors['email1'] = 'Please enter a valid email address, 90 characters max!';
	}

	// Check for a password and match against the confirmed password
	if (preg_match ('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])[0-9A-Za-z!@#$%+-]{6,32}$/', $_POST['pass1']) ) {
		if ($_POST['pass1'] == $_POST['pass2']) {
			$password = mysqli_real_escape_string ($dbc, $_POST['pass1']);
		} else {
			$reg_errors['pass2'] = 'Your passwords did not match!';
		}
	} else {
		$reg_errors['pass1'] = 'Please enter a valid password!';
	}

	if (empty($reg_errors)) { // If everything's OK...

		// Make sure the email address and username are available
		$q = "SELECT email, username FROM users WHERE email='$email' OR username='$username'";
		$r = mysqli_query ($dbc, $q);

		// Get the number of rows returned
		$rows = mysqli_num_rows($r);

		if ($rows == 0) { // No dups!

			// Add the user to the database
			$q = "INSERT INTO users (type, username, email, pass, name, status, blocks, observing, groups) VALUES ('$type', '$username', '$email', '"  .  password_hash($password, PASSWORD_BCRYPT) .  "', '$name', 'active', 'null', 'null', 'null')";
			$r = mysqli_query ($dbc, $q);

			if (mysqli_affected_rows($dbc) == 1) { // If it ran OK

				// Update any Editors to themselves
				if ( ($type = 'editor') || ($type = 'admin') || ($type = 'supervisor') ) {
					$editor_id = $dbc->insert_id;
				} else {
					$editor_id = $userid;
				}
				$q = "UPDATE users SET editor='$editor_id' WHERE id='$editor_id'";
				$r = mysqli_query ($dbc, $q);
				if (mysqli_affected_rows($dbc) != 1) {
					echo "Database error.";
				}

				/*
				// Send the registration email
				$from = '"'.$site_from_email_name.'" <'.$site_from_email.'>';
				$to = '"'.$name.'" <'.$email.'>';
				$subject = "Registration: $siteTitle";
				$message = "<html><p>Thank you for registering at $siteTitle.</p><br />Username: $username<br /><p>You agreed to our Terms & Conditions, which may change and you will receive an email when you do. You also agreed that all sales are final and no refunds are given under any circumstances.</p><br /><a title=\"PinkWrite 99\" href=\"https://pacificdailyads.com\">pacificdailyads.com</a></html>";
				$headers .= 'To: ' . $to . "\r\n";
				$headers .= 'From: ' . $from . "\r\n";
				$headers .= 'Bcc: ' . $site_bcc_email . "\r\n";
				mail($to,$subject,$message, $headers);
				*/

				// Display a thanks message
				echo '<h2>Success!</h2><p class="sans">User "'.$name.'" has been registered.</p>';

				// Unset the variables
				unset ($type);
				unset ($username);
				unset ($email);
				unset ($name);
				unset ($password);
				unset ($project);
				unset ($_POST['type']);
				unset ($_POST['name']);
				unset ($_POST['project']);
				unset ($_POST['username']);
				unset ($_POST['email1']);
				unset ($_POST['email2']);
				unset ($_POST['pass1']);
				unset ($_POST['pass2']);
				unset ($_POST['register_new']);
				unset ($_POST['submit_button']);


			} else { // If it did not run OK
				echo 'We had a database issue on our end. Contact the website IT team.';
			}

		} else { // The email address or username is not available

			if ($rows == 2) { // Both are taken

				$reg_errors['email1'] = 'This email address has already been registered. If you have forgotten your password, use the link at right to have your password sent to you.';
				$reg_errors['username'] = 'This username has already been registered. Please try another.';

			} else { // One or both may be taken

				// Get row
				$row = mysqli_fetch_array($r, MYSQLI_NUM);

				if( ($row[0] == $_POST['email1']) && ($row[1] == $_POST['username'])) { // Both match
					$reg_errors['email1'] = 'This email address has already been registered. If you have forgotten your password, use the link at right to have your password sent to you.';
					$reg_errors['username'] = 'This username has already been registered with this email address. If you have forgotten your password, use the link at right to have your password sent to you.';
				} elseif ($row[0] == $_POST['email1']) { // Email match
					$reg_errors['email1'] = 'This email address has already been registered. <a href=\"forgot_password.php\" align=\"right\">Forgot your password?</a>';
				} elseif ($row[1] == $_POST['username']) { // Username match
					$reg_errors['username'] = 'This username has already been registered. Please try another.';
				}

			} // End of $rows == 2 ELSE

		} // End of $rows == 0 IF

	} // End of empty($reg_errors) IF

} // End of the main form submission conditional

// Make sure the user is not logged in or just registered
if ( (isset($_SESSION['user_id'])) && ($_SERVER['REQUEST_METHOD'] == 'POST') && (isset($_POST['registrar'])) && ($_POST['registrar'] == $userid) ) {

// define create_form_input()
require_once('./includes/form_functions.inc.php');
echo "
<form id=\"registerform\" class=\"userform\" action=\"$rformaction\" method=\"post\" accept-charset=\"utf-8\">
<input type=\"hidden\" name=\"register_new\" value=\"submitted\" />
<input type=\"hidden\" name=\"registrar\" value=\"$userid\" />

		<p><label class =\"sans\" for=\"utype\"><b>Type</b></label><br /><br />
		<select class=\"formselect\" name=\"utype\" required>";
		if ($_SESSION['user_is_admin'] == true) {
			echo "
			<option value=\"admin\">Admin</option>
		  <option value=\"supervisor\">Supervisor</option>
			";
		}
echo "
			<option value=\"editor\" selected>Editor</option>
			<option value=\"writer\">Writer</option>
			<option value=\"observer\">Observer</option>
			";

echo "
		</select>
		</p>

		<p><label class=\"sans\" for=\"name\"><b>Name</b></label><br /><br />";
		create_form_input('name', 'text', $reg_errors, '');
		echo "</p>

		<p><label class=\"sans\" for=\"username\"><b>Username</b><br /><small class =\"sans\">6-32 characters, only letters and numbers, case doesn't matter</small></label><br /><br />";
		create_form_input('username', 'text', $reg_errors, '');
		echo "</p>

		<p><label class=\"sans\" for=\"email1\"><b>Email</b></label><br /><br />";
		create_form_input('email1', 'email', $reg_errors, '');
		echo "</p>
		<p><label class=\"sans\" for=\"email2\"><b>Double-Check Email</b></label><br /><br />";
		create_form_input('email2', 'email', $reg_errors, '');
		echo "</p>

		<p><label class=\"sans\" for=\"pass1\"><b>Password</b><br /><small class =\"sans\">6-32 characters, one lowercase letter, one uppercase letter, one number, special characters allowed: +-!@#$%</small></label><br /><br />";
		create_form_input('pass1', 'password', $reg_errors, '');
		echo "</p>
		<p><label class=\"sans\" for=\"pass2\"><b>Confirm Password</b></label><br /><br />";
		create_form_input('pass2', 'password', $reg_errors, '');
		echo "</p>";
		// Disclaimers
		echo"
		<input type=\"submit\" name=\"submit_button\" value=\"Register &rarr;\" id=\"submit_button\" class=\"formbutton\" />

</form>";
} else {
	echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
	exit(); // Quit the script
}
