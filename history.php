<?php
declare(strict_types=1);
$import = ['auth', 'view', 'html', 'writ', 'user'];
require __DIR__ . '/lib/boot.php';
$u = $app->auth->requireUser();
$wid = (int) ($_GET['w'] ?? 0);
$w = $app->writ->find($wid);
if (!$w) {
    $app->redirect('');
}
$mine = (int) $w['writer_id'] === $app->auth->id();
if (!$mine && !$app->auth->atLeast('editor') && !$app->auth->is('observer')) {
    $app->redirect('');
}
$app->view->start('History', $app->auth->atLeast('editor') ? 'ewrits' : 'writs', $app->auth->atLeast('editor') ? 'editor' : 'writer');
echo '<h2 class="lt">History — ' . h($w['title']) . '</h2>';
echo '<p>' . button('Back to writ', 'Open', $app->auth->atLeast('editor') ? 'review.php?w=' . $wid : 'writ.php?w=' . $wid, 'set_gray') . '</p>';
$drafts = json_arr($w['drafts']);
$redrafts = json_arr($w['redrafts']);
echo '<h3 class="lt">Writer drafts</h3>';
if (!$drafts) {
    echo '<p class="sans dk">None stored yet.</p>';
}
foreach (array_reverse($drafts) as $i => $d) {
    echo '<h4 class="review">' . h($d['at'] ?? '') . ' · ' . (int) ($d['wordcount'] ?? 0) . ' words</h4>';
    echo '<section class="writcontent draft">' . nl_text($d['body'] ?? '') . '</section>';
}
echo '<h3 class="lt">Editor redrafts</h3>';
if (!$redrafts) {
    echo '<p class="sans dk">None stored yet.</p>';
}
foreach (array_reverse($redrafts) as $d) {
    echo '<h4 class="review">' . h($d['at'] ?? '') . '</h4>';
    if (!empty($d['notes'])) {
        echo '<section class="writcontent remarks">' . nl_text($d['notes']) . '</section>';
    }
    echo '<section class="writcontent revision">' . nl_text($d['body'] ?? '') . '</section>';
}
$app->view->end();
