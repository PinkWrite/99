<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'user', 'block'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('editor')) {
    $app->redirect('');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app->csrf->check() && isset($_POST['u'])) {
    $ids = array_map('intval', (array) ($_POST['blocks'] ?? []));
    $app->user->setBlocks((int) $_POST['u'], $ids);
}
$eid = $app->auth->is('editor') ? $app->auth->id() : 0;
$writers = $eid ? $app->user->writersForEditor($eid) : $app->user->listByFacility($app->auth->facilityId(), 'writer');
$blocks = $eid ? $app->block->forEditor($eid, false) : $app->block->forFacility($app->auth->facilityId(), false);
$app->view->start('Roll', 'roll', 'editor');
echo '<h2 class="lt">Roll</h2>';
echo '<p class="sans dk">Writers and the blocks they belong to.</p>';
echo '<p>' . button('Register', 'New writer', 'register.php', 'newNoteButton') . '</p>';
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
    echo button('Open', 'Writer', 'writer.php?u=' . (int) $w['id'], 'editNoteButton') . '</td></tr>';
    $cc = $cc === 'lr' ? 'dr' : 'lr';
}
echo '</table>';
$app->view->end();
