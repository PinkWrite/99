<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'text', 'note'];
require dirname(__DIR__) . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->csrf->check()) {
    $app->json(['ok' => false, 'error' => 'csrf'], 400);
}
$nid = (int) ($_POST['note_id'] ?? 0);
$n = $app->note->find($nid);
$uid = $app->auth->id();
if (!$n || ((int) $n['writer_id'] !== $uid && (int) $n['editor_id'] !== $uid && !$app->auth->atLeast('admin'))) {
    $app->json(['ok' => false, 'error' => 'not found'], 404);
}
$extra = [];
if ($app->auth->atLeast('editor')) {
    if (isset($_POST['type'])) {
        $extra['type'] = $_POST['type'] === 'task' ? 'task' : 'memo';
    }
    if (isset($_POST['editor_set_writer_id'])) {
        $extra['editor_set_writer_id'] = (int) $_POST['editor_set_writer_id'];
    }
    if (isset($_POST['editor_set_block'])) {
        $extra['editor_set_block'] = (int) $_POST['editor_set_block'];
    }
}
$app->note->saveBody($nid, clean_body($_POST['body'] ?? ''), $extra);
$app->json(['ok' => true, 'msg' => 'Saved']);
