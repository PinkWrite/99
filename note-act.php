<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'note'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$app->csrf->check()) {
    $app->redirect('');
}
$uid = $app->auth->id();
if (isset($_POST['pin'])) {
    $app->note->pin((int) $_POST['pin'], $uid, true);
    $app->redirect('notes.php');
}
if (isset($_POST['unpin'])) {
    $app->note->pin((int) $_POST['unpin'], $uid, false);
    $app->redirect('notes.php');
}
if (isset($_POST['undash'])) {
    $app->note->pin((int) $_POST['undash'], $uid, false);
    $app->redirect('');
}
$app->redirect('');
