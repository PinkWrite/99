<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'user', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('supervisor')) {
    $app->redirect('');
}
$app->view->start('Editors', 'editors', 'admin');
echo '<h2 class="lt">Editors</h2>';
echo '<p>' . button('New editor +', 'Register an editor', 'register.php?type=editor', 'newNoteButton') . '</p>';
echo '<h3 class="lt" style="display:inline-block;margin-right:0.75em">Active Editors</h3>';
echo button('View dormant editors', 'Dormant editors', 'editors-dormant.php', 'editNoteButton');
$app->writlist->renderAdminPeople('editors.php', 'editor', 'active');
$app->view->end();
