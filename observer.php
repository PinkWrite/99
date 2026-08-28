<?php
declare(strict_types=1);
$import = ['auth', 'view', 'html', 'user', 'writ'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->is('observer') && !$app->auth->atLeast('editor')) {
    $app->redirect('');
}
$u = $app->auth->user();
$ids = json_arr($u['observing_json'] ?? '[]');
if ($app->auth->atLeast('editor') && !$ids) {
    foreach ($app->user->writersForEditor($app->auth->id()) as $wr) {
        $ids[] = (int) $wr['id'];
    }
}
$app->view->start('Observer', 'observer');
echo '<h2 class="lt">Observees</h2>';
echo '<table class="list"><tr><th>Writer</th><th>Writs</th></tr>';
foreach ($ids as $id) {
    $id = (int) $id;
    if ($id < 1) {
        continue;
    }
    $w = $app->user->find($id);
    if (!$w) {
        continue;
    }
    echo '<tr><td>' . h($w['name']) . '</td><td>';
    foreach ($app->writ->forWriter($id) as $writ) {
        echo button(h($writ['title'] ?: 'Writ'), 'Open', 'writ.php?w=' . (int) $writ['id'], 'editNoteButton') . ' ';
        echo history_button($app->writ->hasHistory($writ), 'history.php?w=' . (int) $writ['id']) . ' ';
    }
    echo '</td></tr>';
}
echo '</table>';
$app->view->end();
