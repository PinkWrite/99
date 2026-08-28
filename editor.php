<?php
declare(strict_types=1);
$import = ['auth', 'view', 'html', 'writ', 'block', 'user'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('editor')) {
    $app->redirect('');
}
$app->view->start('Editor', 'editor');
echo '<h2 class="lt">Blocks</h2>';
echo '<p>' . button('Manage blocks', 'Blocks', 'blocks.php', 'set_gray') . '</p>';
echo '<table class="list"><tr><th>Name</th><th>Code</th><th>Status</th></tr>';
$eid = $app->auth->is('editor') ? $app->auth->id() : 0;
$blocks = $eid ? $app->block->forEditor($eid, false) : $app->block->forFacility($app->auth->facilityId(), false);
foreach ($blocks as $b) {
    echo '<tr><td>' . h($b['name']) . '</td><td>' . h($b['code']) . '</td><td>' . h($b['status']) . '</td></tr>';
}
echo '</table>';
echo '<h2 class="lt">Writs</h2>';
echo '<table class="list"><tr><th>Writer</th><th>Title</th><th>Kind</th><th>Status</th><th></th></tr>';
$list = $eid ? $app->writ->forEditor($eid) : $app->db->all(
    'SELECT w.*, u.name AS writer_name FROM writs w JOIN users u ON u.id = w.writer_id WHERE w.review_status=\'current\' ORDER BY w.id DESC LIMIT 100'
);
foreach ($list as $w) {
    echo '<tr><td>' . h($w['writer_name'] ?? '') . '</td><td>' . h($w['title']) . '</td><td>' . h($w['kind']) . '</td><td>' . h($w['draft_status']) . ' / ' . h($w['edits_status']) . '</td><td>';
    echo button('Review', 'Review', 'review.php?w=' . (int) $w['id'], 'editNoteButton') . ' ';
    echo history_button($app->writ->hasHistory($w), 'history.php?w=' . (int) $w['id']);
    echo '</td></tr>';
}
echo '</table>';
$app->view->end();
