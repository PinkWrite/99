<?php
declare(strict_types=1);
$import = ['auth', 'view', 'html', 'note', 'user'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
$u = $app->auth->user();
$app->view->start('Binder', 'binder');
$ids = json_arr($u['observing_json'] ?? '[]');
echo '<h2 class="lt">Memos for observees</h2>';
foreach ($ids as $wid) {
    $wid = (int) $wid;
    if ($wid < 1) {
        continue;
    }
    $w = $app->user->find($wid);
    echo '<h3 class="lt">' . h($w['name'] ?? ('#' . $wid)) . '</h3>';
    echo '<table class="list">';
    foreach ($app->note->memosForWriter($wid) as $n) {
        echo '<tr><td>' . h($n['save_date']) . '</td><td>' . h(substr((string) $n['body'], 0, 100)) . '</td></tr>';
    }
    echo '</table>';
}
$app->view->end();
