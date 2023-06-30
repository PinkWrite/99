<?php

// Require the configuration before any PHP code as the configuration controls error reporting
require('./pw99-config.php');

// A settings page requires form functions
require_once('./includes/form_functions.inc.php');

// str_contains() is only in PHP 8, this polyfill makes up for it on earlier
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return '' === $needle || false !== strpos($haystack, $needle);
    }
}

// Logged in or not?
if (isset($_SESSION['user_id'])) {
	$userid = $_SESSION['user_id'];

	// Writer archiving selected
	if ( ($_SERVER['REQUEST_METHOD'] === 'POST') && ($_POST['bluksubmit'] == 'archive')
	&& ((isset($_POST['writer_archive'])) && (isset($_POST['writer_archive']) == $userid))
 	&& (!isset($_POST['checksubmit'])) ) {
	  unset($_POST['bluksubmit']);
		unset($_POST['writer_archive']);
	  foreach ($_POST as $writ_id) {
			$q = "UPDATE writs SET term_status='archived' WHERE writer_id='$userid' AND id='$writ_id'";
			$r = mysqli_query ($dbc, $q);
			if (!$r) {
				$error = true;
			}
	  } // End loop
		if (isset($error)) {
			echo "Database error, give to tech support: <pre>$q</pre>"; exit();
		} else {
			$_SESSION['act_message'] = '<p class="sans noticegreen">Writ(s) archived.</p>';
			header("Location: writs.php");
			exit(); // Quit the script
		}

	// Writer archiving all scored
	} elseif ( ($_SERVER['REQUEST_METHOD'] === 'POST') && ($_POST['bluksubmit'] == 'archive all scored')
	  && (isset($_POST['writer_archive'])) && (isset($_POST['writer_archive']) == $userid)
		&& (isset($_POST['checksubmit'])) && ($_POST['checksubmit'] == 'archive_selected') ) {
		unset($_POST['bluksubmit']);
		unset($_POST['writer_archive']);
		unset($_POST['checksubmit']);
		$q = "UPDATE writs SET term_status='archived' WHERE edits_status='scored' AND writer_id=$userid";
		$r = mysqli_query ($dbc, $q);
		if (!$r) {
			$error = true;
		}
		if (isset($error)) {
			echo "Database error, give to tech support: <pre>$q</pre>"; exit();
		} else {
			$_SESSION['act_message'] = '<p class="sans noticegreen">All scored writs archived.</p>';
			header("Location: writs.php");
			exit(); // Quit the script
		}

	// Writer restoring selected
	} elseif ( ($_SERVER['REQUEST_METHOD'] === 'POST') && ($_POST['bluksubmit'] == 'restore')
	  && ((isset($_POST['writer_archive'])) && (isset($_POST['writer_archive']) == $userid))
 		&& (!isset($_POST['checksubmit'])) ) {
	  unset($_POST['bluksubmit']);
		unset($_POST['writer_archive']);
	  foreach ($_POST as $writ_id) {
			$q = "UPDATE writs SET term_status='current' WHERE writer_id='$userid' AND id='$writ_id'";
			$r = mysqli_query ($dbc, $q);
			if (!$r) {
				$error = true;
			}
	  } // End loop
		if (isset($error)) {
			echo "Database error, give to tech support: <pre>$q</pre>"; exit();
		} else {
			$_SESSION['act_message'] = '<p class="sans noticegreen">Writ(s) restored.</p>';
			header("Location: archives.php");
			exit(); // Quit the script
		}

	// Writer deleting selected
	} elseif ( ($_SERVER['REQUEST_METHOD'] === 'POST') && ($_POST['bluksubmit'] == 'delete')
	  && ((isset($_POST['writer_archive'])) && (isset($_POST['writer_archive']) == $userid))
 		&& (isset($_POST['checksubmit'])) && ($_POST['checksubmit'] == 'delete') ) {
	  unset($_POST['bluksubmit']);
		unset($_POST['writer_archive']);
		unset($_POST['checksubmit']);
	  foreach ($_POST as $writ_id) {
			$q = "DELETE FROM writs WHERE term_status='archived' AND writer_id='$userid' AND id='$writ_id'";
			$r = mysqli_query ($dbc, $q);
			if (!$r) {
			$error = true;
			}
		} // End loop
		if (isset($error)) {
			echo "Database error, give to tech support: <pre>$q</pre>"; exit();
		} else {
			$_SESSION['act_message'] = '<p class="sans noticegreen">Writ(s) deleted.</p>';
			header("Location: archives.php");
			exit(); // Quit the script
		}

	// Editor archiving selected
	} elseif ( ($_SERVER['REQUEST_METHOD'] === 'POST') && ($_POST['bluksubmit'] == 'archive')
	  && ((isset($_POST['editor_archive'])) && (isset($_POST['editor_archive']) == $userid)) && (!isset($_POST['checksubmit'])) ) {
		unset($_POST['bluksubmit']);
		unset($_POST['editor_archive']);
	  foreach ($_POST as $writ_id) {
			$q = "UPDATE writs SET review_status='archived' WHERE id=$writ_id";
			$r = mysqli_query ($dbc, $q);
			if (!$r) {
				$error = true;
			}
	  } // End loop
		if (isset($error)) {
			echo "Database error, give to tech support: <pre>$q</pre>"; exit();
		} else {
			$_SESSION['act_message'] = '<p class="sans noticegreen">Writ(s) archived.</p>';
			header("Location: editor.php");
			exit(); // Quit the script
		}

	// Editor restoring selected
	} elseif ( ($_SERVER['REQUEST_METHOD'] === 'POST') && ($_POST['bluksubmit'] == 'restore')
	  && ((isset($_POST['editor_archive'])) && (isset($_POST['editor_archive']) == $userid))
 		&& (!isset($_POST['checksubmit'])) ) {
			unset($_POST['bluksubmit']);
			unset($_POST['editor_archive']);
			foreach ($_POST as $writ_id) {
				$q = "UPDATE writs SET review_status='current' WHERE id=$writ_id";
				$r = mysqli_query ($dbc, $q);
				if (!$r) {
					$error = true;
				}
		  } // End loop
			if (isset($error)) {
				echo "Database error, give to tech support: <pre>$q</pre>"; exit();
			} else {
				$_SESSION['act_message'] = '<p class="sans noticegreen">Writ(s) restored.</p>';
				header("Location: archives_editor.php");
				exit(); // Quit the script
			}

	// Editor deleting selected
	} elseif ( ($_SERVER['REQUEST_METHOD'] === 'POST') && ($_POST['bluksubmit'] == 'delete')
	  && ((isset($_POST['editor_archive'])) && (isset($_POST['editor_archive']) == $userid))
 		&& (isset($_POST['checksubmit'])) && ($_POST['checksubmit'] == 'delete') ) {
			unset($_POST['bluksubmit']);
			unset($_POST['editor_archive']);
			unset($_POST['checksubmit']);
			foreach ($_POST as $writ_id) {
				$q = "DELETE FROM writs WHERE review_status='archived' AND id=$writ_id";
				$r = mysqli_query ($dbc, $q);
				if (!$r) {
					$error = true;
				}
		  } // End loop
			if (isset($error)) {
				echo "Database error, give to tech support: <pre>$q</pre>"; exit();
			} else {
				$_SESSION['act_message'] = '<p class="sans noticegreen">Writ(s) deleted.</p>';
				header("Location: archives_editor.php");
				exit(); // Quit the script
			}

	// Editor archiving all scored
	} elseif ( ($_SERVER['REQUEST_METHOD'] === 'POST') && ($_POST['bluksubmit'] == 'archive all scored')
	  && (isset($_POST['editor_archive'])) && (isset($_POST['editor_archive']) == $userid)
		&& (isset($_POST['checksubmit'])) && ($_POST['checksubmit'] == 'archive_selected') ) {
		unset($_POST['bluksubmit']);
		unset($_POST['editor_archive']);
		unset($_POST['checksubmit']);
		// Get Blocks
		$qb = "SELECT id FROM blocks WHERE editor_id='$userid'";
		$rb = mysqli_query ($dbc, $qb);
		// Archive scored in each Block
		while ($rowb = mysqli_fetch_array($rb)) {
			$block_id = "$rowb[0]";
			$q = "UPDATE writs SET review_status='archived' WHERE edits_status='scored' AND block=$block_id";
			$r = mysqli_query ($dbc, $q);
			if (!$r) {
				echo "Database error, give to tech support: <pre>$q</pre>"; exit();
			}
		}
		// Get Writers
		$qu = "SELECT id FROM users WHERE editor='$userid'";
		$ru = mysqli_query ($dbc, $qu);
		// Archive scored in each Writer
		while ($rowu = mysqli_fetch_array($ru)) {
			$writer_id = "$rowu[0]";
			$q = "UPDATE writs SET review_status='archived' WHERE edits_status='scored' AND block='0' AND writer_id=$writer_id";
			$r = mysqli_query ($dbc, $q);
			if (!$r) {
				$error = true;
			}
	  } // End loop
		if (isset($error)) {
			echo "Database error, give to tech support: <pre>$q</pre>"; exit();
		} else {
			$_SESSION['act_message'] = '<p class="sans noticegreen">All scored writs archived.</p>';
			header("Location: editor.php");
			exit(); // Quit the script
		}

	// Admin archiving selected
	} elseif ( ($_SERVER['REQUEST_METHOD'] === 'POST') && ($_POST['bluksubmit'] == 'archive')
	  && ((isset($_POST['admin_archive'])) && (isset($_POST['admin_archive']) == $userid))
 		&& (!isset($_POST['checksubmit'])) ) {

		// Unpack GET arguments back into URL
		// Note: if redirecting with GET args, then $_POST['writs_sup_get_redirect'] should be set from the form that brought us here
		if ((isset($_POST['writs_sup_get_redirect'])) && (str_contains($_POST['writs_sup_get_redirect'], '.php'))) {
			$redirect_php = $_POST['writs_sup_get_redirect'];
			$get_suffix = '?';
			foreach ($_GET AS $key => $value) {
				$get_suffix .= "$key=$value";
			}
		} else {
			$get_suffix = '';
		}

		unset($_POST['bluksubmit']);
		unset($_POST['admin_archive']);
		unset($_POST['writs_sup_get_redirect']);
	  foreach ($_POST as $writ_id) {
			$q = "UPDATE writs SET review_status='archived' WHERE id=$writ_id";
			$r = mysqli_query ($dbc, $q);
			if (!$r) {
				$error = true;
			}
	  } // End loop
		if (isset($error)) {
			echo "Database error, give to tech support: <pre>$q</pre>"; exit();
		} else {
			$_SESSION['act_message'] = '<p class="sans noticegreen">Writ(s) archived.</p>';
			header("Location: {$redirect_php}{$get_suffix}");
			exit(); // Quit the script
		}

	// Admin restoring selected
	} elseif ( ($_SERVER['REQUEST_METHOD'] === 'POST') && ($_POST['bluksubmit'] == 'restore')
	  && ((isset($_POST['admin_archive'])) && (isset($_POST['admin_archive']) == $userid))
 		&& (!isset($_POST['checksubmit'])) ) {

			// Unpack GET arguments back into URL
			// Note: if redirecting with GET args, then $_POST['writs_sup_get_redirect'] should be set from the form that brought us here
			if ((isset($_POST['writs_sup_get_redirect'])) && (str_contains($_POST['writs_sup_get_redirect'], '.php'))) {
				$redirect_php = $_POST['writs_sup_get_redirect'];
				$get_suffix = '?';
				foreach ($_GET AS $key => $value) {
					$get_suffix .= "$key=$value";
				}
			} else {
				$get_suffix = '';
			}

			unset($_POST['bluksubmit']);
			unset($_POST['admin_archive']);
			unset($_POST['writs_sup_get_redirect']);
			foreach ($_POST as $writ_id) {
				$q = "UPDATE writs SET review_status='current' WHERE id=$writ_id";
				$r = mysqli_query ($dbc, $q);
				if (!$r) {
					$error = true;
				}
		  } // End loop
			if (isset($error)) {
				echo "Database error, give to tech support: <pre>$q</pre>"; exit();
			} else {
				$_SESSION['act_message'] = '<p class="sans noticegreen">Writ(s) restored.</p>';
				header("Location: {$redirect_php}{$get_suffix}");
				exit(); // Quit the script
			}

	// Admin deleting selected
	} elseif ( ($_SERVER['REQUEST_METHOD'] === 'POST') && ($_POST['bluksubmit'] == 'delete')
	  && ((isset($_POST['admin_archive'])) && (isset($_POST['admin_archive']) == $userid))
 		&& (isset($_POST['checksubmit'])) && ($_POST['checksubmit'] == 'delete') ) {

			// Unpack GET arguments back into URL
			// Note: if redirecting with GET args, then $_POST['writs_sup_get_redirect'] should be set from the form that brought us here
			if ((isset($_POST['writs_sup_get_redirect'])) && (str_contains($_POST['writs_sup_get_redirect'], '.php'))) {
				$redirect_php = $_POST['writs_sup_get_redirect'];
				$get_suffix = '?';
				foreach ($_GET AS $key => $value) {
					$get_suffix .= "$key=$value";
				}
			} else {
				$get_suffix = '';
			}

			unset($_POST['bluksubmit']);
			unset($_POST['admin_archive']);
			unset($_POST['checksubmit']);
			unset($_POST['writs_sup_get_redirect']);
			foreach ($_POST as $writ_id) {
				$q = "DELETE FROM writs WHERE review_status='archived' AND id=$writ_id";
				$r = mysqli_query ($dbc, $q);
				if (!$r) {
					$error = true;
				}
		  } // End loop
			if (isset($error)) {
				echo "Database error, give to tech support: <pre>$q</pre>"; exit();
			} else {
				$_SESSION['act_message'] = '<p class="sans noticegreen">Writ(s) deleted.</p>';
				header("Location: {$redirect_php}{$get_suffix}");
				exit(); // Quit the script
			}

	// Admin archiving all in block, from Admin: Closed Blocks
	} elseif ( ($_SERVER['REQUEST_METHOD'] === 'POST') && (isset($_POST['block_archive'])) && ($_POST['block_archive'] == 'archive all writs')
		&& (isset($_POST['checksubmit'])) ) {
		$block_id = (filter_var($_POST['checksubmit'], FILTER_VALIDATE_INT, array('min_range' => 0))) ? $_POST['checksubmit'] : "";
		$q = "UPDATE writs SET term_status='archived', review_status='archived' WHERE block=$block_id";
		$r = mysqli_query ($dbc, $q);
		if (!$r) {
			echo "Database error, give to tech support: <pre>$q</pre>"; exit();
		} else {
			$_SESSION['act_message'] = '<p class="sans noticegreen">Writ(s) archived.</p>';
			header("Location: blocks_closed_sup.php");
			exit(); // Quit the script
		}

	// End scenarios tests
	} else {
		header("Location: " . PW99_HOME);
		exit(); // Quit the script
	}

// End logged in test
} else {
	header("Location: " . PW99_HOME);
	exit(); // Quit the script
}

// Include the footer file to complete the template
require('./includes/footer.html');
?>
