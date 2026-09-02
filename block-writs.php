<?php
declare(strict_types=1);
$import = ['auth', 'view', 'html', 'block', 'writ', 'user'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('supervisor')) {
    $app->redirect('');
}
$bid = (int) ($_GET['v'] ?? 0);
$b = $bid ? $app->block->find($bid) : null;
if (!$b) {
    $app->redirect('blocks-editor.php');
}
$col = $app->db->columnExists('writs', 'block_id') ? 'block_id' : 'block';
$rows = $app->db->all(
    "SELECT w.*, u.name AS writer_name FROM writs w
     LEFT JOIN users u ON u.id = w.writer_id
     WHERE w.{$col} = ? AND w.review_status = 'current'
     ORDER BY w.id DESC",
    [$bid]
);
$app->view->start('Block writs', 'blocks', 'admin');
echo '<h2 class="lt">Writs · ' . h($app->block->named($b)) . '</h2>';
echo '<p>' . button('Back to blocks', 'Blocks', 'blocks-editor.php', 'set_gray') . '</p>';
if (!$rows) {
    echo '<p class="lt sans">No writs in this block.</p>';
    $app->view->end();
    exit;
}
echo '<table class="list writ lt sans"><tbody><tr><th></th><th>Work</th><th>Title</th><th>Status</th><th>Writer</th></tr>';
$cc = 'lr';
foreach ($rows as $w) {
    echo '<tr class="' . $cc . '"><td>' . get_switch('Open', 'Read this writ', 'writ.php', 'w', (string) $w['id'], 'set_writ_blue') . '</td>';
    echo '<td>' . h(writ_work($w['work'] ?? '', (int) $w['id'])) . '</td>';
    echo '<td>' . h(writ_title($w['title'] ?? '')) . '</td>';
    echo '<td>' . h((string) $w['draft_status']) . '</td>';
    echo '<td>' . h((string) ($w['writer_name'] ?? '')) . '</td></tr>';
    $cc = $cc === 'lr' ? 'dr' : 'lr';
}
echo '</tbody></table>';
$app->view->end();
