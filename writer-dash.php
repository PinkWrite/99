<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'writ', 'note', 'user', 'block', 'writlist'];
require __DIR__ . '/lib/boot.php';

if (!$app->auth->user()) {
    $app->redirect('login.php');
}
if ($app->auth->is('observer')) {
    $app->redirect('observer.php');
}
$u = $app->auth->user();
$uid = $app->auth->id();
$app->view->start('Writer Dash for ' . $u['name'], 'writs', 'writer');
echo '<p class="sans dk">Typing and Editing for Learners and Teachers.</p>';

try {
echo post_button('New note +', 'Start a new note', 'note.php', 'new_note', (string) $uid, 'newNoteButton', $app->csrf->token());
echo '<br>';
$pins = $app->note->pinnedFor($uid, 10);
if ($pins) {
    $cc = 'lr';
    echo '<table class="list lt notes sans">';
    foreach ($pins as $n) {
        $nid = (int) $n['id'];
        echo '<tr class="' . $cc . '">';
        echo '<td><a class="listed_note" href="note.php?n=' . $nid . '">' . h(note_heading($n['body'] ?? '')) . '</a></td>';
        echo '<td><i class="listed_note">' . h((string) $n['save_date']) . '</i><div style="display:inline;float:right">';
        echo get_switch('Read', 'Read this note', 'note.php', 'n', (string) $nid, 'act_blue editNoteButton');
        echo '</div></td><td><div style="display:inline;float:right">';
        echo post_button('Unpin', 'Unpin from Dashboard', 'note-act.php', 'undash', (string) $nid, 'editNoteButton', $app->csrf->token());
        echo '</div></td></tr>';
        $cc = $cc === 'lr' ? 'dr' : 'lr';
    }
    echo '</table>';
}

$memos = $app->note->memosForDash($uid, $app->user->blocksOf($u), 5);
if ($memos) {
    echo '<h4>Memos</h4><table class="list lt notes sans"><tbody>';
    $cc = 'lr';
    foreach ($memos as $n) {
        $nid = (int) $n['id'];
        $who = 'Block: Main';
        if ((int) $n['editor_set_writer_id'] > 0) {
            $w = $app->user->find((int) $n['editor_set_writer_id']) ?: [];
            $who = 'Writer: ' . h((string) ($w['name'] ?? '')) . ' <small>' . h((string) ($w['email'] ?? '')) . '</small>';
        } elseif ((int) $n['editor_set_block'] > 0) {
            $b = $app->block->find((int) $n['editor_set_block']) ?: [];
            $who = 'Block: ' . h((string) ($b['name'] ?? '')) . ' <small>' . h((string) ($b['code'] ?? '')) . '</small>';
        }
        echo '<tr class="' . $cc . '">';
        echo '<td><a class="listed_note" href="note.php?n=' . $nid . '">' . h(note_heading($n['body'] ?? '')) . '</a></td>';
        echo '<td>' . $who . '</td>';
        echo '<td><i class="listed_note">' . h((string) $n['save_date']) . '</i></td><td><div style="display:inline;float:right">';
        echo get_switch('Read', 'Read this note', 'note.php', 'n', (string) $nid, 'act_blue editNoteButton');
        echo '</div></td></tr>';
        $cc = $cc === 'lr' ? 'dr' : 'lr';
    }
    echo '</tbody></table>';
}
echo '<br>';
echo button('All memos', 'View all notes from your editor and blocks', 'memos.php', 'editNoteButton');
echo '<br><br>';

echo post_button('New writ +', 'Start writing something new', 'writ.php', 'new_writ', (string) $uid, 'set_gray', $app->csrf->token());
$app->writlist->renderWriter('writer-dash.php');
} catch (Throwable $e) {
    echo '<p class="sans noticered">Dashboard list failed: ' . h($e->getMessage()) . '</p>';
}
$app->view->end();
