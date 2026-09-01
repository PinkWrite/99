<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'text', 'note', 'writ', 'user', 'block', 'notify'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('editor')) {
    $app->redirect('');
}
$mid = (int) ($_GET['m'] ?? $_POST['m'] ?? 0);
$memo = $mid ? $app->note->find($mid) : null;
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app->csrf->check() && $memo) {
    $title = clean_title($_POST['title'] ?? 'Assignment');
    $work = clean_title($_POST['work'] ?? 'Assignment');
    $blockId = (int) ($_POST['block'] ?? 0);
    $ids = [];
    if (!empty($_POST['writer_id'])) {
        $ids[] = (int) $_POST['writer_id'];
    } elseif ($blockId) {
        foreach ($app->user->writersForEditor($app->auth->id()) as $wr) {
            $blocks = json_arr($wr['blocks_json']);
            if (in_array($blockId, array_map('intval', $blocks), true) || in_array((string) $blockId, $blocks, true)) {
                $ids[] = (int) $wr['id'];
            }
        }
    }
    foreach ($ids as $wid) {
        $writId = $app->writ->create([
            'writer_id' => $wid,
            'facility_id' => $app->auth->facilityId(),
            'block_id' => $blockId,
            'kind' => 'assignment',
            'memo_id' => $mid,
            'instructions' => $memo['body'],
            'title' => $title,
            'work' => $work,
        ]);
        $app->notify->send($wid, 'new_assignment', $title, 'writ.php?w=' . $writId, 'New assignment from your editor.');
    }
    $msg = count($ids) . ' assignment(s) created.';
}

$app->view->start('Assignment', 'assign', 'editor');
echo '<h2 class="lt">Assignment</h2>';
echo '<p class="sans dk">An assignment is a writ with a memo attached as instructions.</p>';
if ($msg) {
    echo '<p class="sans noticegreen">' . h($msg) . '</p>';
}
echo '<form method="post">' . $app->csrf->field();
echo '<p class="sans">Memo id <input name="m" value="' . (int) $mid . '"></p>';
if ($memo) {
    echo '<section class="writcontent remarks">' . nl_text($memo['body']) . '</section>';
}
echo '<p class="sans">Work <input name="work" value="Assignment"> Title <input name="title" value="Assignment" required></p>';
echo '<p class="sans">Block id (all writers in that block) <input name="block" value="0"> or one writer id <input name="writer_id"></p>';
echo '<p><input type="submit" class="lt_button" value="Create assignment"></p></form>';
$app->view->end();
