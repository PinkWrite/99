<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'writ', 'user', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('editor')) {
    $app->redirect('');
}
$u = $app->auth->user();
$app->view->start('Editor Archives', 'locker', 'editor');
echo '<h2>Editor Archives</h2>';
$app->writlist->renderEditor('archives-editor.php', 'archived');
$app->view->end();
