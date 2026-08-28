<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'writ'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
$app->view->start('Writs', 'writs');
echo '<p>' . post_button('New writ +', 'New', 'writ.php', 'new_writ', (string) $app->auth->id(), 'newNoteButton', $app->csrf->token()) . '</p>';
echo '<table class="list"><tr><th>Work</th><th>Title</th><th>Kind</th><th>Status</th><th></th></tr>';
foreach ($app->writ->forWriter($app->auth->id()) as $w) {
    echo '<tr><td>' . h($w['work']) . '</td><td>' . h($w['title']) . '</td><td>' . h($w['kind']) . '</td><td>' . h($w['draft_status']) . '</td><td>';
    $href = $w['kind'] === 'test' ? 'take-test.php?w=' : 'writ.php?w=';
    echo button('Open', 'Open', $href . (int) $w['id'], 'editNoteButton') . ' ';
    echo history_button($app->writ->hasHistory($w), 'history.php?w=' . (int) $w['id']);
    echo '</td></tr>';
}
echo '</table>';
$app->view->end();
