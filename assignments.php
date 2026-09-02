<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'writ', 'user', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('editor')) {
    $app->redirect('');
}
$u = $app->auth->user();
$app->view->start('Assignments', 'assign', 'editor');
echo '<h2 class="lt">Assignments</h2>';
echo '<p>' . post_button('New assignment +', 'Memo plus a writ for the writer or block', 'assignment.php', 'new_assignment', '1', 'newNoteButton', $app->csrf->token()) . '</p>';
$eid = $app->auth->is('editor') ? $app->auth->id() : 0;
$where = ["w.kind = 'assignment'", "w.review_status = 'current'"];
$params = [];
if ($eid) {
    $col = $app->db->columnExists('users', 'editor_id') ? 'editor_id' : 'editor';
    $where[] = "u.{$col} = ?";
    $params[] = $eid;
}
[$rows] = $app->writ->dashList($where, $params, 'activity', 250);
echo '<table class="list lt sans"><tr><th></th><th>Work</th><th>Title</th><th>Writer</th><th>Status</th></tr>';
if (!$rows) {
    echo '<tr class="lr"><td colspan="5" class="lt sans">No assignments yet.</td></tr>';
}
$cc = 'lr';
foreach ($rows as $w) {
    echo '<tr class="' . $cc . '"><td>' . get_switch('Open', 'Review', 'review.php', 'w', (string) $w['id'], 'set_writ_blue') . '</td>';
    echo '<td>' . h((string) $w['work']) . '</td><td>' . h((string) $w['title']) . '</td>';
    echo '<td>' . h((string) ($w['writer_name'] ?? '')) . '</td><td>' . h((string) $w['draft_status']) . '</td></tr>';
    $cc = $cc === 'lr' ? 'dr' : 'lr';
}
echo '</table>';
$app->view->end();
