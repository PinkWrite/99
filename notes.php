<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'note', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if ($app->auth->is('observer')) {
    $app->redirect('observer.php');
}
$app->view->start('Notes', 'notes', 'writer');
echo '<br>';
echo button('Memos →', 'View all notes from your editor and blocks', 'memos.php', 'editNoteButton');
echo '<h2 class="lt">My Notes</h2>';
echo post_button('New note +', 'Start a new note', 'note.php', 'new_note', (string) $app->auth->id(), 'newNoteButton', $app->csrf->token());
echo '<br>';
$app->writlist->renderNotes('notes.php');
$app->view->end();
