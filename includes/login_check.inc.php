<?php

// Reject anyone who doesn't have an IP
if (isset($_SERVER['REMOTE_ADDR'])) {
	if (filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
    $user_ip = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
	} elseif (filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
		$user_ip = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
	} else {
		header("Location: " . PW99_HOME);
		exit(); // Quit the script
	}

} else {
	header("Location: " . PW99_HOME);
	exit(); // Quit the script
}

// Recently blocked from this IP?
$timeNow = date("Y-m-d H:i:s");
$timeNowEpoch = strtotime($timeNow);
$lastAllowedFailEpoch = ($timeNowEpoch - (60 * 60));
$q = "SELECT id FROM clickathon WHERE ip='$user_ip' AND time_epoch > '$lastAllowedFailEpoch'";
$r = mysqli_query ($dbc, $q);
if (mysqli_num_rows($r) >= 1) {
	$ip_blocked = true;
} else {
	$ip_blocked = false;
}

// Don't even start if being hacked
if ( ((isset($_SESSION['clickathon_count'])) && ($_SESSION['clickathon_count'] > 5) && ((isset($_SESSION['clickathon_time'])) && ($_SESSION['clickathon_time'] > $lastAllowedFailEpoch))) || $ip_blocked == true) {
	// Message
	echo '<p class="noticered">Too many login tries from '.$user_ip.'. Try again later.1</p>';
	return;
}

// Array for recording errors
$login_errors = array();

// This whole form is only for POST action
if (($_SERVER['REQUEST_METHOD'] == 'POST') && (isset($_POST['username']))) {

	// Validate the username:
	if ((!empty($_POST['username'])) && (preg_match ('/^[A-Z0-9]{6,32}$/i', $_POST['username']))) {
		$username = mysqli_real_escape_string ($dbc, $_POST['username']);
		// Clickathon string
		$_SESSION['clickathon_usernames'] = (empty($_SESSION['clickathon_usernames'])) ? $username : $_SESSION['clickathon_usernames'] . ', ' . $username ;
	} else {
		$login_errors['username'] = 'Please enter a valid username!';
		// Clickathon
		$_SESSION['clickathon_count'] = (empty($_SESSION['clickathon_count'])) ? 1 : $_SESSION['clickathon_count'] + 1 ;
		$_SESSION['clickathon_time'] = $timeNowEpoch;
		$_SESSION['clickathon_usernames'] = (empty($_SESSION['clickathon_usernames'])) ? $username : $_SESSION['clickathon_usernames'] . ', TRIED-INVALID-CHARACTERS' ;
	}

	// Validate the password
	if (!empty($_POST['pass'])) {
		$p = mysqli_real_escape_string ($dbc, $_POST['pass']);
	} else {
		$login_errors['pass'] = 'Please enter your password!';
	}

	if (empty($login_errors)) { // OK to proceed!

		// Verify the password, continue other checks if TRUE
		$q = "SELECT pass FROM users WHERE username='$username'";
		$r = mysqli_query ($dbc, $q);
		$row = mysqli_fetch_array ($r, MYSQLI_NUM);
		$hp = $row[0];
		if (password_verify($p, $hp)) {

			// Query the database for other credentials
			$q = "SELECT id, username, type, name, blocks, level FROM users WHERE username='$username'";
			$r = mysqli_query ($dbc, $q);

			if (mysqli_num_rows($r) == 1) { // A match was made

				// Clear the clickathon
				unset($_SESSION['clickathon_count']);
				unset($_SESSION['clickathon_usernames']);
				unset($_SESSION['clickathon_time']);

				// Get the data
				$row = mysqli_fetch_array ($r, MYSQLI_NUM);

				// Store the data in a session
				$_SESSION['user_id'] = $row[0];
				$_SESSION['username'] = $row[1];
				$_SESSION['type'] = $row[2];
				$_SESSION['name'] = $row[3];
				$_SESSION['blocks'] = $row[4];
				$_SESSION['level'] = $row[5];

				// Set all types false for valid testing where this is used
				$_SESSION['user_is_admin'] = false;
				$_SESSION['user_is_supervisor'] = false;
				$_SESSION['user_is_editor'] = false;
				$_SESSION['user_is_observer'] = false;
				$_SESSION['user_is_writer'] = false;

				// Type of account
				if ($row[2] == 'admin') $_SESSION['user_is_admin'] = true;
				if ($row[2] == 'supervisor') $_SESSION['user_is_supervisor'] = true;
				if ($row[2] == 'editor') $_SESSION['user_is_editor'] = true;
				if ($row[2] == 'observer') $_SESSION['user_is_observer'] = true;
				if ($row[2] == 'writer') $_SESSION['user_is_writer'] = true;

			} else { // No match was made
				$login_errors['login'] = 'The username and password do not match those on file.';
				// Clickathon
				$_SESSION['clickathon_count'] = (empty($_SESSION['clickathon_count'])) ? 1 : $_SESSION['clickathon_count'] + 1 ;
				$_SESSION['clickathon_time'] = $timeNowEpoch;
			}
		} else { // No match was made
			$login_errors['login'] = 'The username and password do not match those on file.';
			// Clickathon
			$_SESSION['clickathon_count'] = (empty($_SESSION['clickathon_count'])) ? 1 : $_SESSION['clickathon_count'] + 1 ;
			$_SESSION['clickathon_time'] = $timeNowEpoch;
		}

		// Stop here after 5 fails
		if ( ((isset($_SESSION['clickathon_count'])) && ($_SESSION['clickathon_count'] > 5)) && ((isset($_SESSION['clickathon_time'])) && ($_SESSION['clickathon_time'] > $lastAllowedFailEpoch)) ) {

			// SQL entry
			$clickathon_usernames = $_SESSION['clickathon_usernames'];
			$q = "INSERT INTO clickathon (username_list, ip, time_epoch) VALUES ('$clickathon_usernames', '$user_ip', '$timeNowEpoch')";
			$r = mysqli_query ($dbc, $q);

			// Message
			echo '<h3 class="noticered">Too many login tries from '.$user_ip.'. Try again later.3</h3>';
			return;
		}

	} // End of $login_errors IF
}
