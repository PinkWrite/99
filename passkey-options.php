<?php
declare(strict_types=1);
$import = ['auth', 'passkey'];
require __DIR__ . '/lib/boot.php';
header('Content-Type: application/json; charset=utf-8');
$u = $app->auth->user();
echo json_encode($app->passkey->optionsGet($u ? (int) $u['id'] : null));
