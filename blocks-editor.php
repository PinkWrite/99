<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'text', 'block', 'notify', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('editor')) {
    $app->redirect('');
}
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app->csrf->check() && isset($_POST['create'])) {
    $app->block->create([
        'facility_id' => $app->auth->facilityId(),
        'editor_id' => $app->auth->is('editor') ? $app->auth->id() : (int) ($_POST['editor_id'] ?? $app->auth->id()),
        'name' => clean_title($_POST['name'] ?? 'Block', 120),
        'code' => clean_title($_POST['code'] ?? '', 10),
    ]);
    $app->notify->send($app->auth->id(), 'new_block', 'Block created', 'blocks-editor.php');
    $msg = 'Block created.';
}
$u = $app->auth->user();
$app->view->start('Blocks', 'blocks', 'editor');
echo '<h2 class="lt">Blocks</h2>';
if ($msg) {
    echo '<p class="sans noticegreen">' . h($msg) . '</p>';
}
echo '<form method="post">' . $app->csrf->field();
echo '<p class="sans">Name <input name="name" required> Code <input name="code" size="8"> ';
echo '<input type="submit" name="create" class="lt_button" value="Create block"></p></form>';
$app->writlist->renderEditorBlocks('blocks-editor.php');
$app->view->end();
