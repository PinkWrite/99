<?php

// Check for config
$configured = (file_exists('./pw99-config.php')) ? true : false ;
if ($configured) {
  require_once('./pw99-config.php');
  $configured = $config['configured']; // From the settings in the pw99-config.php file
  $configured = (isset($dbc)) ? true : false ;
  $configured = ($configured) && (isset($siteTitle)) ? true : false ;
  $configured = ($configured) && (defined(DB_USER)) ? true : false ;
  $configured = ($configured) && (defined(DB_PASSWORD)) ? true : false ;
  $configured = ($configured) && (defined(DB_HOST)) ? true : false ;
  $configured = ($configured) && (defined(DB_NAME)) ? true : false ;
  $configured = ($configured) && (defined(PW99_HOME)) ? true : false ;
  $configured = ($configured) && (defined(SITE_TITLE)) ? true : false ;
}

// For storing registration errors
$reg_errors = array();

// POSTed database & config
if (($_SERVER['REQUEST_METHOD'] === 'POST') && (isset($_POST['db_inputs'])) && ($configured == false)) {

  // Check if the config already exists, if so this just creates a new admin account
  if (!file_exists('./pw99-config.php')) {

    // One-time database checks
    $db_name = (preg_match('/[a-zA-Z0-9_]{1,64}$/i', $_POST['db_name']))
    ? preg_replace("/[^a-zA-Z0-9_]/","", $_POST['db_name']) : '';
    if ($db_name == '') {
      echo '<p class="error">Not a valid database name!</p>';
      $no_db_cred_errors = false;
      $reg_errors['db_name'] = '64 character max, only alphanumeric characters and underscore _';
    }

    $db_user = (preg_match('/[a-zA-Z0-9_]{2,32}$/i', $_POST['db_user']))
    ? preg_replace("/[^a-zA-Z0-9_]/","", $_POST['db_user']) : '';
    if ($db_user == '') {
      echo '<p class="error">Not a valid database username!</p>';
      $no_db_cred_errors = false;
      $reg_errors['db_user'] = '64 character max, only alphanumeric characters and underscore _';
    }

    // Fancy ReGex that allows for all allowable characters in a MySQL database password, for these special characters... '/&*=]\[<>;,.:^?+$%-‘~!@#)(}{_  (and space)
    // Order of these special characters matters in a RegEx!
    $db_pass = (preg_match('/[A-Za-z0-9 \'\/&\*=\]\|[<>;,\.:\^\?\+\$%-‘~!@#)(}{_ ]{6,32}$/', $_POST['db_pass']))
    ? preg_replace("/[^A-Za-z0-9 \'\/&\*=\]\|[<>;,\.:\^\?\+\$%-‘~!@#)(}{_ ]/","", $_POST['db_pass']) : '';
    if ($db_pass == '') {
      echo '<p class="error">Not a valid database password!</p>';
      $no_db_cred_errors = false;
      $reg_errors['db_pass'] = '64 character max, only alphanumeric characters and _ % # @ ! ? \' ‘ : ; , . * ^ & % $ + = - ~ | / ( ) < > { }';
    }

    // This test (on two lines to make is easy to read) checks for either a valid URL starting with https:// or 'localhost'
    $db_host =
      ( ((filter_var($_POST['db_host'],FILTER_VALIDATE_URL)) && (substr($_POST['db_host'], 0, 8) === "https://"))
      || ($_POST['db_host'] == 'localhost') )
    ? $_POST['db_host'] : '';
    if ($db_host == '') {
      echo '<p class="error">Not a valid database host!</p>';
      $no_db_cred_errors = false;
      $reg_errors['db_host'] = 'Must be "localhost" or a valid URL using https://';
    }

    // Site title
    $site_title = (preg_match('/[a-zA-Z0-9_ ,.|:\'"%#@!?;*^&$+=-~/()<>{}\[\]]{1,54}$/i', $_POST['db_user']))
    ? preg_replace("/[^a-zA-Z0-9_]/","", $_POST['site_title']) : '';
    if ($site_title == '') {
      echo '<p class="error">Not a valid database username!</p>';
      $no_title_errors = false;
      $reg_errors['site_title'] = '64 character max, only alphanumeric characters and _ % # @ ! ? \' " : ; , . * ^ & % $ + = - ~ | / ( ) < > { } [ ]';
    }

    // No errors, all ready
    if ((!isset($no_db_cred_errors)) && (!isset($no_title_errors))) {

      // Protocol
      $protocol = (isset($_SERVER['HTTPS'])
      && ($_SERVER['HTTPS'] == 'on'
      || $_SERVER['HTTPS'] == 1)
      || isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
      && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') ? 'https://' : 'http://' ;

      // Web base URL
      $page = '/install.php';
      $pw99Home = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
      $pw99Home = preg_replace('/'. preg_quote($page, '/') . '$/', '', $pw99Home);

// Heredoc:
$configFile = <<<EOF
<?php

// Start the session
session_start();

// Database
DEFINE ('DB_NAME', '$db_name');
DEFINE ('DB_USER', '$db_user');
DEFINE ('DB_PASSWORD', '$db_pass');
DEFINE ('DB_HOST', '$db_host');

// Home URL base
DEFINE ('PW99_HOME', '$pw99Home');

// Site Title
DEFINE ('SITE_TITLE', '$siteTitle');

// *** DANGER ZONE *** Developer & Webmin settings
\$config = [
  'configured' => true,          // Configured (for install purposes)
  'allowcreateadmin' => false,   // Only set true for emergency recovery access to an admin account!
];

// Make the connection & character set
\$dbc = mysqli_connect (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
mysqli_set_charset(\$dbc, 'utf8');

// Friendly variables
\$pw99Home = PW99_HOME;
\$siteTitle = SITE_TITLE;

EOF;

      // Write the file:
      file_put_contents('./pw99-config.php', $configFile);

      // Include our config file (which includes the newly-written SQL config) if it exists
      if (!file_exists('./pw99-config.php')) {
        echo '<p>Could not create the database config file, quitting.</p>';
        exit ();
      } else {
        require_once('./pw99-config.php');

        // Write .htaccess

// Heredoc:
$htaccess = <<<EOF
RewriteEngine on

# Remove "index.php" from any URL and redirect back to the "directory"
RewriteCond %{ENV:REDIRECT_STATUS} ^$
RewriteCond %{REQUEST_URI} ^/(.+/)?index\.php
RewriteRule (^|/)index\.php(/|$) /%1 [R=301,L]

# Forward and mask "login.php" to "in"
RewriteCond %{ENV:REDIRECT_STATUS} ^$
RewriteCond %{REQUEST_URI} ^/(.+/)?login\.php
RewriteRule (^|/)login\.php(/|$) /%1in [R=302,L]
RewriteRule ^in?$ login.php

EOF;

        // Write the file:
        file_put_contents('.htaccess', $htaccess);

      } // Now we have a database connection and we can begin making queries

      $q = "CREATE TABLE IF NOT EXISTS `users` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `type` ENUM('writer','observer','editor','supervisor','admin') NOT NULL,
        `username` VARCHAR(32) NOT NULL,
        `email` VARCHAR(90) NOT NULL,
        `name` VARCHAR(80) NOT NULL,
        `project` VARCHAR(80) DEFAULT NULL,
        `level` BIGINT UNSIGNED DEFAULT 0,
        `groups` JSON NOT NULL,
        `blocks` JSON NOT NULL,
        `observing` JSON NOT NULL,
        `editor` BIGINT UNSIGNED DEFAULT NULL,
        `status` ENUM('signup','active','dormant','grad') NOT NULL,
        `date_created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `date_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `pass` VARCHAR(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `username` (`username`),
        UNIQUE KEY `email` (`email`)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4";
      $r = mysqli_query ($dbc, $q);
      if (!$r) {
        echo '<p class="sans">Could not create table <code>users</code>. I quit.</p>';
        exit();
      }


      $q = "CREATE TABLE IF NOT EXISTS `notes` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `writer_id` BIGINT UNSIGNED NOT NULL,
        `body` TEXT DEFAULT NULL,
        `pinned` BOOLEAN NOT NULL DEFAULT 0,
        `group` BIGINT UNSIGNED NOT NULL,
        `save_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4";
      $r = mysqli_query ($dbc, $q);
      if (!$r) {
        echo '<p class="sans">Could not create table <code>notes</code>. I quit.</p>';
        exit();
      }


      $q = "CREATE TABLE IF NOT EXISTS `blocks` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `editor_id` BIGINT UNSIGNED NOT NULL,
        `name` TINYTEXT DEFAULT NULL,
        `code` VARCHAR(10) DEFAULT NULL,
        `status` ENUM('open', 'closed') NOT NULL,
        `project` BIGINT UNSIGNED DEFAULT 0,
        `series` BIGINT UNSIGNED DEFAULT 0,
        `group` BIGINT UNSIGNED DEFAULT 0,
        `level` BIGINT UNSIGNED DEFAULT 0,
        `creation_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4";
      $r = mysqli_query ($dbc, $q);
      if (!$r) {
        echo '<p class="sans">Could not create table <code>blocks</code>. I quit.</p>';
        exit();
      }

      $q = "CREATE TABLE IF NOT EXISTS `writs` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `writer_id` BIGINT UNSIGNED NOT NULL,
        `project` BIGINT UNSIGNED DEFAULT 0,
        `block` BIGINT UNSIGNED DEFAULT 0,
        `level` BIGINT UNSIGNED DEFAULT 0,
        `task` BIGINT UNSIGNED DEFAULT 0,
        `term_status` ENUM('current', 'archived') NOT NULL,
        `review_status` ENUM('current', 'archived') NOT NULL,
        `title` VARCHAR(122) DEFAULT NULL,
        `work` VARCHAR(122) DEFAULT NULL,
        `score` INT UNSIGNED DEFAULT NULL,
        `outof` INT UNSIGNED DEFAULT 100,
        `task_title` TEXT DEFAULT NULL,
        `task_content` MEDIUMTEXT DEFAULT NULL,
        `scoring` TEXT DEFAULT NULL,
        `correction` MEDIUMTEXT DEFAULT NULL,
        `edits` MEDIUMTEXT DEFAULT NULL,
        `edit_notes` TEXT DEFAULT NULL,
        `draft` MEDIUMTEXT DEFAULT NULL,
        `correction_ontime` ENUM('ontime', 'late') NOT NULL,
        `draft_ontime` ENUM('ontime', 'late') NOT NULL,
        `draft_status` ENUM('saved', 'submitted', 'reviewed') NOT NULL,
        `edits_status` ENUM('drafting', 'viewed', 'saved', 'submitted', 'scored') NOT NULL,
        `notes` MEDIUMTEXT DEFAULT NULL,
        `instructions` MEDIUMTEXT DEFAULT NULL,
        `draft_open_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `draft_save_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `draft_submit_date` TIMESTAMP NULL DEFAULT NULL,
        `edits_date` TIMESTAMP NULL DEFAULT NULL,
        `edits_viewed_date` TIMESTAMP NULL DEFAULT NULL,
        `corrected_save_date` TIMESTAMP NULL DEFAULT NULL,
        `corrected_submit_date` TIMESTAMP NULL DEFAULT NULL,
        `scoring_date` TIMESTAMP NULL DEFAULT NULL,
        PRIMARY KEY (`id`)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4";
      $r = mysqli_query ($dbc, $q);
      if (!$r) {
        echo '<p class="sans">Could not create table <code>writs</code>. I quit.</p>';
        exit();
      }


      $q = "CREATE TABLE IF NOT EXISTS `clickathon` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `username_list` MEDIUMTEXT NOT NULL,
        `ip` MEDIUMTEXT NOT NULL,
        `time_stamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `time_epoch` INT UNSIGNED NOT NULL,
        PRIMARY KEY (`id`)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4";
      $r = mysqli_query ($dbc, $q);
      if (!$r) {
        echo '<p class="sans">Could not create table <code>clickathon</code>. I quit.</p>';
        exit();
      }

    } //((!isset($no_db_cred_errors)) && (!isset($no_title_errors)))
  } // (!file_exists('./pw99-config.php'))
} // Finish db_inputs POST if

// Create admin user

// Check for a register admin form submission
if ( ($_SERVER['REQUEST_METHOD'] == 'POST') && (isset($_POST['register_admin'])) ) {

  // If not installing a database, we have not yet included our config
  require_once('./pw99-config.php');
echo "config included";
	// User type
	if (isset($_POST['utype'])) {
		$type = $_POST['utype'];
	} else {
		$reg_errors['utype'] = 'Please choose a type!';
	}

	// Check for a name
	if (preg_match ('/^[A-Z \'.-]{1,80}$/i', $_POST['name'])) {
		$name = mysqli_real_escape_string ($dbc, $_POST['name']);
	} else {
		$reg_errors['name'] = 'Please enter your name, only letters and hyphens, 80 characters max!';
	}

	// Check for a username
	if (preg_match ('/^[A-Z0-9]{6,32}$/i', $_POST['username'])) {
		$username = mysqli_real_escape_string ($dbc, $_POST['username']);
	} else {
		$reg_errors['username'] = 'Please enter a valid username, 6-32 characters!';
	}

	// Check for an email and match against the confirmed email
	if (filter_var($_POST['email1'], FILTER_VALIDATE_EMAIL)) {
		if ($_POST['email1'] == $_POST['email2']) {
			$email = mysqli_real_escape_string ($dbc, $_POST['email1']);
		} else {
			$reg_errors['email2'] = 'Your email addresses did not match!';
		}
	} else {
		$reg_errors['email1'] = 'Please enter a valid email address, 90 characters max!';
	}

	// Check for a password and match against the confirmed password
	if (preg_match ('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])[0-9A-Za-z!@#$%+-]{6,32}$/', $_POST['pass1']) ) {
		if ($_POST['pass1'] == $_POST['pass2']) {
			$password = mysqli_real_escape_string ($dbc, $_POST['pass1']);
		} else {
			$reg_errors['pass2'] = 'Your passwords did not match!';
		}
	} else {
		$reg_errors['pass1'] = 'Please enter a valid password!';
	}

	if (empty($reg_errors)) { // If everything's OK...

		// Make sure the email address and username are available
		$q = "SELECT email, username FROM users WHERE email='$email' OR username='$username'";
		$r = mysqli_query ($dbc, $q);

		// Get the number of rows returned
		$rows = mysqli_num_rows($r);

		if ($rows == 0) { // No dups!

			// Add the user to the database
			$q = "INSERT INTO users (type, username, email, pass, name, status, blocks, observing, groups) VALUES ('$type', '$username', '$email', '"  .  password_hash($password, PASSWORD_BCRYPT) .  "', '$name', 'active', 'null', 'null', 'null')";
			$r = mysqli_query ($dbc, $q);

			if (mysqli_affected_rows($dbc) == 1) { // If it ran OK

				/*
				// Send the registration email
				$from = '"'.$site_from_email_name.'" <'.$site_from_email.'>';
				$to = '"'.$name.'" <'.$email.'>';
				$subject = "Registration: $siteTitle";
				$message = "<html><p>Thank you for registering at $siteTitle.</p><br />Username: $username<br /><p>You agreed to our Terms & Conditions, which may change and you will receive an email when you do. You also agreed that all sales are final and no refunds are given under any circumstances.</p><br /><a title=\"$siteTitle\" href=\"https://pacificdailyads.com\">pacificdailyads.com</a></html>";
				$headers .= 'To: ' . $to . "\r\n";
				$headers .= 'From: ' . $from . "\r\n";
				$headers .= 'Bcc: ' . $site_bcc_email . "\r\n";
				mail($to,$subject,$message, $headers);
				*/

				// Display a thanks message
				echo '<h2>Success!</h2><p class="sans">User "'.$name.'" has been registered.</p>';

				// Unset the variables
				unset ($type);
				unset ($username);
				unset ($email);
				unset ($name);
				unset ($password);
				unset ($_POST['type']);
				unset ($_POST['name']);
				unset ($_POST['username']);
				unset ($_POST['email1']);
				unset ($_POST['email2']);
				unset ($_POST['pass1']);
				unset ($_POST['pass2']);
				unset ($_POST['register_new']);
				unset ($_POST['submit_button']);

        // Reset pw99-config.php
        // Inherit old settings
        $db_name = DB_NAME;
        $db_user = DB_USER;
        $db_pass = DB_PASSWORD;
        $db_host = DB_HOST;
        $pw99Home = PW99_HOME;
        $siteTitle = SITE_TITLE;

// Heredoc:
$configFile = <<<EOF
<?php

// Start the session
session_start();

// Database
DEFINE ('DB_NAME', '$db_name');
DEFINE ('DB_USER', '$db_user');
DEFINE ('DB_PASSWORD', '$db_pass');
DEFINE ('DB_HOST', '$db_host');

// Home URL base
DEFINE ('PW99_HOME', '$pw99Home');

// Site Title
DEFINE ('SITE_TITLE', '$siteTitle');

// *** DANGER ZONE *** Developer & Webmin settings
\$config = [
  'configured' => true,          // Configured (for install purposes)
  'allowcreateadmin' => false,   // Only set true for emergency recovery access to an admin account!
];

// Make the connection & character set
\$dbc = mysqli_connect (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
mysqli_set_charset(\$dbc, 'utf8');

// Friendly variables
\$pw99Home = PW99_HOME;
\$siteTitle = SITE_TITLE;

EOF;

        // Write the file:
        file_put_contents('./pw99-config.php', $configFile);

        // Include our config file (which includes the newly-written SQL config) if it exists
        if (!file_exists('./pw99-config.php')) {
          echo '<p>Could not update pw99-config.php. This is a serious and unkonwn error. It could be a file permissions issue on the webserver. Contact the web IT team.</p>';
          exit ();
        } else {
          require_once('./pw99-config.php');
          // WRITE HTACCESS
        } // Now we have a database connection and we can begin making queries


			} else { // If it did not run OK
				echo 'Due to a database issue and could not register a new admin. Contact your website IT team.';
			}

		} else { // The email address or username is not available

			if ($rows == 2) { // Both are taken

				$reg_errors['email1'] = 'This email address has already been registered. If you have forgotten your password, use the link at right to have your password sent to you.';
				$reg_errors['username'] = 'This username has already been registered. Please try another.';

			} else { // One or both may be taken

				// Get row
				$row = mysqli_fetch_array($r, MYSQLI_NUM);

				if( ($row[0] == $_POST['email1']) && ($row[1] == $_POST['username'])) { // Both match
					$reg_errors['email1'] = 'This email address has already been registered. If you have forgotten your password, use the link at right to have your password sent to you.';
					$reg_errors['username'] = 'This username has already been registered with this email address. If you have forgotten your password, use the link at right to have your password sent to you.';
				} elseif ($row[0] == $_POST['email1']) { // Email match
					$reg_errors['email1'] = 'This email address has already been registered. <a href=\"forgot_password.php\" align=\"right\">Forgot your password?</a>';
				} elseif ($row[1] == $_POST['username']) { // Username match
					$reg_errors['username'] = 'This username has already been registered. Please try another.';
				}

			} // End of $rows == 2 else

		} // End of $rows == 0 if

	} // End of empty($reg_errors) if

} // End of register_admin POST if


// Render a page
?>
<!DOCTYPE html>
<html>
<head>
  <!-- CSS file included as <link> -->
  <link href="css/styles.css" rel="stylesheet" type="text/css" />
</head>
<body>
  <h1 style="clear: both; display: block;"><?php echo $siteTitle; ?></h1>
  <p class="dk sans"><b>Typing and Editing for Learners and Teachers</b>, <a href="https://pinkwrite.com"><small><i>powered by PinkWrite 99</i></small></a></p>

<?php

// Install page
echo '<h1>Install PinkWrite 99</h1>';

// define create_form_input()
require_once('./includes/form_functions.inc.php');

// The form
echo '
<form action="install.php" method="post">';

// Config exists check
if (!file_exists('./pw99-config.php')) {

if ($no_title_errors == false) {
  echo '<p class="noticered">Please enter a Site title with valid characters.</p>';
}

  echo '<h3>Site info</h3>
  <p class="sans lt"
  Site title: <input type="text" name="site_title"';
  echo (array_key_exists('site_title', $reg_errors)) ? ' class="noticered"><span class="noticered">'.$reg_errors['site_title'].'</span>' : '>' ;
  echo '</p>';

  if ($no_db_cred_errors == false) {
    echo '<p class="noticered">Please use database credentials with valid characters.</p>';
  }

  echo '<h3>Database info</h3>
  <input type="hidden" name="db_inputs">
  <p class="sans lt"
  Database name: <input type="text" name="db_name"><br';
  echo (array_key_exists('db_name', $reg_errors)) ? ' class="noticered"><span class="noticered">'.$reg_errors['db_name'].'</span>' : '>' ;
  echo '><br>
  Database username: <input type="text" name="db_user"';
  echo (array_key_exists('db_user', $reg_errors)) ? ' class="noticered"><span class="noticered">'.$reg_errors['db_user'].'</span>' : '>' ;
  echo '><br><br>
  Database password: <input type="text" name="db_pass"';
  echo (array_key_exists('db_pass', $reg_errors)) ? ' class="noticered"><span class="noticered">'.$reg_errors['db_pass'].'</span>' : '>' ;
  echo '><br><br>
  Database host: (leave as <i>localhost</i> unless told otherwise) <input type="text" name="db_host" value="localhost"';
  echo (array_key_exists('db_host', $reg_errors)) ? ' class="noticered"><span class="noticered">'.$reg_errors['db_host'].'</span>' : '>' ;
  echo '><br><br>
  </p>';

} else {
  echo '
  <p class="sans">Database and config is already set up. This will only create a new admin user.</p>';
}

// Admin signup section
if (($config['allowcreateadmin'] == true) || ($configured != true)) {

  echo '
  <h3>Register New Admin User</h3>
  <p>';

  echo "
  <input type=\"hidden\" name=\"register_admin\" value=\"submitted\" />
  <input type=\"hidden\" name=\"utype\" value=\"admin\" />

	<p><label class=\"sans\" for=\"name\"><strong>Name</strong></label><br /><br />";
	create_form_input('name', 'text', $reg_errors, '');
	echo "</p>

	<p><label class=\"sans\" for=\"username\"><strong>Username</strong><br /><small class =\"sans\">6-32 characters, only letters and numbers, case doesn't matter</small></label><br /><br />";
	create_form_input('username', 'text', $reg_errors, '');
	echo "</p>

	<p><label class=\"sans\" for=\"email1\"><strong>Email</strong></label><br /><br />";
	create_form_input('email1', 'email', $reg_errors, '');
	echo "</p>
	<p><label class=\"sans\" for=\"email2\"><strong>Double-Check Email</strong></label><br /><br />";
	create_form_input('email2', 'email', $reg_errors, '');
	echo "</p>

	<p><label class=\"sans\" for=\"pass1\"><strong>Password</strong><br /><small class =\"sans\">6-32 characters, one lowercase letter, one uppercase letter, one number, special characters allowed: +-!@#$%</small></label><br /><br />";
	create_form_input('pass1', 'password', $reg_errors, '');
	echo "</p>
	<p><label class=\"sans\" for=\"pass2\"><strong>Confirm Password</strong></label><br /><br />";
	create_form_input('pass2', 'password', $reg_errors, '');
	echo "</p>";
}

// Disclaimers
echo"
<input type=\"submit\" name=\"submit_button\" value=\"Register New Admin &rarr;\" id=\"submit_button\" class=\"formbutton\" />
</form>";

?>

</body>
</html>
