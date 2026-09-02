<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'writ'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();

$allow = [
    'archives-editor.php', 'archives.php', 'editor.php', 'writs.php',
    'writs-editor.php', 'writer-dash.php', 'writer.php', 'index.php',
];
$backFrom = static function (?string $raw) use ($allow): string {
    $path = parse_url((string) $raw, PHP_URL_PATH);
    $base = basename($path ?: (string) $raw);
    $base = preg_replace('/\?.*$/', '', $base) ?? $base;
    return in_array($base, $allow, true) ? $base : '';
};
$back = $backFrom($_POST['return'] ?? '');
if ($back === '') {
    $back = $backFrom($_SERVER['HTTP_REFERER'] ?? '');
}
if ($back === '') {
    $back = isset($_POST['editor_archive']) ? 'archives-editor.php'
        : (isset($_POST['writer_archive']) ? 'archives.php' : '');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$app->csrf->check()) {
    $app->view->setFlash('That archive action did not go through. Try again.', false);
    $app->redirect($back);
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
    $app->view->setFlash('You cannot change those archives.', false);
    $app->redirect($back);
}

$needsIds = in_array($op, ['archive', 'restore', 'delete'], true);
if ($needsIds && $ids === []) {
    $app->view->setFlash('Select at least one writ.', false);
    $app->redirect($back);
}

$n = count($ids);
$label = static function (int $n, string $one, string $many) {
    return $n === 1 ? $one : $many;
};

try {
    if ($isWriter) {
        if ($op === 'archive') {
            foreach ($ids as $id) {
                $app->db->run("UPDATE writs SET term_status='archived' WHERE writer_id=? AND id=?", [$uid, $id]);
            }
            $app->view->setFlash($label($n, 'Writ archived.', 'Writs archived.'));
            $app->redirect($back !== '' ? $back : 'writs.php');
        }
        if ($op === 'archive all scored') {
            $app->db->run("UPDATE writs SET term_status='archived' WHERE edits_status='scored' AND writer_id=?", [$uid]);
            $app->view->setFlash('All scored writs archived.');
            $app->redirect($back !== '' ? $back : 'writs.php');
        }
        if ($op === 'restore') {
            foreach ($ids as $id) {
                $app->db->run("UPDATE writs SET term_status='current' WHERE writer_id=? AND id=?", [$uid, $id]);
            }
            $app->view->setFlash($label($n, 'Writ restored.', 'Writs restored.'));
            $app->redirect($back !== '' ? $back : 'archives.php');
        }
        if ($op === 'delete') {
            foreach ($ids as $id) {
                if ($app->db->tableExists('writ_comments')) {
                    $app->db->run('DELETE FROM writ_comments WHERE writ_id=?', [$id]);
                }
                $app->db->run("DELETE FROM writs WHERE term_status='archived' AND writer_id=? AND id=?", [$uid, $id]);
            }
            $app->view->setFlash($label($n, 'Writ deleted.', 'Writs deleted.'), false);
            $app->redirect($back !== '' ? $back : 'archives.php');
        }
    }

    if ($isEditor) {
        $editorCol = $app->db->columnExists('users', 'editor_id') ? 'editor_id' : 'editor';
        $scope = $app->auth->is('editor')
            ? " AND writer_id IN (SELECT id FROM users WHERE {$editorCol}=?)"
            : '';
        $scopeParams = $app->auth->is('editor') ? [$uid] : [];
        if ($op === 'archive') {
            foreach ($ids as $id) {
                $app->db->run("UPDATE writs SET review_status='archived' WHERE id=?{$scope}", array_merge([$id], $scopeParams));
            }
            $app->view->setFlash($label($n, 'Writ archived.', 'Writs archived.'));
            $app->redirect($back !== '' ? $back : 'editor.php');
        }
        if ($op === 'restore') {
            foreach ($ids as $id) {
                $app->db->run("UPDATE writs SET review_status='current' WHERE id=?{$scope}", array_merge([$id], $scopeParams));
            }
            $app->view->setFlash($label($n, 'Writ restored.', 'Writs restored.'));
            $app->redirect($back !== '' ? $back : 'archives-editor.php');
        }
        if ($op === 'delete') {
            foreach ($ids as $id) {
                if ($app->db->tableExists('writ_comments')) {
                    $app->db->run('DELETE FROM writ_comments WHERE writ_id=?', [$id]);
                }
                $app->db->run("DELETE FROM writs WHERE review_status='archived' AND id=?{$scope}", array_merge([$id], $scopeParams));
            }
            $app->view->setFlash($label($n, 'Writ deleted.', 'Writs deleted.'), false);
            $app->redirect($back !== '' ? $back : 'archives-editor.php');
        }
        if ($op === 'archive all scored') {
            if ($app->auth->is('editor')) {
                $app->db->run(
                    "UPDATE writs w JOIN users u ON u.id = w.writer_id
                     SET w.review_status='archived'
                     WHERE w.edits_status='scored' AND u.{$editorCol}=?",
                    [$uid]
                );
            } else {
                $app->db->run("UPDATE writs SET review_status='archived' WHERE edits_status='scored'");
            }
            $app->view->setFlash('All scored writs archived.');
            $app->redirect($back !== '' ? $back : 'editor.php');
        }
    }
} catch (Throwable $e) {
    $app->view->setFlash('Could not update archives.', false);
    $app->redirect($back !== '' ? $back : ($isEditor ? 'archives-editor.php' : 'archives.php'));
}

$app->view->setFlash('Nothing was changed. Confirm the action if asked.', false);
$app->redirect($back);
