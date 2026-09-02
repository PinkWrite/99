<?php
declare(strict_types=1);
$import = ['auth'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
$app->redirect('locker.php');
