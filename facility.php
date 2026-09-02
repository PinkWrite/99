<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'text', 'facility'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->is('superintendent')) {
    $app->redirect('');
}
$fid = (int) ($_GET['f'] ?? $_POST['f'] ?? 0);
$f = $fid ? $app->facility->find($fid) : null;
if (!$f) {
    $app->redirect('facilities.php');
}
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app->csrf->check()) {
    if (isset($_POST['save_facility'])) {
        $app->facility->rename($fid, clean_title($_POST['name'] ?? '', 120), clean_title($_POST['code'] ?? '', 16));
        $app->facility->setStatus($fid, ($_POST['status'] ?? '') === 'closed' ? 'closed' : 'open');
        $f = $app->facility->find($fid);
        $msg = 'Saved.';
    } elseif (isset($_POST['enter'])) {
        $_SESSION['facility_id'] = $fid;
        $msg = 'Working inside this facility.';
    }
}
$app->view->start('Facility', 'facilities', 'super');
echo '<h2 class="lt">Edit facility</h2>';
if ($msg) {
    echo '<p class="sans noticegreen">' . h($msg) . '</p>';
}
echo '<form method="post">' . $app->csrf->field();
echo '<input type="hidden" name="f" value="' . $fid . '">';
echo '<p class="field sans"><label for="name">Name</label><input name="name" id="name" value="' . h($f['name']) . '" required></p>';
echo '<p class="field sans"><label for="code">Code</label><input name="code" id="code" value="' . h((string) $f['code']) . '"></p>';
echo '<p class="field sans"><label for="status">Status</label>';
echo form_select('status', ['open' => 'open', 'closed' => 'closed'], $f['status'], '', 'formselect small') . '</p>';
echo '<p><input type="submit" name="save_facility" class="lt_button" value="Save"></p>';
echo '<p><button type="submit" name="enter" class="set_gray" value="1">Work in this facility</button></p>';
echo '</form>';
$app->view->end();
