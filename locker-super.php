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
echo '<p>' . button('Mail', 'Domains, boxes, aliases, BIMI', 'locker-mail.php', 'set_gray') . '</p>';
echo '<p>' . button('Facilities', 'Schools', 'facilities.php', 'set_gray') . '</p>';
echo '<p>' . button('Update app', 'Git pull + SQL', 'update-app.php', 'set_gray') . '</p>';
$app->view->end();
