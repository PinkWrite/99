<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'note'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
$app->view->start('Notes', 'notes');
echo '<p>' . post_button('New note +', 'Create', 'note.php', 'new_note', '1', 'newNoteButton', $app->csrf->token()) . '</p>';
echo '<table class="list"><tr><th>Updated</th><th>Preview</th><th></th></tr>';
foreach ($app->note->forWriter($app->auth->id(), 'note') as $n) {
    echo '<tr><td>' . h($n['save_date']) . '</td><td>' . h(substr((string) $n['body'], 0, 80)) . '</td><td>';
    echo button('Open', 'Open', 'note.php?n=' . (int) $n['id'], 'editNoteButton') . '</td></tr>';
}
echo '</table>';
$app->view->end();
