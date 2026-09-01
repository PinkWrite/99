<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'writ', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if ($app->auth->is('observer')) {
    $app->redirect('observer.php');
}
$u = $app->auth->user();
$app->view->start('Writs', 'writs', 'writer');
echo '<h2 class="lt">Your Writs</h2>';
echo post_button('New writ +', 'Start writing something new', 'writ.php', 'new_writ', (string) $app->auth->id(), 'set_gray', $app->csrf->token());
$app->writlist->renderWriter('writs.php');
$app->view->end();
