<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'user', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('supervisor')) {
    $app->redirect('');
}
$app->view->start('Dormant editors', 'editors', 'admin');
echo '<h2 class="lt">Editors</h2>';
echo '<h3 class="lt" style="display:inline-block;margin-right:0.75em">Dormant editors</h3>';
echo button('View active editors', 'Active Editors', 'editors.php', 'editNoteButton');
$app->writlist->renderAdminEditors('editors-dormant.php', 'dormant');
$app->view->end();
