<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'user'];
require __DIR__ . '/lib/boot.php';

$token = (string) ($_GET['t'] ?? $_POST['t'] ?? '');
$err = '';
$ok = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app->csrf->check()) {
    $u = $app->user->consumeReset($token);
    if (!$u) {
        $err = 'That link is invalid or expired.';
    } elseif (!$app->auth->canEmailReset($u)) {
        $err = 'This account cannot reset by email.';
    } else {
        $p1 = (string) ($_POST['pass1'] ?? '');
        $p2 = (string) ($_POST['pass2'] ?? '');
        if (strlen($p1) < 8 || $p1 !== $p2) {
            $err = 'Passwords must match and be at least 8 characters.';
        } else {
            $app->user->setPassword((int) $u['id'], $p1);
            $ok = 'Password changed. You can log in.';
        }
    }
}

$app->view->start('Reset password', 'login');
echo '<h2 class="lt">Reset password</h2>';
if ($err) {
    echo '<p class="sans noticered">' . h($err) . '</p>';
}
if ($ok) {
    echo '<p class="sans noticegreen">' . h($ok) . ' <a href="login.php">Login</a></p>';
} else {
    echo '<form method="post">' . $app->csrf->field();
    echo '<input type="hidden" name="t" value="' . h($token) . '">';
    echo '<p class="sans">New password<br><input type="password" name="pass1" required></p>';
    echo '<p class="sans">Confirm<br><input type="password" name="pass2" required></p>';
    echo '<p><input type="submit" class="lt_button" value="Set password"></p></form>';
}
$app->view->end();
