<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'text', 'facility', 'user', 'notify'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->is('superintendent')) {
    $app->redirect('');
}
$msg = $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app->csrf->check()) {
    if (isset($_POST['new_facility'])) {
        $id = $app->facility->create(clean_title($_POST['fname'] ?? '', 120), clean_title($_POST['fcode'] ?? '', 16));
        $app->notify->send($app->auth->id(), 'new_facility', 'Facility created', 'super.php');
        $msg = 'Facility created.';
    } elseif (isset($_POST['enter'])) {
        $_SESSION['facility_id'] = (int) $_POST['enter'];
        $msg = 'Working inside that facility.';
    } elseif (isset($_POST['leave'])) {
        unset($_SESSION['facility_id']);
    } elseif (isset($_POST['new_admin'])) {
        $fid = (int) $_POST['facility_id'];
        $un = (string) $_POST['username'];
        $em = (string) $_POST['email'];
        if (!preg_match('/^[A-Za-z0-9]{4,32}$/', $un) || !filter_var($em, FILTER_VALIDATE_EMAIL)) {
            $err = 'Username or email not valid.';
        } elseif ($app->user->findByUsername($un) || $app->user->findByEmail($em)) {
            $err = 'Username or email already used.';
        } else {
            $id = $app->user->create([
                'type' => 'admin',
                'facility_id' => $fid,
                'username' => $un,
                'email' => $em,
                'name' => clean_title($_POST['name'] ?? $un, 80),
                'pass' => password_hash((string) $_POST['pass'], PASSWORD_DEFAULT),
            ]);
            $app->notify->send($app->auth->id(), 'new_admin', 'Admin ' . $un, 'super.php');
            $msg = 'Administrator created.';
        }
    }
}
$app->view->start('Superintendent', 'super');
echo '<h2 class="lt">Facilities</h2>';
echo '<p class="sans dk">A Facility is a school. Blocks are classes inside a facility.</p>';
if ($err) {
    echo '<p class="sans noticered">' . h($err) . '</p>';
}
if ($msg) {
    echo '<p class="sans noticegreen">' . h($msg) . '</p>';
}
echo '<form method="post">' . $app->csrf->field();
echo '<p class="sans">New facility name <input name="fname" required> code <input name="fcode" size="8"> ';
echo '<input type="submit" name="new_facility" class="lt_button" value="Create"></p></form>';

echo '<table class="list"><tr><th>Name</th><th>Code</th><th>Status</th><th></th></tr>';
foreach ($app->facility->all() as $f) {
    echo '<tr><td>' . h($f['name']) . '</td><td>' . h($f['code']) . '</td><td>' . h($f['status']) . '</td><td>';
    echo post_button('Enter', 'Work as this facility', 'super.php', 'enter', (string) $f['id'], 'editNoteButton', $app->csrf->token());
    echo '</td></tr>';
}
echo '</table>';
if (!empty($_SESSION['facility_id'])) {
    echo '<p class="sans">In facility #' . (int) $_SESSION['facility_id'] . ' ';
    echo post_button('Leave', 'Clear', 'super.php', 'leave', '1', 'set_gray', $app->csrf->token()) . '</p>';
}

echo '<h2 class="lt">New Administrator</h2>';
echo '<form method="post">' . $app->csrf->field();
echo '<p class="sans">Facility <select name="facility_id">';
foreach ($app->facility->all() as $f) {
    echo '<option value="' . (int) $f['id'] . '">' . h($f['name']) . '</option>';
}
echo '</select></p>';
echo '<p class="sans">Name <input name="name"> Username <input name="username" required></p>';
echo '<p class="sans">Email <input type="email" name="email" required> Password <input type="password" name="pass" required></p>';
echo '<p><input type="submit" name="new_admin" class="lt_button" value="Create admin"></p></form>';
$app->view->end();
