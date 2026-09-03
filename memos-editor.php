<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'note', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('editor')) {
    $app->redirect('');
}
$app->view->start('Memos', 'memos', 'editor');
echo '<p>' . post_button('New memo +', 'Create a memo', 'note.php', 'new_note', '1', 'newNoteButton', $app->csrf->token()) . '</p>';
$bid = (int) ($_GET['b'] ?? 0);
$where = $bid > 0 ? 'memos-editor.php?b=' . $bid : 'memos-editor.php';
$app->writlist->renderEditorMemos($where);
$app->view->end();
