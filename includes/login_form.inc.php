<?php

// Clickathon?
if ( ((isset($_SESSION['clickathon_count'])) && ($_SESSION['clickathon_count'] > 5) && ((isset($_SESSION['clickathon_time']))
&&   ((isset($lastAllowedFailEpoch)) && ($_SESSION['clickathon_time'] > $lastAllowedFailEpoch)) ))
||   ((isset($ip_blocked)) && ($ip_blocked == true))) {
	return;
}

// Create an empty error array if it doesn't already exist
if (!isset($login_errors)) $login_errors = array();

// Need the form functions script, which defines create_form_input()
// The file may already have been included (e.g., if this is register.php or forgot_password.php)
require_once('./includes/form_functions.inc.php');
echo "<h3>Log In</h3>
<form id=\"loginform\" class=\"userform\" action=\"$lformaction\" method=\"post\" accept-charset=\"utf-8\">
<input type=\"hidden\" name=\"loginform\" value=\"submitted\" />
<p>";
if (array_key_exists('login', $login_errors)) {
	echo '<span class="noticered sans">' . $login_errors['login'] . '</span><br /><br />';
	}
echo "<label class =\"sans\" for=\"username\"><strong>Username</strong></label><br /><br />";
create_form_input('username', 'text', $login_errors, '');
echo "<br /><br /><label class =\"sans\" for=\"pass\"><strong>Password</strong></label><br /><br />";
create_form_input('pass', 'password', $login_errors, '');
//echo " <a href=\"password_reset.php\" align=\"right\">Forgot?</a>";
echo "<br /><br /><input type=\"submit\" value=\"Login &rarr;\" class=\"formbutton\"></p>
</form>";
