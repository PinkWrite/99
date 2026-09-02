<?php
declare(strict_types=1);
$import = ['auth', 'view', 'html', 'note', 'user'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->is('observer') && !$app->auth->atLeast('editor')) {
    $app->redirect('');
}
$u = $app->auth->user();
$ids = $app->user->observeeIds($u);
$wid = (int) ($_GET['w'] ?? 0);
if ($wid > 0 && in_array($wid, $ids, true)) {
    $ids = [$wid];
}
$app->view->start('Binder', 'binder', 'observer');
echo '<h2 class="lt">Memos for observees</h2>';
if ($wid > 0 && $ids === [$wid]) {
    $one = $app->user->find($wid);
    echo '<p>' . button('Observe from all', 'All observees', 'binder-observer.php', 'navButton') . '</p>';
    if ($one) {
        echo '<h3 class="lt">' . h($one['name']) . '</h3>';
    }
}
foreach ($ids as $oid) {
    $oid = (int) $oid;
    if ($oid < 1) {
        continue;
    }
    $w = $app->user->find($oid);
    if ($wid < 1) {
        echo '<h3 class="lt">' . h($w['name'] ?? ('#' . $oid)) . '</h3>';
    }
    echo '<table class="list lt sans"><tbody>';
    $cc = 'lr';
    $memos = $app->note->memosForWriter($oid);
    if (!$memos) {
        echo '<tr class="' . $cc . '"><td class="lt sans">No memos</td></tr>';
    }
    foreach ($memos as $n) {
        echo '<tr class="' . $cc . '"><td>' . h((string) $n['save_date']) . '</td>';
        echo '<td>' . h(substr((string) $n['body'], 0, 100)) . '</td>';
        echo '<td>' . button('Open', 'Open', 'note.php?n=' . (int) $n['id'], 'editNoteButton') . '</td></tr>';
        $cc = $cc === 'lr' ? 'dr' : 'lr';
    }
    echo '</tbody></table>';
}
$app->view->end();
