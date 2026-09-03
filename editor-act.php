<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'user', 'audit'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('supervisor')) {
    $app->redirect('editor.php');
}

$back = pw99_editors_return();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$app->csrf->check()) {
    $app->view->setFlash('That editor action did not go through. Try again.', false);
    $app->redirect($back);
}

$op = (string) ($_POST['editorsubmit'] ?? '');
$ids = [];
foreach ($_POST as $k => $v) {
    if (str_starts_with((string) $k, 'bulk_') && filter_var($v, FILTER_VALIDATE_INT)) {
        $ids[] = (int) $v;
    }
}
if ($ids === []) {
    $app->view->setFlash('Select at least one editor.', false);
    $app->redirect($back);
}

$ok = 0;
foreach ($ids as $id) {
    $u = $app->user->find($id);
    if (!$u || ($u['type'] ?? '') !== 'editor' || !$app->auth->canManageAccount($u)) {
        continue;
    }
    if ($op === 'dormant') {
        $app->user->setStatus($id, 'dormant');
        $app->audit->record($id, 'status', 'dormant');
        $ok++;
    } elseif ($op === 'activate') {
        $app->user->setStatus($id, 'active');
        $app->audit->record($id, 'status', 'active');
        $ok++;
    } elseif ($op === 'delete') {
        $app->user->delete($id);
        $app->audit->record($id, 'delete', 'editor');
        $ok++;
    }
}

if ($op === 'dormant') {
    $app->view->setFlash($ok === 1 ? 'Editor set dormant.' : $ok . ' editors set dormant.');
} elseif ($op === 'activate') {
    $app->view->setFlash($ok === 1 ? 'Editor activated.' : $ok . ' editors activated.');
} elseif ($op === 'delete') {
    $app->view->setFlash($ok === 1 ? 'Editor deleted.' : $ok . ' editors deleted.', false);
} else {
    $app->view->setFlash('Nothing was changed. Confirm the action if asked.', false);
}
$app->redirect($back);
