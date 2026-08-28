<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'text', 'test'];
require dirname(__DIR__) . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('editor')) {
    $app->json(['ok' => false, 'error' => 'forbidden'], 403);
}
if (!$app->csrf->check()) {
    $app->json(['ok' => false, 'error' => 'csrf'], 400);
}
$tid = (int) ($_POST['test_id'] ?? 0) ?: null;
$r = $app->test->save(
    $app->auth->id(),
    $tid,
    clean_title($_POST['title'] ?? 'Untitled test'),
    (string) ($_POST['source'] ?? ''),
    (int) ($_POST['block'] ?? 0),
    $app->auth->facilityId()
);
$msg = $r['changed'] ? 'Saved — question numbers updated.' : 'Saved';
$app->json(['ok' => true, 'msg' => $msg, 'source' => $r['source'], 'id' => $r['id']]);
