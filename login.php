<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'text', 'passkey', 'totp', 'oauth'];
require __DIR__ . '/lib/boot.php';

if ($app->auth->user()) {
    $app->redirect('');
}

$ip = client_ip();
$err = '';
$needTotp = !empty($_SESSION['pending_2fa']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['passkey'])) {
    $u = $app->passkey->assert(
        (string) $_POST['id'],
        (string) $_POST['clientData'],
        (string) $_POST['authData'],
        (string) $_POST['sig']
    );
    if ($u && $u['status'] === 'active') {
        $app->auth->establish($u);
        $app->redirect('');
    }
    $err = 'Passkey failed.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['totp_code'])) {
    if ($app->auth->finishTotp((string) $_POST['totp_code'])) {
        $app->redirect('');
    }
    $err = 'That code did not match.';
    $needTotp = true;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    if (!$app->csrf->check()) {
        $err = 'Bad request.';
    } else {
        $r = $app->auth->login((string) $_POST['username'], (string) ($_POST['pass'] ?? ''), $ip);
        if ($r === 'ok') {
            $app->redirect('');
        } elseif ($r === 'totp') {
            $needTotp = true;
        } elseif ($r === 'blocked') {
            $err = 'Too many failed logins from this IP. Try later.';
        } elseif ($r === 'inactive') {
            $err = 'This account is not active.';
        } else {
            $err = 'The username and password do not match those on file.';
        }
    }
}

$app->view->start('Log In', 'login');
if (!empty($_SESSION['oauth_err'])) {
    echo '<p class="sans noticered">' . h((string) $_SESSION['oauth_err']) . '</p>';
    unset($_SESSION['oauth_err']);
}
if (!empty($_SESSION['logout'])) {
    echo '<p class="sans">You are now logged out. Bye!</p>';
    unset($_SESSION['logout']);
}
echo '<table style="clear:both;float:left;display:block;position:relative;width:auto;" class="plain"><tbody><tr>';
echo '<td><span class="sans dk"><a href="88">Typing practice: 88 Word Hanon</a></span></td>';
echo '<td><span class="sans dk"><a href="https://github.com/PinkWrite/99">GitHub Source</a></span></td>';
echo '</tr></tbody></table>';
echo '<h1 style="clear:both;display:block;">' . h($app->title()) . '</h1>';
echo '<p class="dk sans"><b>Typing and Editing for Learners and Teachers</b>, <a href="https://pinkwrite.com"><small><i>powered by PinkWrite 99</i></small></a></p>';
if ($err) {
    echo '<p class="sans noticered">' . h($err) . '</p>';
}
if ($needTotp) {
    echo '<h3 class="lt">Authenticator</h3>';
    echo '<form method="post">' . $app->csrf->field();
    echo '<p class="sans">Code <input name="totp_code" inputmode="numeric" autocomplete="one-time-code" required></p>';
    echo '<p><input type="submit" class="lt_button" value="Verify"></p></form>';
} else {
    echo '<form method="post" action="login.php">' . $app->csrf->field();
    echo '<p class="sans">Username<br><input name="username" required autocomplete="username"></p>';
    echo '<p class="sans">Password<br><input type="password" name="pass" required autocomplete="current-password"></p>';
    echo '<p><input type="submit" class="lt_button" value="Log in"> ';
    echo '<a class="dk sans" href="forgot.php">Forgot password?</a></p></form>';
    echo '<p><button type="button" class="set_gray" id="pkbtn">Sign in with a passkey</button></p>';
    echo '<p class="sans dk">Authenticator and passkeys stay available after Google, Apple, or GitHub.</p>';
    echo '<p>';
    foreach (['google' => 'Google', 'apple' => 'Apple', 'github' => 'GitHub'] as $p => $lab) {
        if ($app->oauth->enabled($p)) {
            echo '<a class="lt_button" href="oauth.php?p=' . $p . '">' . h($lab) . '</a> ';
        }
    }
    echo '</p>';
    echo '<script src="js/pw99.js"></script><script>document.getElementById("pkbtn").onclick=function(){pwPasskeyLogin("passkey-options.php");};</script>';
}
$app->view->end();
