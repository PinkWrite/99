<?php
declare(strict_types=1);
$import = ['auth', 'view', 'html', 'user', 'text', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('editor')) {
    $app->redirect('');
}
$app->view->start('Roll', 'roll', 'editor');
echo '<h2 class="lt">Roll</h2>';
echo '<p class="sans dk">Writers you work with. View only.</p>';
$app->writlist->renderEditorRoll('roll.php');
$app->view->end();
