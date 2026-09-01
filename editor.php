<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'writ', 'block', 'user', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('editor')) {
    $app->redirect('');
}
$u = $app->auth->user();
$app->view->start('Editor Dash for ' . $u['name'], 'editor', 'editor');
echo '<h2 class="lt">Blocks</h2>';
$app->writlist->renderEditorBlocks('blocks-editor.php');
echo '<h2 class="lt">Writs</h2>';
$app->writlist->renderEditor('writs-editor.php');
$app->view->end();
