<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'text', 'block'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('editor')) {
    $app->redirect('');
}
$bid = (int) ($_GET['b'] ?? $_POST['b'] ?? 0);
$b = $app->block->find($bid);
if (!$b) {
    $app->redirect('blocks.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app->csrf->check()) {
    $app->block->save($bid, [
        'name' => clean_title($_POST['name'] ?? '', 120),
        'code' => clean_title($_POST['code'] ?? '', 10),
        'status' => ($_POST['status'] ?? '') === 'closed' ? 'closed' : 'open',
    ]);
    $b = $app->block->find($bid);
}
$app->view->start('Block', 'blocks', 'editor');
echo '<form method="post">' . $app->csrf->field();
echo '<input type="hidden" name="b" value="' . $bid . '">';
echo '<p class="sans">Name <input name="name" value="' . h($b['name']) . '"> Code <input name="code" value="' . h($b['code']) . '"></p>';
echo '<p class="sans">Status <select name="status"><option value="open"' . ($b['status'] === 'open' ? ' selected' : '') . '>open</option>';
echo '<option value="closed"' . ($b['status'] === 'closed' ? ' selected' : '') . '>closed</option></select></p>';
echo '<p><input type="submit" class="lt_button" value="Save"></p></form>';
$app->view->end();
