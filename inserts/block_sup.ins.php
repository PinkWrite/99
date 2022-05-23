<?php

if (isset($_GET['v'])) {
	if (filter_var($_GET['v'], FILTER_VALIDATE_INT, array('min_range' => 1))) {
		$block_id = preg_replace("/[^0-9]/","", $_GET['v']);
	} else {
		echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
		exit(); // Quit the script
	}
}

// New block?
if ( ($_SERVER['REQUEST_METHOD'] === 'POST') && (isset($_POST['new_block'])) && ($_POST['new_block'] == $userid) ) {
	$newblock = true;
	$where_was_i = 'no';

// Save new block
} elseif ( ($_SERVER['REQUEST_METHOD'] === 'POST') && (isset($_POST['editor'])) && (isset($_POST['block_name'])) && (!isset($block_id)) ) {
		// Sanitize the input
		$editor_id = filter_var($_POST['editor'], FILTER_VALIDATE_INT, array('min_range' => 1));
		$block_code = htmlspecialchars($_POST['block_code']); $block_code = strip_tags($block_code); $block_code = substr($block_code,0,10);
		$block_name = htmlspecialchars($_POST['block_name']); $block_name = strip_tags($block_name);
		$block_status = ( (isset($_POST['block_status'])) && ($_POST['block_status'] != '') ) ? $_POST['block_status'] : 'open';
		$block_status = htmlspecialchars($_POST['block_status']); $block_status = strip_tags($block_status);
		// SQL mysqli_real_escape_string
		$sql_block_code = mysqli_real_escape_string($dbc, $block_code);
		$sql_block_name = mysqli_real_escape_string($dbc, $block_name);
		$sql_block_status = mysqli_real_escape_string($dbc, $block_status);

		// Check Block code for dups
		$q = "SELECT name, code FROM blocks WHERE code='$sql_block_code' AND status='open'";
		$r = mysqli_query ($dbc, $q);
		if (mysqli_num_rows($r) > 0) {
			$row = mysqli_fetch_array($r);
			$dup_block_name = "$row[0]";
			$dup_block_code = "$row[1]";
			$show_dup_block = '<b>'.$dup_block_name. ' <small>('.$dup_block_code.')</small></b>';
			$add_num = 0;
			// If there were no changes
			while (isset($show_dup_block)) {
				$add_num = $add_num + 1;
				$new_block_code = $block_code.'-'.$add_num;
				$sql_new_block_code = mysqli_real_escape_string($dbc, $new_block_code);

				// Check again
				$q = "SELECT id FROM blocks WHERE code='$sql_new_block_code'";
				$r = mysqli_query ($dbc, $q);
				if (mysqli_num_rows($r) == 0) {
					$block_code = $new_block_code;
					$sql_block_code = mysqli_real_escape_string($dbc, $block_code);
					break;
				}
			}
		} // End Block code dup check

		// Insert into database
		$q = "INSERT INTO blocks (editor_id, name, code, status) VALUES ('$editor_id', '$sql_block_name', '$sql_block_code', '$sql_block_status')";
		$r = mysqli_query ($dbc, $q);
		if (mysqli_affected_rows($dbc) == 1) {
			// Get the last id INSERTed, similar to SCOPE_IDENTITY() but with MySQLi
			$block_id = $dbc->insert_id;
			// No empty variables
			$show_dup_block = (isset($show_dup_block)) ? $show_dup_block : '';
			// Reload this page as a proper block edit
			// Thanks https://stackoverflow.com/a/5576700/10343144
			echo "
			<form id=\"myForm\" action=\"block_sup.php?v=$block_id\" method=\"post\">
				<input type=\"hidden\" name=\"where_was_i\" value=\"no\">
 				<input type=\"hidden\" name=\"opened_by\" value=\"$userid\">
				<input type=\"hidden\" name=\"dup_block_code\" value=\"$show_dup_block\">
				<input type=\"hidden\" name=\"saved\" value=\"$userid\">
			</form>
			<script type=\"text/javascript\">
			    document.getElementById('myForm').submit();
			</script>";
		} else {
			echo "Database error, could not be saved.";
		}

// Save edited note
} elseif ( ($_SERVER['REQUEST_METHOD'] === 'POST') && (isset($_POST['editor'])) && (isset($_POST['block_name'])) && (isset($block_id)) ) {
	// Sanitize the input
	$editor_id = filter_var($_POST['editor'], FILTER_VALIDATE_INT, array('min_range' => 1));
	$block_code = htmlspecialchars($_POST['block_code']); $block_code = strip_tags($block_code); $block_code = substr($block_code,0,10);
	$block_name =  htmlspecialchars($_POST['block_name']); $block_name = strip_tags($block_name);
	$block_status = ( (isset($_POST['block_status'])) && ($_POST['block_status'] != '') ) ? $_POST['block_status'] : 'open';
	$block_status = htmlspecialchars($_POST['block_status']); $block_status = strip_tags($block_status);
	// SQL mysqli_real_escape_string
	$sql_block_code = mysqli_real_escape_string($dbc, $block_code);
	$sql_block_name = mysqli_real_escape_string($dbc, $block_name);
	$sql_block_status = mysqli_real_escape_string($dbc, $block_status);

	// $where_was_i ?
	if ((isset($_POST['where_was_i'])) && ($_POST['where_was_i'] != 'no') && (filter_var($_POST['where_was_i'], FILTER_VALIDATE_URL))) {
		$where_was_i = filter_var($_POST['where_was_i'], FILTER_VALIDATE_URL);
	} else {
		$where_was_i = 'no';
	}

	// Check Block code for dups
	$q = "SELECT name, code FROM blocks WHERE code='$sql_block_code' AND status='open' AND NOT id='$block_id'";
	$r = mysqli_query ($dbc, $q);
	if (mysqli_num_rows($r) > 0) {
		$row = mysqli_fetch_array($r);
		$dup_block_name = "$row[0]";
		$dup_block_code = "$row[1]";
		$show_dup_block = '<b>'.$dup_block_name. ' <small>('.$dup_block_code.')</small></b>';
		$add_num = 0;
		// If there were no changes
		while (isset($show_dup_block)) {
			$add_num = $add_num + 1;
			$new_block_code = $block_code.'-'.$add_num;
			$sql_new_block_code = mysqli_real_escape_string($dbc, $new_block_code);

			// Check again
			$q = "SELECT id FROM blocks WHERE code='$sql_new_block_code'";
			$r = mysqli_query ($dbc, $q);
			if (mysqli_num_rows($r) == 0) {
				$block_code = $new_block_code;
				$sql_block_code = mysqli_real_escape_string($dbc, $block_code);
				break;
			}
		}
	} // End Block code dup check

	// Save block
	$q = "UPDATE blocks SET name='$sql_block_name', editor_id='$editor_id', code='$sql_block_code', status='$sql_block_status' WHERE id='$block_id'";
	$r = mysqli_query ($dbc, $q);
	if ($r) {
		// Reload this page as a proper block edit
		// Thanks https://stackoverflow.com/a/5576700/10343144
		echo "
		<form id=\"myForm\" action=\"block_sup.php?v=$block_id\" method=\"post\">
			<input type=\"hidden\" name=\"opened_by\" value=\"$userid\">";
		// $where_was_i ?
		echo (isset($where_was_i)) ? "<input type=\"hidden\" name=\"where_was_i\" value=\"$where_was_i\">" : false ;
		echo (isset($show_dup_block))	? "<input type=\"hidden\" name=\"dup_block_code\" value=\"$show_dup_block\">" : false ;
		echo "
		<input type=\"hidden\" name=\"saved\" value=\"$userid\">
		</form>
		<script type=\"text/javascript\">
				document.getElementById('myForm').submit();
		</script>";
	} else {
		echo "Database error, could not be saved.";
	}

// Edit block
} elseif ( ($_SERVER['REQUEST_METHOD'] === 'POST') && (isset($_POST['opened_by'])) && ($_POST['opened_by'] == $userid) ) {
	$editing = true;
	if ( (isset($_POST['dup_block_code'])) && ($_POST['dup_block_code'] != '') ) {
		$show_dup_block = $_POST['dup_block_code'];
	}

	// $where_was_i ?
	if ((isset($_SERVER['HTTP_REFERER'])) && ((!isset($_POST['where_was_i'])) || ($_POST['where_was_i'] != 'no'))) {
		$where_was_i = filter_var($_SERVER['HTTP_REFERER'], FILTER_VALIDATE_URL);
	} elseif ((isset($_POST['where_was_i'])) && ($_POST['where_was_i'] != 'no') && (filter_var($_POST['where_was_i'], FILTER_VALIDATE_URL))) {
		$where_was_i = filter_var($_POST['where_was_i'], FILTER_VALIDATE_URL);
	} else {
		$where_was_i = 'no';
	}

// No new block, no save new block, no save edited block
} elseif (!isset($block_id)) {
	echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
	exit(); // Quit the script

} // End POST tests

if (isset($block_id)) {
	// Get the post info
	$q = "SELECT editor_id, name, code, status FROM blocks WHERE id='$block_id'";
	$r = mysqli_query ($dbc, $q);
	if (mysqli_num_rows($r) == 0) {
		echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
		exit(); // Quit the script
	}
	$row = mysqli_fetch_array($r);
	$editor_id = "$row[0]";
	$block_name = "$row[1]";
	$block_code = "$row[2]";
	$block_status = "$row[3]";

	// We are editing
	$editing = true;
}

// Saved?
if ( (isset($_POST['saved'])) && ($_POST['saved'] == $userid) ) {

	echo '<p class="noticegreen sans">Saved</p>';
	// New
	set_switch("New block +", "Create new block", "block_sup.php", "new_block", $userid, "newNoteButton");

	// Check for $where_was_i
	if ((isset($_POST['where_was_i'])) && (filter_var($_POST['where_was_i'], FILTER_VALIDATE_URL))) {
		$where_was_i = filter_var($_POST['where_was_i'], FILTER_VALIDATE_URL);
		echo '<br>';
		set_button("&larr; Go back", "Return to the page that brought you here", $where_was_i, "newNoteButton");
	}
}

// New / editing form
if ( (isset($editing)) || (isset($newblock)) ) {
	if (isset($newblock)) {
		// Title
		echo "<h2 class=\"lt\">New Block</h2>";
		echo '<form id="editform" class="userform" action="block_sup.php" method="post" accept-charset="utf-8">';
		// Status
		$block_status = 'open';
	} elseif (isset($editing)) {
		// Title
		echo "<h2 class=\"lt\">Edit Block</h2>";
		echo '<form id="editform" class="userform" action="block_sup.php?v='.$block_id.'" method="post" accept-charset="utf-8">';
		// $where_was_i ?
		echo (isset($where_was_i)) ? '<input type="hidden" name="where_was_i" value="'.$where_was_i.'">' : false ;
	}

	// Editor
	echo '<p class="lt sans">Editor:<br><br><select class="formselect" name="editor" required>
	<option hidden>Choose an editor...</option>';

	$qs = "SELECT id, name, username FROM users WHERE type='editor' OR type='supervisor' OR type='admin'";
	$rs = mysqli_query ($dbc, $qs);

	while ($rows = mysqli_fetch_array($rs)) {
		$editor_id_form = "$rows[0]";
		$editor_name_form = "$rows[1]";
		$editor_username_form = "$rows[2]";
		echo '<option value="'.$editor_id_form.'"';

		if ((isset($editor_id)) && ($editor_id == $editor_id_form)) {
			echo ' selected';
		}
		echo '>'.$editor_name_form.' <i>('.$editor_username_form.')</i></option>';
	}
	echo '</select></p>';

	// Block name
	echo '<p class="lt sans">Name:<br><br>
	<input type="text" name="block_name" id="block_name" class="readBox" required';
	if (isset($block_name)) {
		echo ' value="'.$block_name.'" />';
	} else {
	echo ' placeholder="Block Name" />';
	}

	// Space
	echo '</p>';

	// Block code
	echo '<p class="lt sans">Code:<br><br>
	<input type="text" name="block_code" id="block_code" class="readBox" maxlength="10" required';
	if (isset($block_code)) {
		echo ' value="'.$block_code.'" />';
	} else {
	echo ' placeholder="Short Code (eg: AH522, ArtHist5)" />';
	}
	echo '</p>';
	if (isset($show_dup_block)) {
		echo '<p class="noticeorange sans">Code appended; already used by open Block: '.$show_dup_block.'</p>';
	}

	// Status
	$status_open = ( (isset($block_status)) && ($block_status == 'open') ) ? ' checked' : '';
	$status_closed = ( (isset($block_status)) && ($block_status == 'closed') ) ? ' checked' : '';
	echo '<p class="lt sans">Status:<br><br>';
	echo '<label><input type="radio" name="block_status" value="open"'.$status_open.'> Open</label><br>';
	echo '<label><input type="radio" name="block_status" value="closed"'.$status_closed.'> Closed</label><br>';

	// Save
	echo '</p>
	<input type="submit" name="save_block" value="Save" id="save_block" class="lt_button" />';

	echo '</form>
	<br />';

	// Delete button
	if (isset($block_id)) {
		echo '<br><br>';
		set_switch("Delete &rarr;", "Delete this class", "delete_block_sup.php", "deleted_block", $block_id, "editNoteButton");
	}

// Redirect
} else {
	echo '<script type="text/javascript"> window.location = "' . PW99_HOME . '" </script>';
	exit(); // Quit the script
}
