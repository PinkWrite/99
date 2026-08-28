<?php
declare(strict_types=1);
$import = ['auth', 'view', 'html', 'writ', 'note', 'user'];
require __DIR__ . '/lib/boot.php';

if (!$app->auth->user()) {
    $app->redirect('login.php');
}
$u = $app->auth->user();
$app->view->start($app->title(), 'dash');
echo '<p class="sans dk">Typing and Editing for Learners and Teachers.</p>';

if ($app->auth->is('writer') || $app->auth->is('observer')) {
    echo '<p>' . button('New writ +', 'Start writing', 'writ.php', 'set_gray') . '</p>';
    $list = $app->auth->is('writer')
        ? $app->writ->forWriter($app->auth->id())
        : [];
    echo '<h2 class="lt">Writs</h2>';
    echo '<table class="list"><tr><th>Work</th><th>Title</th><th>Kind</th><th>Status</th><th></th></tr>';
    foreach ($list as $w) {
        echo '<tr><td>' . h($w['work']) . '</td><td>' . h($w['title']) . '</td><td>' . h($w['kind']) . '</td><td>' . h($w['draft_status']) . '</td><td>';
        echo button('Open', 'Open', 'writ.php?w=' . (int) $w['id'], 'editNoteButton');
        echo ' ' . history_button($app->writ->hasHistory($w), 'history.php?w=' . (int) $w['id']);
        echo '</td></tr>';
    }
    echo '</table>';
}

if ($app->auth->atLeast('editor')) {
    echo '<p>' . button('Editor Dash', 'Editor', 'editor.php', 'navDarkButton') . ' ';
    echo button('New assignment', 'From a memo', 'assignment.php', 'set_gray') . ' ';
    echo button('New test', 'Compose a test', 'test.php', 'set_gray') . '</p>';
}
$app->view->end();
