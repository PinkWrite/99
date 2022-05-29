<?php

// Logged in or not?
if (!isset($_SESSION['user_id'])) {
	return;
}

// Okay to view this page
$userid = $_SESSION['user_id'];

// New switch
set_switch("New writ +", "Start writing something new", "writ.php", "new_writ", $userid, "set_gray");

// Writ table
include('inserts/list_writs.ins.php');
