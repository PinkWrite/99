<?php
declare(strict_types=1);
$import = ['auth', 'view', 'html', 'block', 'writ', 'user', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('supervisor')) {
    $app->redirect('');
}
$bid = (int) ($_GET['v'] ?? 0);
$b = $bid ? $app->block->find($bid) : null;
if (!$b) {
    $app->redirect('blocks-editor.php');
}
$app->view->start('Block writs', 'blocks', 'admin');
echo '<h2 class="lt">Writs · ' . h($app->block->named($b)) . '</h2>';
echo '<p>' . button('Back to blocks', 'Blocks', 'blocks-editor.php', 'set_gray') . '</p>';
$app->writlist->renderBlockWrits('block-writs.php?v=' . $bid, $bid);
$app->view->end();
