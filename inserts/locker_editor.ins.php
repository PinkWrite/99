<?php

// Logged in or not?
if (!isset($_SESSION['user_id'])) {
	return;
}

set_button("Editor Archives", "View editor archives", "archives_editor.php", "set_gray");

echo '<br><br>';

set_switch("Editor Register", "Register a new writer or observer", "register.php", "registrar", $userid, "set_gray");
