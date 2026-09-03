<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'facility', 'user', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->is('superintendent')) {
    $app->redirect('');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app->csrf->check() && isset($_POST['save_admin_facilities'], $_POST['u'])) {
    $aid = (int) $_POST['u'];
    $picked = array_map('intval', (array) ($_POST['facilities'] ?? []));
    $app->user->setFacility($aid, $picked[0] ?? null);
    $app->view->setFlash('Administrator facilities saved.');
    $app->redirect('admins-dormant.php');
}
$app->view->start('Dormant administrators', 'admins', 'super');
echo '<h2 class="lt">Administrators</h2>';
echo '<h3 class="lt" style="display:inline-block;margin-right:0.75em">Dormant administrators</h3>';
echo button('View active administrators', 'Active Administrators', 'administrators.php', 'editNoteButton');
$app->writlist->renderAdminPeople('admins-dormant.php', 'admin', 'dormant');
$app->view->end();
