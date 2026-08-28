<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'user', 'block'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('editor')) {
    $app->redirect('');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app->csrf->check() && isset($_POST['u'], $_POST['blocks'])) {
    $ids = array_map('intval', (array) $_POST['blocks']);
    $app->user->setBlocks((int) $_POST['u'], $ids);
}
$eid = $app->auth->is('editor') ? $app->auth->id() : 0;
$writers = $eid ? $app->user->writersForEditor($eid) : $app->user->listByFacility($app->auth->facilityId(), 'writer');
$blocks = $eid ? $app->block->forEditor($eid, false) : $app->block->forFacility($app->auth->facilityId(), false);
$app->view->start('Enrollment', 'roll');
echo '<h2 class="lt">Writers</h2>';
echo '<p>' . button('Register', 'New', 'register.php', 'set_gray') . '</p>';
echo '<table class="list"><tr><th>Name</th><th>Username</th><th>Blocks</th><th></th></tr>';
foreach ($writers as $w) {
    echo '<tr><td>' . h($w['name']) . '</td><td>' . h($w['username']) . '</td><td>';
    echo '<form method="post">' . $app->csrf->field() . '<input type="hidden" name="u" value="' . (int) $w['id'] . '">';
    $have = array_map('intval', json_arr($w['blocks_json']));
    foreach ($blocks as $b) {
        $ck = in_array((int) $b['id'], $have, true) ? ' checked' : '';
        echo '<label><input type="checkbox" name="blocks[]" value="' . (int) $b['id'] . '"' . $ck . '> ' . h($b['code'] ?: $b['name']) . '</label> ';
    }
    echo '<input type="submit" class="lt_button small" value="Save"></form></td><td>';
    echo button('Open', 'Writer', 'writer.php?u=' . (int) $w['id'], 'editNoteButton') . '</td></tr>';
}
echo '</table>';
$app->view->end();
