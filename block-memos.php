<?php
declare(strict_types=1);
$import = ['auth', 'view', 'html', 'block', 'note'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('supervisor')) {
    $app->redirect('');
}
$bid = (int) ($_GET['b'] ?? 0);
$b = $bid ? $app->block->find($bid) : null;
if (!$b) {
    $app->redirect('blocks-editor.php');
}
$app->view->start('Block memos', 'blocks', 'admin');
echo '<h2 class="lt">Memos · ' . h($app->block->named($b)) . '</h2>';
echo '<p>' . button('Back to blocks', 'Blocks', 'blocks-editor.php', 'set_gray') . '</p>';
$rows = $app->note->memosForBlock($bid);
if (!$rows) {
    echo '<p class="lt sans">No memos for this block.</p>';
    $app->view->end();
    exit;
}
echo '<table class="list lt sans"><tbody><tr><th>When</th><th>Preview</th><th></th></tr>';
$cc = 'lr';
foreach ($rows as $n) {
    echo '<tr class="' . $cc . '"><td>' . h((string) $n['save_date']) . '</td>';
    echo '<td>' . h(substr((string) $n['body'], 0, 100)) . '</td>';
    echo '<td>' . button('Open', 'Open', 'note.php?n=' . (int) $n['id'], 'editNoteButton') . '</td></tr>';
    $cc = $cc === 'lr' ? 'dr' : 'lr';
}
echo '</tbody></table>';
$app->view->end();
