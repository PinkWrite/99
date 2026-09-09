<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'user', 'passkey', 'oauth', 'audit'];
require dirname(__DIR__) . '/lib/boot.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $app->json(['ok' => false, 'error' => 'POST only'], 405);
}
$u = $app->auth->requireUser();
if (!$app->csrf->check()) {
    $app->json(['ok' => false, 'error' => 'csrf'], 400);
}
$id = $app->auth->id();
$pks = $app->passkey->list($id);
$oauths = $app->oauth->list($id);
if ($pks === [] || $oauths === []) {
    $app->json(['ok' => false, 'error' => 'Need a passkey and a linked login first.'], 400);
}
$off = isset($_POST['disable_password']) && (string) $_POST['disable_password'] !== '0';
if ($off) {
    $app->user->setPassLogin($id, false);
    $app->audit->record($id, 'password_off', 'self');
    $app->json(['ok' => true, 'msg' => 'Saved', 'off' => true]);
}
if (empty($u['pass'])) {
    $app->json(['ok' => false, 'error' => 'Set a password first.', 'off' => true], 400);
}
$app->user->setPassLogin($id, true);
$app->audit->record($id, 'password_on', 'self');
$app->json(['ok' => true, 'msg' => 'Saved', 'off' => false]);
