<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'user', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('supervisor')) {
    $app->redirect('');
}
$app->view->start('Dormant observers', 'observers', 'admin');
echo '<h2 class="lt">Observers</h2>';
echo '<h3 class="lt" style="display:inline-block;margin-right:0.75em">Dormant observers</h3>';
echo button('View active observers', 'Active Observers', 'observers.php', 'editNoteButton');
$app->writlist->renderAdminPeople('observers-dormant.php', 'observer', 'dormant');
$app->view->end();
