<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'block', 'user', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('supervisor')) {
    $app->redirect('editor.php');
}
$app->view->start('Closed blocks', 'blocks', 'admin');
echo '<h2 class="lt">Blocks</h2>';
echo '<h3 class="lt" style="display:inline-block;margin-right:0.75em">Closed blocks</h3>';
echo button('View open blocks', 'Open blocks', 'blocks-editor.php', 'editNoteButton');
$app->writlist->renderEditorBlocks('blocks-closed.php', 'admin', 'closed');
$app->view->end();
