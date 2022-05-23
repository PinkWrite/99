<?php

// Logged in or not?
if (!isset($_SESSION['user_id'])) {
	return;
}

set_button("Observed Archives", "View observed archives", "archives_observer.php", "set_gray");
