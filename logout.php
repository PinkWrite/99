<?php
declare(strict_types=1);
$import = ['auth'];
require __DIR__ . '/lib/boot.php';
$app->auth->logout();
$_SESSION['logout'] = true;
$app->redirect('login.php');
