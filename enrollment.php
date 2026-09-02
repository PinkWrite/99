<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'user', 'block'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('supervisor')) {
    $app->redirect('editor.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app->csrf->check() && isset($_POST['u'])) {
    $ids = array_map('intval', (array) ($_POST['blocks'] ?? []));
    $app->user->setBlocks((int) $_POST['u'], $ids);
}
$writers = $app->user->listByFacility($app->auth->facilityId(), 'writer');
$blocks = $app->block->forFacility($app->auth->facilityId(), false);
$app->view->start('Writers', 'writers', 'admin');
echo '<h2 class="lt">Writers</h2>';
$focus = (int) ($_GET['b'] ?? 0);
if ($focus > 0) {
    $fb = $app->block->find($focus);
    if ($fb) {
        echo '<p class="sans dk">Block: ' . h($app->block->named($fb)) . '</p>';
    }
}
echo '<p class="sans dk">Writers and the blocks they belong to.</p>';
echo '<p>' . button('New writer +', 'Register a writer', 'register.php?type=writer', 'newNoteButton') . '</p>';
echo '<table class="list roll lt sans"><tr><th>Name</th><th>Username</th><th>Blocks</th><th></th></tr>';
$cc = 'lr';
foreach ($writers as $w) {
    echo '<tr class="' . $cc . '"><td>' . h($w['name']) . '</td><td>' . h($w['username']) . '</td><td>';
    echo '<form method="post">' . $app->csrf->field() . '<input type="hidden" name="u" value="' . (int) $w['id'] . '">';
    $have = array_map('intval', json_arr($w['blocks_json']));
    foreach ($blocks as $b) {
        $ck = in_array((int) $b['id'], $have, true) ? ' checked' : '';
        echo '<label><input type="checkbox" name="blocks[]" value="' . (int) $b['id'] . '"' . $ck . '> ' . h($b['code'] ?: $b['name']) . '</label> ';
    }
    echo '<input type="submit" class="lt_button small" value="Save"></form></td><td>';
    echo button('Edit', 'Edit account', 'account.php?u=' . (int) $w['id'], 'editNoteButton') . '</td></tr>';
    $cc = $cc === 'lr' ? 'dr' : 'lr';
}
echo '</table>';
$app->view->end();
