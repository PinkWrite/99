<?php
declare(strict_types=1);
$import = ['auth', 'view', 'html', 'note', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
$app->view->start('Memos', 'memos', 'writer');
echo '<h2 class="lt">Memos</h2>';
$where = isset($_GET['b']) ? 'memos.php?b=' . (int) $_GET['b'] : 'memos.php';
$app->writlist->renderWriterMemos($where);
$app->view->end();
