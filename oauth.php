<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'oauth', 'user'];
require __DIR__ . '/lib/boot.php';

$p = (string) ($_GET['p'] ?? $_POST['p'] ?? '');
$link = isset($_GET['link']) || isset($_POST['link']);

if (isset($_GET['code'], $_GET['state'])) {
    $r = $app->oauth->finish((string) $_GET['code'], (string) $_GET['state']);
    if (!empty($r['need']) && $r['need'] === 'totp') {
        $app->redirect('login.php');
    }
    if (!empty($r['ok'])) {
        $app->redirect($link || $app->auth->user() ? 'security.php' : '');
    }
    $_SESSION['oauth_err'] = $r['error'] ?? 'Sign-in failed.';
    $app->redirect('login.php');
}

if (in_array($p, ['google', 'apple', 'github'], true) && $app->oauth->enabled($p)) {
    header('Location: ' . $app->oauth->start($p, $link));
    exit;
}

$app->redirect('login.php');
