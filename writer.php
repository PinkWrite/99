<?php
declare(strict_types=1);
$import = ['auth', 'view', 'html', 'user', 'writ'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('editor')) {
    $app->redirect('');
}
$id = (int) ($_GET['u'] ?? 0);
$w = $app->user->find($id);
if (!$w) {
    $app->redirect('enrollment.php');
}
$app->view->start($w['name'], 'roll');
echo '<h2 class="lt">' . h($w['name']) . ' <small class="dk">' . h($w['username']) . '</small></h2>';
echo '<table class="list"><tr><th>Title</th><th>Status</th><th></th></tr>';
foreach ($app->writ->forWriter($id) as $writ) {
    echo '<tr><td>' . h($writ['title']) . '</td><td>' . h($writ['draft_status']) . '</td><td>';
    echo button('Review', 'Review', 'review.php?w=' . (int) $writ['id'], 'editNoteButton') . ' ';
    echo history_button($app->writ->hasHistory($writ), 'history.php?w=' . (int) $writ['id']);
    echo '</td></tr>';
}
echo '</table>';
$app->view->end();
