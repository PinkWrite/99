<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'user', 'notify'];
require __DIR__ . '/lib/boot.php';
$u = $app->auth->requireUser();
$msg = $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app->csrf->check()) {
    $cur = (string) ($_POST['current'] ?? '');
    $p1 = (string) ($_POST['pass1'] ?? '');
    $p2 = (string) ($_POST['pass2'] ?? '');
    if (!password_verify($cur, (string) $u['pass'])) {
        $err = 'Current password is incorrect.';
    } elseif (strlen($p1) < 8 || $p1 !== $p2) {
        $err = 'New passwords must match and be at least 8 characters.';
    } else {
        $app->user->setPassword($app->auth->id(), $p1);
        $msg = 'Password changed.';
        if ($u['editor_id']) {
            $app->notify->send((int) $u['editor_id'], 'password_change', $u['username'] . ' changed their password', 'writer.php?u=' . $app->auth->id());
        }
    }
}
$app->view->start('Password', 'locker');
echo '<h2 class="lt">Password</h2>';
if ($err) {
    echo '<p class="sans noticered">' . h($err) . '</p>';
}
if ($msg) {
    echo '<p class="sans noticegreen">' . h($msg) . '</p>';
}
echo '<form method="post">' . $app->csrf->field();
echo '<p class="sans">Current<br><input type="password" name="current" required></p>';
echo '<p class="sans">New<br><input type="password" name="pass1" required></p>';
echo '<p class="sans">Confirm<br><input type="password" name="pass2" required></p>';
echo '<p><input type="submit" class="lt_button" value="Change password"></p></form>';
$app->view->end();
