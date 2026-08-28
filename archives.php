<?php
declare(strict_types=1);
$import = ['auth', 'view', 'html', 'writ'];
require __DIR__ . '/lib/boot.php';
$u = $app->auth->requireUser();
$app->view->start('Archives', 'locker');
echo '<h2 class="lt">Archives</h2>';
$list = $app->auth->is('writer')
    ? $app->writ->forWriter($app->auth->id(), 'archived')
    : $app->writ->forEditor($app->auth->id(), 'archived');
echo '<table class="list"><tr><th>Title</th><th>Status</th><th></th></tr>';
foreach ($list as $w) {
    echo '<tr><td>' . h($w['title']) . '</td><td>' . h($w['draft_status']) . '</td><td>';
    echo button('Open', 'Open', 'writ.php?w=' . (int) $w['id'], 'editNoteButton') . '</td></tr>';
}
echo '</table>';
$app->view->end();
