<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'note'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->is('superintendent')) {
    $app->redirect('');
}
$u = $app->auth->user();
$app->view->start('Super Dash', 'super', 'super');
echo '<h2 class="lt">Super Dash</h2>';
echo '<p class="sans dk">Pinned notes. Facilities and administrators are on the sub-dash.</p>';
$pins = $app->note->pinnedFor($app->auth->id(), 25);
if (!$pins) {
    echo '<p class="sans dk">No pinned notes.</p>';
} else {
    $cc = 'lr';
    echo '<table class="list lt notes sans">';
    foreach ($pins as $n) {
        $nid = (int) $n['id'];
        echo '<tr class="' . $cc . '"><td><a class="listed_note" href="note.php?n=' . $nid . '">' . h(note_heading($n['body'] ?? '')) . '</a></td>';
        echo '<td><i class="listed_note">' . h((string) $n['save_date']) . '</i></td>';
        echo '<td>' . get_switch('Read', 'Read', 'note.php', 'n', (string) $nid, 'act_blue editNoteButton') . '</td></tr>';
        $cc = $cc === 'lr' ? 'dr' : 'lr';
    }
    echo '</table>';
}
$app->view->end();
