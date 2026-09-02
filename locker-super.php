<?php
declare(strict_types=1);
$import = ['auth', 'view', 'html'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->is('superintendent')) {
    $app->redirect('locker.php');
}
$app->view->start('Superintendent Locker', 'locker', 'super');
echo '<h2 class="lt">Superintendent Locker</h2>';
echo '<p class="sans dk">Password reset for this seat is in-person: the SysAdmin sets <code>allow_create_super</code> in config and uses install.php.</p>';
echo '<p>' . button('Password (you are logged in)', 'Change password while logged in', 'password.php', 'set_gray') . '</p>';
echo '<p>' . button('Security', '2FA and passkeys', 'security.php', 'set_gray') . '</p>';
echo '<p>' . button('Notification settings', 'In-app and email', 'notify-settings.php', 'set_gray') . '</p>';
echo '<p>' . button('Mail (inkMail)', 'Domains, boxes, aliases, BIMI', 'locker-mail.php', 'set_gray') . '</p>';
echo '<p>' . button('Facilities', 'Schools', 'facilities.php', 'set_gray') . '</p>';
echo '<p>' . button('Update app', 'Git pull + SQL', 'update-app.php', 'set_gray') . '</p>';
$app->view->end();
