<?php

// Require the configuration before any PHP code as the configuration controls error reporting
require('./pw99-config.php');

// A settings page requires form functions
require_once('./includes/form_functions.inc.php');

// We need database connection

// Include the header file
$page_title = "Log In :: $siteTitle";
include('./includes/header.html');

// Login cluster
  // We need this first to check errors
  include('./includes/login_check.inc.php');

  // Redirect Logged-in users
  if (isset($_SESSION['user_id'])) {
    header("Location: " . PW99_HOME);
    exit(); // Quit the script
  } else {

    // Clickathon?
    // Recently blocked from this IP?
    $timeNow = date("Y-m-d H:i:s");
    $timeNowEpoch = strtotime($timeNow);
    $lastAllowedFailEpoch = ($timeNowEpoch - (60 * 60));
    $qhack = "SELECT id FROM clickathon WHERE ip='$user_ip' AND time_epoch > '$lastAllowedFailEpoch' AND unlocked IS NULL";
    $rowhack = mysqli_query($dbc, $qhack);
    if (mysqli_num_rows($rowhack) >= 1) {
    	$ip_blocked = true;
    } else {
      $ip_blocked = false;
    }

    if ( ((isset($_SESSION['clickathon_count'])) && ($_SESSION['clickathon_count'] > 5) && ((isset($_SESSION['clickathon_time'])) && ($_SESSION['clickathon_time'] > $lastAllowedFailEpoch))) || $ip_blocked == true) {
      // Clickathon
      echo '<h3>Too many failed logins</h3>';

    } else {
      // Simple header
      echo '<table style="clear: both; float: left; display: block; position: relative; width: auto;" class="plain"><tbody><tr><td><span class="sans dk"><a href="88">Typing practice: 88 Word Hanon</a></span></td><td><span class="sans dk"><a href="https://github.com/PinkWrite/99">GitHub Source</a></span></td><td><span class="sans dk"><a href="'.PW99_HOME.'">Home</a></span></td></tr></tbody></table>
    	<h1 style="clear: both; display: block;">'.SITE_TITLE.'</h1>
    	<p class="dk sans"><b>Typing and Editing for Learners and Teachers</b>, <a href="https://pinkwrite.com"><small><i>powered by PinkWrite 99</i></small></a></p>';

      // Logged out?
      echo ((isset($_SESSION['logout'])) && ($_SESSION['logout'] == true)) ? '<p class="sans">You are now logged out. Bye!</p>' : false ;
      if (isset($_SESSION['logout'])) unset($_SESSION['logout']);

      // Non-logged in users can login
      $lformaction = 'in'; // This must be set for the include to work
      require('./includes/login_form.inc.php'); // This must be a separate file, not a function, so the error checks in login.inc.php will work
    }
  }

// Include the HTML footer
include('./includes/footer.html');
?>
