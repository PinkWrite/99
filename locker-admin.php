<?php
declare(strict_types=1);
$import = ['auth', 'view', 'html'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('supervisor')) {
    $app->redirect('locker.php');
}
$app->view->start('Admin Locker', 'locker', 'admin');
echo '<h2 class="lt">Admin Locker</h2>';
echo '<p>' . button('Failed logins', 'Clickathon', 'login-fails.php', 'set_gray') . '</p>';
echo '<p>' . button('Staffing', 'Users', 'staffing.php', 'set_gray') . '</p>';
$app->view->end();
