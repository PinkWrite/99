<?php
declare(strict_types=1);
$import = ['auth', 'view', 'html', 'user', 'text'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('editor')) {
    $app->redirect('');
}
$app->view->start('Roll', 'roll', 'editor');
echo '<h2 class="lt">Roll</h2>';
echo '<p class="sans dk">Writers you work with. View only.</p>';
if ($app->auth->is('editor')) {
    $rows = $app->user->listByEditor($app->auth->id());
} else {
    $rows = $app->user->listByFacility($app->auth->facilityId(), 'writer');
}
echo '<table class="list roll lt sans"><tr><th>Name</th><th>Username</th><th>Last seen</th><th></th></tr>';
$cc = 'lr';
foreach ($rows as $w) {
    $seen = writ_when($w['last_seen'] ?? null);
    echo '<tr class="' . $cc . '"><td>' . h((string) $w['name']) . '</td>';
    echo '<td>' . h((string) $w['username']) . '</td>';
    echo '<td><i class="listed_note">' . ($seen !== '' ? h($seen) : '—') . '</i></td>';
    echo '<td>' . get_switch('View writs', 'Writs for this writer', 'writs-editor.php', 'u', (string) $w['id'], 'editNoteButton') . '</td></tr>';
    $cc = $cc === 'lr' ? 'dr' : 'lr';
}
if (!$rows) {
    echo '<tr><td colspan="4" class="lt sans">No writers yet</td></tr>';
}
echo '</table>';
$app->view->end();
