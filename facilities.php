<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'text', 'facility', 'notify'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->is('superintendent')) {
    $app->redirect('');
}
$msg = $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app->csrf->check()) {
    if (isset($_POST['new_facility'])) {
        $app->facility->create(clean_title($_POST['fname'] ?? '', 120), clean_title($_POST['fcode'] ?? '', 16));
        $app->notify->send($app->auth->id(), 'new_facility', 'Facility created', 'facilities.php');
        $msg = 'Facility created.';
    } elseif (isset($_POST['leave'])) {
        unset($_SESSION['facility_id']);
        $msg = 'Left the working facility.';
    }
}
$app->view->start('Facilities', 'facilities', 'super');
echo '<h2 class="lt">Facilities</h2>';
echo '<p class="sans dk">A Facility is a school. Blocks are classes inside a facility.</p>';
if ($err) {
    echo '<p class="sans noticered">' . h($err) . '</p>';
}
if ($msg) {
    echo '<p class="sans noticegreen">' . h($msg) . '</p>';
}
echo '<form method="post">' . $app->csrf->field();
echo '<p class="field sans"><label for="fname">New facility name</label><input name="fname" id="fname" required></p>';
echo '<p class="field sans"><label for="fcode">Code</label><input name="fcode" id="fcode" size="8"></p>';
echo '<p><input type="submit" name="new_facility" class="lt_button" value="Create"></p></form>';

echo '<table class="list lt sans"><tr><th>Name</th><th>Code</th><th>Status</th><th></th></tr>';
$cc = 'lr';
foreach ($app->facility->all() as $f) {
    echo '<tr class="' . $cc . '"><td>' . h($f['name']) . '</td><td>' . h($f['code']) . '</td><td>' . h($f['status']) . '</td><td>';
    echo button('Edit', 'Edit this facility', 'facility.php?f=' . (int) $f['id'], 'editNoteButton');
    echo '</td></tr>';
    $cc = $cc === 'lr' ? 'dr' : 'lr';
}
echo '</table>';
if (!empty($_SESSION['facility_id'])) {
    $cur = $app->facility->find((int) $_SESSION['facility_id']);
    echo '<p class="sans">Working in ' . h($cur['name'] ?? ('#' . (int) $_SESSION['facility_id'])) . ' ';
    echo post_button('Leave', 'Clear working facility', 'facilities.php', 'leave', '1', 'set_gray', $app->csrf->token()) . '</p>';
}
$app->view->end();
