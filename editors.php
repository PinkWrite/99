<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'text', 'user', 'audit', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('supervisor')) {
    $app->redirect('');
}
$err = $msg = '';
$fid = $app->auth->facilityId();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app->csrf->check() && isset($_POST['create'])) {
    $un = (string) ($_POST['username'] ?? '');
    $em = (string) ($_POST['email'] ?? '');
    $name = clean_title($_POST['name'] ?? $un, 80);
    $pass = (string) ($_POST['pass'] ?? '');
    if (!preg_match('/^[A-Za-z0-9]{4,32}$/', $un)) {
        $err = 'Username: 4–32 letters or digits.';
    } elseif (!filter_var($em, FILTER_VALIDATE_EMAIL)) {
        $err = 'Valid email required.';
    } elseif (strlen($pass) < 8) {
        $err = 'Password must be at least 8 characters.';
    } elseif ($app->user->findByUsername($un) || $app->user->findByEmail($em)) {
        $err = 'Username or email already used.';
    } else {
        $app->user->create([
            'type' => 'editor',
            'facility_id' => $fid,
            'username' => $un,
            'email' => $em,
            'name' => $name,
            'pass' => password_hash($pass, PASSWORD_DEFAULT),
        ]);
        $msg = 'Editor created.';
    }
}
$app->view->start('Editors', 'editors', 'admin');
echo '<h2 class="lt">Editors</h2>';
if ($err) {
    echo '<p class="sans noticered">' . h($err) . '</p>';
}
if ($msg) {
    echo '<p class="sans noticegreen">' . h($msg) . '</p>';
}
echo '<form method="post">' . $app->csrf->field();
echo '<p class="sans">Name<br><input name="name" required></p>';
echo '<p class="sans">Email<br><input type="email" name="email" required></p>';
echo '<p class="sans">Username<br><input name="username" required></p>';
echo '<p class="sans">Password<br><input type="password" name="pass" required></p>';
echo '<p><input type="submit" name="create" class="lt_button" value="Create editor"></p></form>';
echo '<h3 class="lt" style="display:inline-block;margin-right:0.75em">Active Editors</h3>';
echo button('View dormant editors', 'Dormant editors', 'editors-dormant.php', 'editNoteButton');
$app->writlist->renderAdminEditors('editors.php', 'active');
$app->view->end();
