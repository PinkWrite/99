<?php

// Logged in or not?
if (!isset($_SESSION['user_id'])) {
	return;
}

set_switch("My Password", "Change my password", "change_password.php", "user", $userid, "set_gray");

echo '<br><br>';

set_button("My Archives", "View my archives", "archives.php", "set_gray");
