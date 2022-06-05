<?php

// Logged in or not?
if (!isset($_SESSION['user_id'])) {
	return;
}


// Valid the Block ID
if ((isset($_GET['v'])) && ((filter_var($_GET['v'], FILTER_VALIDATE_INT, array('min_range' => 0))) || ($_GET['v'] == '0'))) {
  $block_id = preg_replace("/[^0-9]/","", $_GET['v']);
	$block_type = ($block_id == 0) ? 'main' : 'block';
} else {
	echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
	exit(); // Quit the script
}

// Okay to view this page
$userid = $_SESSION['user_id'];

// Build page according to block type
// Main Block
if ($block_type == 'main') {

	// Get the Editor name
	$qe = "SELECT editor FROM users WHERE id='$userid'";
	$re = mysqli_query ($dbc, $qe);
	$rowe = mysqli_fetch_array($re);
	$editor_id = "$rowe[0]";
	if (mysqli_num_rows($re) == 0) {
		echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
		exit(); // Quit the script
	}
	// Get the Editor name
	$qe = "SELECT name FROM users WHERE id='$editor_id'";
	$re = mysqli_query ($dbc, $qe);
	$rowe = mysqli_fetch_array($re);
	$editor_name = "$rowe[0]";
	if (mysqli_num_rows($re) == 0) {
		echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
		exit(); // Quit the script
	}

	// Block name
	echo '<h3>Main Block: '.$editor_name.'</h3>';

	// New generic Writ for Main Block
	set_switch("New Main writ +", "Start a writ for the Main Block", "writ.php?v=0", "new_writ", $userid, "set_gray");

	// Tasks query
	$qt = "SELECT id, name, code FROM tasks WHERE block='0' AND type='open' AND editor_id='$editor_id'";

// Normal Block
} elseif ($block_type == 'block') {
	// User had Block?
	$qe = "SELECT blocks FROM users WHERE id='$userid'";
	$re = mysqli_query ($dbc, $qe);
	$rowe = mysqli_fetch_array($re);
	$u_blocks_array = json_decode($rowe[0], true);
	// User has this block?
	if (!in_array($block_id, $u_blocks_array)) {
		echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
		exit(); // Quit the script
	}
	// Get the Block info
	$qb = "SELECT editor_id, name, code FROM blocks WHERE id='$block_id'";
	$rb = mysqli_query ($dbc, $qb);
	$rowb = mysqli_fetch_array($rb);
	$editor_id = "$rowb[0]";
	$block_name = "$rowb[1]";
	$block_code = "$rowb[2]";
	if (mysqli_num_rows($rb) == 0) {
		echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
		exit(); // Quit the script
	}
	// Get the Editor name
	$qe = "SELECT name FROM users WHERE id='$editor_id'";
	$re = mysqli_query ($dbc, $qe);
	$rowe = mysqli_fetch_array($re);
	$editor_name = "$rowe[0]";
	if (mysqli_num_rows($re) == 0) {
		echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
		exit(); // Quit the script
	}

	// Block name
	echo '<h3 class="lt sans">'.$block_name.' <small>('.$block_code.') <i>'.$editor_name.'</i></small></h3>';

	// New generic Writ for Main Block
	set_switch("New $block_code writ +", "Start a writ for '.$block_name.'", "writ.php?v=$block_id", "new_writ", $userid, "set_gray");

	// Tasks query
	$qt = "SELECT id, name, code FROM tasks WHERE block='$block_id'";
}

/*#taskdev
// Start the table
echo '<h4>Tasks</h4>
<table class="list sans">';

// Start our row color class
$cc = 'lr';

// List tasks
$rt = mysqli_query ($dbc, $qt);
while ($rowt = mysqli_fetch_array($rt)) {
	$task_id = "$rowt[0]";
	$task_name = "$rowt[1]";
	$task_code = "$rowt[2]";
	echo '<tr class="'.$cc.'">
		<td><b>'.$task_code.'</b></td>
		<td><small>('.$task_name.')</small></td>
		<td><div style="display: inline; float:right;">';
	set_switch("Start task +", "Start a task '.$task_name.'", "writ.php?v=$task_id", "new_writ", $userid, "editNoteButton");
	echo '</div>
		</td>
		</tr>';

	// Rotate our row color class
	$cc = ($cc == 'lr') ? 'dr' : 'lr';
} // Finish the rows

echo '
</table>';
*/

// Writs
echo '<h4>Writs</h4>';

// Writ table
$term_status = 'current';
include('inserts/list_writs.ins.php');
