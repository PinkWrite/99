<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'text', 'user', 'mail', 'notify'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('supervisor')) {
    $app->redirect('editor.php');
}
$err = $msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app->csrf->check()) {
    $type = $_POST['type'] === 'observer' ? 'observer' : 'writer';
    $un = (string) ($_POST['username'] ?? '');
    $em = (string) ($_POST['email'] ?? '');
    $name = clean_title($_POST['name'] ?? '', 80);
    $p1 = (string) ($_POST['pass1'] ?? '');
    $p2 = (string) ($_POST['pass2'] ?? '');
    if (!preg_match('/^[A-Za-z0-9]{4,32}$/', $un)) {
        $err = 'Username: 4–32 letters or digits.';
    } elseif (!filter_var($em, FILTER_VALIDATE_EMAIL)) {
        $err = 'Valid email required.';
    } elseif (strlen($p1) < 8 || $p1 !== $p2) {
        $err = 'Passwords must match, 8+ characters.';
    } elseif ($app->user->findByUsername($un) || $app->user->findByEmail($em)) {
        $err = 'Username or email already registered.';
    } else {
        $id = $app->user->create([
            'type' => $type,
            'facility_id' => $app->auth->facilityId(),
            'username' => $un,
            'email' => $em,
            'name' => $name,
            'pass' => password_hash($p1, PASSWORD_DEFAULT),
            'editor_id' => $app->auth->is('editor') ? $app->auth->id() : null,
        ]);
        $app->notify->send($app->auth->id(), $type === 'observer' ? 'new_observer' : 'new_writer', $name . ' registered', $type === 'observer' ? 'observers.php' : 'enrollment.php');
        if ($app->mail->enabled()) {
            $app->mail->send($em, 'Welcome to ' . $app->title(), "Your username is {$un}. Sign in at " . $app->url('login.php') . "\n");
        }
        $msg = ucfirst($type) . ' created.';
    }
}
$kind = (($_POST['type'] ?? $_GET['type'] ?? 'writer') === 'observer') ? 'observer' : 'writer';
$app->view->start('Register', $kind === 'observer' ? 'observers' : 'writers', 'admin');
echo '<h2 class="lt">Register</h2>';
if ($err) {
    echo '<p class="sans noticered">' . h($err) . '</p>';
}
if ($msg) {
    echo '<p class="sans noticegreen">' . h($msg) . '</p>';
}
echo '<form method="post">' . $app->csrf->field();
echo '<p class="sans">Type<br><select class="formselect" name="type">';
echo '<option value="writer"' . ($kind === 'writer' ? ' selected' : '') . '>Writer</option>';
echo '<option value="observer"' . ($kind === 'observer' ? ' selected' : '') . '>Observer</option></select></p>';
echo '<p class="sans">Name<br><input name="name" required></p>';
echo '<p class="sans">Username<br><input name="username" required></p>';
echo '<p class="sans">Email<br><input type="email" name="email" required></p>';
echo '<p class="sans">Password<br><input type="password" name="pass1" required></p>';
echo '<p class="sans">Confirm<br><input type="password" name="pass2" required></p>';
echo '<p><input type="submit" class="lt_button" value="Register"></p></form>';
$app->view->end();
