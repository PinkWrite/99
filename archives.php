<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'writ', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if ($app->auth->is('observer')) {
    $app->redirect('observer.php');
}
$u = $app->auth->user();
$app->view->start('Archives', 'locker', 'writer');
echo '<h2 class="lt">Archives</h2>';
$app->writlist->renderWriter('archives.php', 'archived');
$app->view->end();
