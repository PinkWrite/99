<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'writ', 'user', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('editor')) {
    $app->redirect('');
}
$app->view->start('Assignments', 'assign', 'editor');
echo '<h2 class="lt">Assignments</h2>';
echo '<p>' . post_button('New assignment +', 'Memo plus a writ for the writer or block', 'assignment.php', 'new_assignment', '1', 'newNoteButton', $app->csrf->token()) . '</p>';
$app->writlist->renderEditorAssignments('assignments.php');
$app->view->end();
