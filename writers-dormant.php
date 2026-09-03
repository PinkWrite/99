<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'user', 'block', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('supervisor')) {
    $app->redirect('editor.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app->csrf->check() && isset($_POST['u'])) {
    $ids = array_map('intval', (array) ($_POST['blocks'] ?? []));
    $app->user->setBlocks((int) $_POST['u'], $ids);
    $app->view->setFlash('Blocks saved.');
    $app->redirect('writers-dormant.php');
}
$app->view->start('Dormant writers', 'writers', 'admin');
echo '<h2 class="lt">Writers</h2>';
echo '<h3 class="lt" style="display:inline-block;margin-right:0.75em">Dormant writers</h3>';
echo button('View active writers', 'Active Writers', 'enrollment.php', 'editNoteButton');
$app->writlist->renderAdminPeople('writers-dormant.php', 'writer', 'dormant');
$app->view->end();
