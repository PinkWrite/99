<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'user'];
require __DIR__ . '/lib/boot.php';
$u = $app->auth->requireUser();
$uid = $app->auth->id();
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app->csrf->check() && isset($_POST['save_contact'])) {
    try {
        $app->user->saveContact($uid, (string) ($_POST['name'] ?? ''), (string) ($_POST['email'] ?? ''));
        $app->view->setFlash('Saved.');
        $app->redirect('locker.php');
    } catch (InvalidArgumentException $e) {
        $err = $e->getMessage();
    }
}

$u = $app->user->find($uid) ?? $u;
$app->view->start('My Locker', 'locker', 'my');
echo '<h2 class="lt">My Locker</h2>';
if ($err) {
    echo '<p class="sans noticered">' . h($err) . '</p>';
}
echo '<form method="post" class="sans">' . $app->csrf->field();
echo '<input type="hidden" name="save_contact" value="1">';
echo '<p class="field">Name<br><input type="text" name="name" class="readBox" maxlength="80" value="' . h((string) $u['name']) . '" required></p>';
echo '<p class="field">Email<br><input type="email" name="email" class="readBox" maxlength="120" value="' . h((string) $u['email']) . '" required></p>';
echo '<p><input type="submit" class="lt_button" value="Save"></p></form>';
echo '<p>' . button('Password', 'Change password', 'password.php', 'set_gray') . '</p>';
echo '<p>' . button('Security', '2FA and passkeys', 'security.php', 'set_gray') . '</p>';
echo '<p>' . button('Notification settings', 'In-app and email', 'notify-settings.php', 'set_gray') . '</p>';
$app->view->end();
