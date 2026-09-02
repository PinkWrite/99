<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'block', 'user', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('editor')) {
    $app->redirect('');
}
$u = $app->auth->user();
$app->view->start('My Blocks', 'myblocks', 'editor');
echo '<h2 class="lt">My Blocks</h2>';
$app->writlist->renderEditorBlocks('my-blocks.php', 'editor');
$app->view->end();
