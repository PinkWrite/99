<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'writ'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$app->csrf->check()) {
    $app->redirect('');
}
$uid = $app->auth->id();
$op = (string) ($_POST['bluksubmit'] ?? '');
$ids = [];
foreach ($_POST as $k => $v) {
    if (str_starts_with((string) $k, 'bulk_') && filter_var($v, FILTER_VALIDATE_INT)) {
        $ids[] = (int) $v;
    }
}

$isWriter = isset($_POST['writer_archive']) && (int) $_POST['writer_archive'] === $uid;
$isEditor = isset($_POST['editor_archive']) && (int) $_POST['editor_archive'] === $uid && $app->auth->atLeast('editor');
if (!$isWriter && !$isEditor) {
    $app->redirect('');
}

$check = (string) ($_POST['checksubmit'] ?? '');

try {
    if ($isWriter) {
        if ($op === 'archive' && $check === '') {
            foreach ($ids as $id) {
                $app->db->run("UPDATE writs SET term_status='archived' WHERE writer_id=? AND id=?", [$uid, $id]);
            }
            $app->view->setFlash('Writ(s) archived.');
            $app->redirect('writs.php');
        }
        if ($op === 'archive all scored' && $check === 'archive_selected') {
            $app->db->run("UPDATE writs SET term_status='archived' WHERE edits_status='scored' AND writer_id=?", [$uid]);
            $app->view->setFlash('All scored writs archived.');
            $app->redirect('writs.php');
        }
        if ($op === 'restore' && $check === '') {
            foreach ($ids as $id) {
                $app->db->run("UPDATE writs SET term_status='current' WHERE writer_id=? AND id=?", [$uid, $id]);
            }
            $app->view->setFlash('Writ(s) restored.');
            $app->redirect('archives.php');
        }
        if ($op === 'delete' && $check === 'delete') {
            foreach ($ids as $id) {
                $app->db->run("DELETE FROM writs WHERE term_status='archived' AND writer_id=? AND id=?", [$uid, $id]);
            }
            $app->view->setFlash('Writ(s) deleted.');
            $app->redirect('archives.php');
        }
    }

    if ($isEditor) {
        $scope = $app->auth->is('editor')
            ? ' AND writer_id IN (SELECT id FROM users WHERE editor_id=?)'
            : '';
        $scopeParams = $app->auth->is('editor') ? [$uid] : [];
        if ($op === 'archive' && $check === '') {
            foreach ($ids as $id) {
                $app->db->run("UPDATE writs SET review_status='archived' WHERE id=?{$scope}", array_merge([$id], $scopeParams));
            }
            $app->view->setFlash('Writ(s) archived.');
            $app->redirect('editor.php');
        }
        if ($op === 'restore' && $check === '') {
            foreach ($ids as $id) {
                $app->db->run("UPDATE writs SET review_status='current' WHERE id=?{$scope}", array_merge([$id], $scopeParams));
            }
            $app->view->setFlash('Writ(s) restored.');
            $app->redirect('archives-editor.php');
        }
        if ($op === 'delete' && $check === 'delete') {
            foreach ($ids as $id) {
                $app->db->run("DELETE FROM writs WHERE review_status='archived' AND id=?{$scope}", array_merge([$id], $scopeParams));
            }
            $app->view->setFlash('Writ(s) deleted.');
            $app->redirect('archives-editor.php');
        }
        if ($op === 'archive all scored' && $check === 'archive_selected') {
            if ($app->auth->is('editor')) {
                $app->db->run(
                    "UPDATE writs w JOIN users u ON u.id = w.writer_id
                     SET w.review_status='archived'
                     WHERE w.edits_status='scored' AND u.editor_id=?",
                    [$uid]
                );
            } else {
                $app->db->run("UPDATE writs SET review_status='archived' WHERE edits_status='scored'");
            }
            $app->view->setFlash('All scored writs archived.');
            $app->redirect('editor.php');
        }
    }
} catch (Throwable $e) {
    $app->view->setFlash('Could not update archives.', false);
    $app->redirect($isEditor ? 'editor.php' : 'writs.php');
}

$app->redirect('');
