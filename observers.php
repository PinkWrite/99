<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'user', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('supervisor')) {
    $app->redirect('');
}
$app->view->start('Observers', 'observers', 'admin');
echo '<h2 class="lt">Observers</h2>';
echo '<p>' . button('New observer +', 'Register an observer', 'register.php?type=observer', 'newNoteButton') . '</p>';
echo '<h3 class="lt" style="display:inline-block;margin-right:0.75em">Active Observers</h3>';
echo button('View dormant observers', 'Dormant observers', 'observers-dormant.php', 'editNoteButton');
$app->writlist->renderAdminPeople('observers.php', 'observer', 'active');
$app->view->end();
