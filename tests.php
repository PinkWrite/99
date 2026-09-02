<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'test'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('editor')) {
    $app->redirect('');
}
$app->view->start('Tests', 'tests', 'editor');
echo '<h2 class="lt">Tests</h2>';
echo '<p>' . post_button('New test +', 'Compose a test', 'test.php', 'new_test', '1', 'newNoteButton', $app->csrf->token()) . '</p>';
echo '<table class="list lt sans"><tr><th>Title</th><th>Status</th><th></th></tr>';
$cc = 'lr';
foreach ($app->test->forEditor($app->auth->id()) as $t) {
    echo '<tr class="' . $cc . '"><td>' . h($t['title']) . '</td><td>' . h($t['status']) . '</td><td>';
    echo button('Edit', 'Edit', 'test.php?t=' . (int) $t['id'], 'editNoteButton') . '</td></tr>';
    $cc = $cc === 'lr' ? 'dr' : 'lr';
}
echo '</table>';
$app->view->end();
