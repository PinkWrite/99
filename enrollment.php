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
    $app->redirect('enrollment.php');
}
$app->view->start('Writers', 'writers', 'admin');
echo '<h2 class="lt">Writers</h2>';
$focus = (int) ($_GET['b'] ?? 0);
if ($focus > 0) {
    $fb = $app->block->find($focus);
    if ($fb) {
        echo '<p class="sans dk">Block: ' . h($app->block->named($fb)) . '</p>';
    }
}
echo '<p class="sans dk">Writers and the blocks they belong to.</p>';
echo '<p>' . button('New writer +', 'Register a writer', 'register.php?type=writer', 'newNoteButton') . '</p>';
echo '<h3 class="lt" style="display:inline-block;margin-right:0.75em">Active Writers</h3>';
echo button('View dormant writers', 'Dormant writers', 'writers-dormant.php', 'editNoteButton');
$where = 'enrollment.php' . ($focus > 0 ? '?b=' . $focus : '');
$app->writlist->renderAdminPeople($where, 'writer', 'active');
$app->view->end();
