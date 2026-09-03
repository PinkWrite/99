<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'test', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('editor')) {
    $app->redirect('');
}
$app->view->start('Tests', 'tests', 'editor');
echo '<h2 class="lt">Tests</h2>';
echo '<p>' . post_button('New test +', 'Compose a test', 'test.php', 'new_test', '1', 'newNoteButton', $app->csrf->token()) . '</p>';
$app->writlist->renderEditorTests('tests.php');
$app->view->end();
