<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'test'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('editor')) {
    $app->redirect('');
}
$back = 'tests.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$app->csrf->check()) {
    $app->view->setFlash('That action did not go through. Try again.', false);
    $app->redirect($back);
}
$uid = $app->auth->id();
$op = (string) ($_POST['testsubmit'] ?? '');
$ids = [];
foreach ($_POST as $k => $v) {
    if (str_starts_with((string) $k, 'bulk_') && filter_var($v, FILTER_VALIDATE_INT)) {
        $ids[] = (int) $v;
    }
}
if (!in_array($op, ['archive', 'delete'], true)) {
    $app->view->setFlash('Nothing was changed. Confirm the action if asked.', false);
    $app->redirect($back);
}
if ($ids === []) {
    $app->view->setFlash('Select at least one test.', false);
    $app->redirect($back);
}
$ok = 0;
foreach ($ids as $id) {
    if ($op === 'delete') {
        if ($app->test->deleteOwned($id, $uid)) {
            $ok++;
        }
    } elseif ($app->test->archive($id, $uid)) {
        $ok++;
    }
}
if ($op === 'delete') {
    $app->view->setFlash($ok === 1 ? 'Test deleted.' : $ok . ' tests deleted.', false);
} else {
    $app->view->setFlash($ok === 1 ? 'Test archived.' : $ok . ' tests archived.');
}
$app->redirect($back);
