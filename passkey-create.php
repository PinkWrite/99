<?php
declare(strict_types=1);
$import = ['auth', 'passkey'];
require __DIR__ . '/lib/boot.php';
$u = $app->auth->requireUser();
header('Content-Type: application/json; charset=utf-8');
echo json_encode($app->passkey->optionsCreate($u));
