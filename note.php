<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'text', 'note', 'block', 'user'];
require __DIR__ . '/lib/boot.php';
$u = $app->auth->requireUser();
$uid = $app->auth->id();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_note']) && $app->csrf->check()) {
    $isEditor = $app->auth->atLeast('editor');
    $id = $app->note->create([
        'type' => $isEditor ? 'memo' : 'note',
        'writer_id' => $isEditor ? null : $uid,
        'editor_id' => $isEditor ? $uid : null,
        'body' => '',
    ]);
    $app->redirect('note.php?n=' . $id);
}

$nid = (int) ($_GET['n'] ?? $_GET['v'] ?? $_POST['note_id'] ?? 0);
$n = $nid ? $app->note->find($nid) : null;
if ($n && (int) $n['writer_id'] === $uid) {
    $app->note->markRead($nid);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['copy_memo']) && $n && $app->auth->atLeast('editor') && $app->csrf->check()) {
    $mid = $app->note->copyNoteToMemo($nid, $uid);
    $app->redirect('note.php?n=' . $mid);
}

$app->view->start($app->auth->atLeast('editor') ? 'Memo' : 'Note', $app->auth->atLeast('editor') ? 'binder' : 'notes', $app->auth->atLeast('editor') ? 'editor' : 'writer');
echo '<p>' . post_button($app->auth->atLeast('editor') ? 'New memo +' : 'New note +', 'Create', 'note.php', 'new_note', '1', 'newNoteButton', $app->csrf->token()) . '</p>';
if (!$n) {
    echo '<p class="sans">Open one from the binder, or create new.</p>';
    $app->view->end();
    exit;
}
$canEdit = ((int) $n['writer_id'] === $uid) || ((int) $n['editor_id'] === $uid) || $app->auth->atLeast('admin');
if (!$canEdit && $n['type'] !== 'memo') {
    $app->redirect('');
}

if ($n['type'] === 'note' && $app->auth->atLeast('editor')) {
    echo '<p>' . post_button('Copy into a memo', 'Make a memo from this note', 'note.php?n=' . $nid, 'copy_memo', '1', 'set_gray', $app->csrf->token()) . '</p>';
}
if ($n['type'] === 'memo' && $app->auth->atLeast('editor')) {
    echo '<p>' . button('Make assignment', 'Writ with this memo as instructions', 'assignment.php?m=' . $nid, 'set_gray') . '</p>';
}

echo '<form id="editform">' . $app->csrf->field();
echo '<input type="hidden" name="note_id" value="' . $nid . '">';
echo '<input type="hidden" name="user_id" value="' . $uid . '">';
if ($app->auth->atLeast('editor')) {
    echo '<p class="sans">Type <select class="formselect small" name="type"><option value="memo"' . ($n['type'] === 'memo' ? ' selected' : '') . '>Memo</option>';
    echo '<option value="task"' . ($n['type'] === 'task' ? ' selected' : '') . '>Task</option></select></p>';
    echo '<p class="sans">For writer id <input name="editor_set_writer_id" value="' . h((string) $n['editor_set_writer_id']) . '"> block id <input name="editor_set_block" value="' . h((string) $n['editor_set_block']) . '"></p>';
}
echo '<p class="save-row"><button type="button" class="lt_button small" title="Save (Ctrl + S)" onclick="pwAjaxForm(\'editform\',\'ajax/save-note.php\',\'ajax_changes\');offNavWarn();">Save</button> ';
echo '<span id="ajax_changes"></span></p>';
echo '<p><textarea name="body" id="writingArea" class="writingBox" rows="16" cols="82" onchange="onNavWarn()">' . h($n['body']) . '</textarea></p>';
echo '</form>';
echo '<script src="js/pw99.js"></script><script>pwBindSave("editform","ajax/save-note.php","ajax_changes");</script>';
$app->view->end();
