<?php
declare(strict_types=1);
$import = ['auth'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
$id = (int) ($_GET['u'] ?? 0);
$app->redirect($id > 0 ? 'account.php?u=' . $id : 'enrollment.php');
