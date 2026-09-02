<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'writ', 'user', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('editor')) {
    $app->redirect('');
}
$u = $app->auth->user();
$filter = (int) ($_GET['u'] ?? 0);
$who = $filter ? $app->user->find($filter) : null;
$app->view->start('Editor Writs for ' . $u['name'], $who ? 'roll' : 'ewrits', 'editor');
if ($who) {
    echo '<h2 class="lt">Writs · ' . h((string) $who['name']) . ' <small class="dk">(' . h((string) $who['username']) . ')</small></h2>';
    echo '<p>' . button('Back to roll', 'Roll', 'roll.php', 'editNoteButton') . '</p>';
} else {
    echo '<h2 class="lt">Writs</h2>';
}
$app->writlist->renderEditor('writs-editor.php');
$app->view->end();
