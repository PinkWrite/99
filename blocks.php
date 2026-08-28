<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'text', 'block', 'notify'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('editor')) {
    $app->redirect('');
}
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app->csrf->check() && isset($_POST['create'])) {
    $id = $app->block->create([
        'facility_id' => $app->auth->facilityId(),
        'editor_id' => $app->auth->is('editor') ? $app->auth->id() : (int) ($_POST['editor_id'] ?? $app->auth->id()),
        'name' => clean_title($_POST['name'] ?? 'Block', 120),
        'code' => clean_title($_POST['code'] ?? '', 10),
    ]);
    $app->notify->send($app->auth->id(), 'new_block', 'Block created', 'blocks.php');
    $msg = 'Block created.';
}
$app->view->start('Blocks', 'blocks');
echo '<h2 class="lt">Blocks</h2>';
if ($msg) {
    echo '<p class="sans noticegreen">' . h($msg) . '</p>';
}
echo '<form method="post">' . $app->csrf->field();
echo '<p class="sans">Name <input name="name" required> Code <input name="code" size="8"> ';
echo '<input type="submit" name="create" class="lt_button" value="Create block"></p></form>';
echo '<table class="list"><tr><th>Name</th><th>Code</th><th>Status</th><th></th></tr>';
$list = $app->auth->is('editor')
    ? $app->block->forEditor($app->auth->id(), false)
    : $app->block->forFacility($app->auth->facilityId(), false);
foreach ($list as $b) {
    echo '<tr><td>' . h($b['name']) . '</td><td>' . h($b['code']) . '</td><td>' . h($b['status']) . '</td><td>';
    echo button('Open', 'Edit', 'block.php?b=' . (int) $b['id'], 'editNoteButton') . '</td></tr>';
}
echo '</table>';
$app->view->end();
