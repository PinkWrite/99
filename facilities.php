<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'facility', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->is('superintendent')) {
    $app->redirect('');
}
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app->csrf->check() && isset($_POST['leave'])) {
    unset($_SESSION['facility_id']);
    $msg = 'Left the working facility.';
}
$app->view->start('Facilities', 'facilities', 'super');
echo '<h2 class="lt">Facilities</h2>';
echo '<p class="sans dk">A Facility is a school. Blocks are classes inside a facility.</p>';
if ($msg) {
    echo '<p class="sans noticegreen">' . h($msg) . '</p>';
}
echo '<p>' . button('New facility +', 'Create a facility', 'facility.php', 'newNoteButton') . '</p>';
echo '<h3 class="lt" style="display:inline-block;margin-right:0.75em">Open facilities</h3>';
echo button('View closed facilities', 'Closed facilities', 'facilities-closed.php', 'editNoteButton');
$app->writlist->renderSuperFacilities('facilities.php', 'open');
if (!empty($_SESSION['facility_id'])) {
    $cur = $app->facility->find((int) $_SESSION['facility_id']);
    echo '<p class="sans">Working in ' . h($cur['name'] ?? ('#' . (int) $_SESSION['facility_id'])) . ' ';
    echo post_button('Leave', 'Clear working facility', 'facilities.php', 'leave', '1', 'set_gray', $app->csrf->token()) . '</p>';
}
$app->view->end();
