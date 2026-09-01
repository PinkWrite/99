<?php
declare(strict_types=1);
$import = ['auth', 'view', 'html'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('editor')) {
    $app->redirect('locker.php');
}
$app->view->start('Editor Locker', 'locker', 'editor');
echo '<h2 class="lt">Editor Locker</h2>';
echo '<p>' . button('Password', 'Change password', 'password.php', 'set_gray') . '</p>';
echo '<p>' . button('Security', '2FA and passkeys', 'security.php', 'set_gray') . '</p>';
echo '<p>' . button('Notification settings', 'In-app and email', 'notify-settings.php', 'set_gray') . '</p>';
echo '<p>' . button('Register writer or observer', 'Register', 'register.php', 'set_gray') . '</p>';
echo '<p>' . button('Archives', 'Editor archives', 'archives-editor.php', 'set_gray') . '</p>';
$app->view->end();
