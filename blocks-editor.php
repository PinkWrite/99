<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'text', 'block', 'user', 'notify', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('supervisor')) {
    $app->redirect('editor.php');
}
$msg = '';
$fid = $app->auth->facilityId();
$editors = $app->user->listByFacility($fid, 'editor');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app->csrf->check() && isset($_POST['create'])) {
    $eid = (int) ($_POST['editor_id'] ?? 0);
    if ($eid < 1 && $editors) {
        $eid = (int) $editors[0]['id'];
    }
    $app->block->create([
        'facility_id' => $fid,
        'editor_id' => $eid,
        'name' => clean_title($_POST['name'] ?? 'Block', 120),
        'code' => clean_title($_POST['code'] ?? '', 10),
    ]);
    $app->notify->send($app->auth->id(), 'new_block', 'Block created', 'blocks-editor.php');
    $msg = 'Block created.';
}
$app->view->start('Blocks', 'blocks', 'admin');
echo '<h2 class="lt">Blocks</h2>';
if ($msg) {
    echo '<p class="sans noticegreen">' . h($msg) . '</p>';
}
echo '<form method="post">' . $app->csrf->field();
echo '<p class="sans">Name<br><input name="name" required></p>';
echo '<p class="sans">Code<br><input name="code" size="8"></p>';
echo '<p class="sans">Editor<br><select class="formselect" name="editor_id">';
foreach ($editors as $ed) {
    echo '<option value="' . (int) $ed['id'] . '">' . h($ed['name']) . '</option>';
}
echo '</select></p>';
echo '<p><input type="submit" name="create" class="lt_button" value="Create block"></p></form>';
$app->writlist->renderEditorBlocks('blocks-editor.php');
$app->view->end();
