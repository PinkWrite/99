<?php
declare(strict_types=1);
$import = ['auth', 'view', 'html', 'test'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('editor')) {
    $app->redirect('');
}
$app->view->start('Tests', 'tests', 'editor');
echo '<p>' . button('New test', 'Compose', 'test.php', 'newNoteButton') . '</p>';
echo '<table class="list"><tr><th>Title</th><th>Status</th><th></th></tr>';
foreach ($app->test->forEditor($app->auth->id()) as $t) {
    echo '<tr><td>' . h($t['title']) . '</td><td>' . h($t['status']) . '</td><td>';
    echo button('Edit', 'Edit', 'test.php?t=' . (int) $t['id'], 'editNoteButton') . '</td></tr>';
}
echo '</table>';
$app->view->end();
