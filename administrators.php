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
    $app->redirect('administrators.php');
}
$facilities = $app->facility->all();
$app->view->start('Administrators', 'admins', 'super');
echo '<h2 class="lt">Administrators</h2>';
echo '<form method="get" action="register.php" class="sans">';
echo '<input type="hidden" name="type" value="admin">';
$fopts = [];
foreach ($facilities as $f) {
    $fopts[(int) $f['id']] = $f['name'];
}
echo form_select('facility_id', $fopts, 0, 'Facility…', 'formselect');
echo ' <input type="submit" class="newNoteButton" value="New admin +"></form>';
echo '<h3 class="lt" style="display:inline-block;margin-right:0.75em">Active Administrators</h3>';
echo button('View dormant administrators', 'Dormant administrators', 'admins-dormant.php', 'editNoteButton');
$app->writlist->renderAdminPeople('administrators.php', 'admin', 'active');
$app->view->end();
