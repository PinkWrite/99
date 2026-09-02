<?php
declare(strict_types=1);
$import = ['auth', 'view', 'html', 'note'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
$app->view->start('Memos', 'memos', 'writer');
echo '<h2 class="lt">Memos</h2><table class="list lt sans"><tr><th>When</th><th>Preview</th><th></th></tr>';
foreach ($app->note->memosForWriter($app->auth->id()) as $n) {
    echo '<tr><td>' . h($n['save_date']) . '</td><td>' . h(substr((string) $n['body'], 0, 80)) . '</td><td>';
    echo button('Open', 'Open', 'note.php?n=' . (int) $n['id'], 'editNoteButton') . '</td></tr>';
}
echo '</table>';
$app->view->end();
