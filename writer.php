<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'user', 'writ', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('supervisor')) {
    $app->redirect('');
}
$id = (int) ($_GET['u'] ?? 0);
$w = $app->user->find($id);
if (!$w) {
    $app->redirect('enrollment.php');
}
$app->view->start($w['name'], 'writers', 'admin');
echo '<h2 class="lt">' . h($w['name']) . ' <small class="dk">' . h($w['username']) . '</small></h2>';
$app->writlist->renderEditor('writer.php?u=' . $id);
$app->view->end();
