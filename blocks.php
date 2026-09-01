<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'block', 'user', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if ($app->auth->is('observer')) {
    $app->redirect('observer.php');
}
$app->view->start('My Blocks', 'blocks', 'writer');
echo '<h2 class="lt">My Blocks</h2>';
$app->writlist->renderWriterBlocks('blocks.php');
$app->view->end();
