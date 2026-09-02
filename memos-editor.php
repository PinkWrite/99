<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'note'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('editor')) {
    $app->redirect('');
}
$app->view->start('Memos', 'memos', 'editor');
echo '<p>' . post_button('New memo +', 'Create a memo', 'note.php', 'new_note', '1', 'newNoteButton', $app->csrf->token()) . '</p>';
$bid = (int) ($_GET['b'] ?? 0);
$memos = $app->note->memosForEditor($app->auth->id());
if ($bid > 0) {
    $memos = array_values(array_filter($memos, static fn ($n) => (int) ($n['editor_set_block'] ?? 0) === $bid));
}
echo '<table class="list lt sans"><tr><th>Type</th><th>When</th><th>Preview</th><th></th></tr>';
$cc = 'lr';
foreach ($memos as $n) {
    echo '<tr class="' . $cc . '"><td>' . h($n['type']) . '</td><td>' . h($n['save_date']) . '</td><td>' . h(substr((string) $n['body'], 0, 80)) . '</td><td>';
    echo get_switch('Open', 'Open', 'note.php', 'n', (string) $n['id'], 'editNoteButton') . ' ';
    echo button('Assign', 'Assignment', 'assignment.php?m=' . (int) $n['id'], 'editNoteButton');
    echo '</td></tr>';
    $cc = $cc === 'lr' ? 'dr' : 'lr';
}
echo '</table>';
$app->view->end();
