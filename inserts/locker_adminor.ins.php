<?php

// Logged in or not?
if (!isset($_SESSION['user_id'])) {
	return;
}

if ($_SESSION['user_is_admin'] == true) {
	set_switch("Admin Register", "Register a new user", "register_admin.php", "registrar", $userid, "set_gray");
} elseif ($_SESSION['user_is_supervisor'] == true) {
	set_switch("Supervisor Register", "Register a new user", "register_admin.php", "registrar", $userid, "set_gray");
}

echo '<br><br>';

get_switch("Login Fails", "View repeat-failed login attempts", "login_fails.php", "", "", "set_gray");

echo '<br><br>';

get_switch("All Writs", "View all writs from all users", "writs_sup.php", "", "", "set_gray");
