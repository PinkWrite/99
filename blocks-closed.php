<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'text', 'block', 'user'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('supervisor')) {
    $app->redirect('editor.php');
}
$fid = $app->auth->facilityId();
$rows = [];
foreach ($app->block->forFacility($fid, false) as $b) {
    if (($b['status'] ?? '') === 'closed') {
        $rows[] = $b;
    }
}
$editors = [];
foreach ($app->user->listByFacility($fid, 'editor') as $ed) {
    $editors[(int) $ed['id']] = $ed['name'];
}
$app->view->start('Closed blocks', 'blocks', 'admin');
echo '<h2 class="lt">Blocks</h2>';
echo '<h3 class="lt" style="display:inline-block;margin-right:0.75em">Closed blocks</h3>';
echo button('View open blocks', 'Open blocks', 'blocks-editor.php', 'editNoteButton');
echo '<div class="bulk-bar"><form id="bulk_actions" class="bulk-bar-form" method="post" action="block-act.php">';
echo $app->csrf->field();
echo '<input type="hidden" name="return" value="blocks-closed.php">';
echo '<span id="bulk_actions_div" class="bulk-opts" hidden>';
echo confirm_submit('blocksubmit', 'delete', 'Confirm delete', 'delete', 'act_red small', 'act_red small');
echo confirm_submit('blocksubmit', 'open', 'Confirm open', 'open', 'act_green small', 'act_green small');
echo '<label class="bulk-select-all"><small class="sans lt">Select all</small> <input type="checkbox" onclick="toggle(this)"></label>';
echo '</span>';
echo '<button type="button" class="act_ltgray small" id="bulk_actions_btn" onclick="showBulkActions()">Actions &#9660;</button>';
echo '</form></div>';
echo '<table class="list bulk roll lt sans"><tr><th>Name</th><th>Code</th><th>Editor</th><th class="bulk_check"></th></tr>';
$cc = 'lr';
foreach ($rows as $b) {
    $id = (int) $b['id'];
    $eid = (int) ($b['editor_id'] ?? 0);
    echo '<tr class="' . $cc . '">';
    echo '<td><a class="listed_note" href="block.php?b=' . $id . '"><b>' . h((string) $b['name']) . '</b></a></td>';
    echo '<td>' . h((string) ($b['code'] ?? '')) . '</td>';
    echo '<td>' . h((string) ($editors[$eid] ?? '')) . '</td>';
    echo '<td class="bulk_check"><input type="checkbox" form="bulk_actions" name="bulk_' . $id . '" value="' . $id . '"></td>';
    echo '</tr>';
    $cc = $cc === 'lr' ? 'dr' : 'lr';
}
if (!$rows) {
    echo '<tr class="lr"><td colspan="4" class="lt sans">No closed blocks.</td></tr>';
}
echo '</table>';
$app->view->end();
