<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'text', 'user', 'mail', 'notify', 'facility'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('supervisor')) {
    $app->redirect('editor.php');
}

$allowed = ['writer', 'observer', 'editor'];
if ($app->auth->is('superintendent')) {
    $allowed[] = 'admin';
}
$want = (string) ($_POST['type'] ?? $_GET['type'] ?? 'writer');
$kind = in_array($want, $allowed, true) ? $want : 'writer';
$fidIn = (int) ($_POST['facility_id'] ?? $_GET['facility_id'] ?? 0);

$err = $msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app->csrf->check()) {
    $type = in_array((string) ($_POST['type'] ?? ''), $allowed, true) ? (string) $_POST['type'] : 'writer';
    $un = (string) ($_POST['username'] ?? '');
    $em = (string) ($_POST['email'] ?? '');
    $name = clean_title($_POST['name'] ?? '', 80);
    $p1 = (string) ($_POST['pass1'] ?? '');
    $p2 = (string) ($_POST['pass2'] ?? '');
    $fid = $type === 'admin' ? ((int) ($_POST['facility_id'] ?? 0) ?: null) : $app->auth->facilityId();
    if ($type === 'admin' && $fid) {
        $ff = $app->facility->find((int) $fid);
        if (!$ff) {
            $fid = null;
        }
    }
    if (!preg_match('/^[A-Za-z0-9]{4,32}$/', $un)) {
        $err = 'Username: 4–32 letters or digits.';
    } elseif (!filter_var($em, FILTER_VALIDATE_EMAIL)) {
        $err = 'Valid email required.';
    } elseif (strlen($p1) < 8 || $p1 !== $p2) {
        $err = 'Passwords must match, 8+ characters.';
    } elseif ($app->user->findByUsername($un) || $app->user->findByEmail($em)) {
        $err = 'Username or email already registered.';
    } else {
        $app->user->create([
            'type' => $type,
            'facility_id' => $fid,
            'username' => $un,
            'email' => $em,
            'name' => $name,
            'pass' => password_hash($p1, PASSWORD_DEFAULT),
            'editor_id' => ($type === 'writer' && $app->auth->is('editor')) ? $app->auth->id() : null,
        ]);
        $note = match ($type) {
            'observer' => 'new_observer',
            'admin' => 'new_admin',
            'writer' => 'new_writer',
            default => '',
        };
        $list = match ($type) {
            'observer' => 'observers.php',
            'editor' => 'editors.php',
            'admin' => 'administrators.php',
            default => 'enrollment.php',
        };
        if ($note !== '') {
            $app->notify->send($app->auth->id(), $note, $name . ' registered', $list);
        }
        if ($app->mail->enabled()) {
            $app->mail->send($em, 'Welcome to ' . $app->title(), "Your username is {$un}. Sign in at " . $app->url('login.php') . "\n");
        }
        $msg = ucfirst($type) . ' created.';
        $kind = $type;
        $fidIn = (int) ($fid ?? 0);
    }
}

[$dash, $active] = match ($kind) {
    'observer' => ['admin', 'observers'],
    'editor' => ['admin', 'editors'],
    'admin' => ['super', 'admins'],
    default => ['admin', 'writers'],
};
$app->view->start('Register', $active, $dash);
echo '<h2 class="lt">Register</h2>';
if ($err) {
    echo '<p class="sans noticered">' . h($err) . '</p>';
}
if ($msg) {
    echo '<p class="sans noticegreen">' . h($msg) . '</p>';
}
echo '<form method="post">' . $app->csrf->field();
echo '<p class="sans">Type<br><select class="formselect" name="type">';
foreach ($allowed as $t) {
    $sel = $kind === $t ? ' selected' : '';
    echo '<option value="' . h($t) . '"' . $sel . '>' . h(ucfirst($t)) . '</option>';
}
echo '</select></p>';
if ($kind === 'admin' && $app->auth->is('superintendent')) {
    $fopts = [];
    foreach ($app->facility->all() as $f) {
        $fopts[(int) $f['id']] = $f['name'];
    }
    echo '<p class="sans">Facility<br>';
    echo form_select('facility_id', $fopts, $fidIn, 'None', 'formselect') . '</p>';
}
echo '<p class="sans">Name<br><input name="name" required></p>';
echo '<p class="sans">Username<br><input name="username" required></p>';
echo '<p class="sans">Email<br><input type="email" name="email" required></p>';
echo '<p class="sans">Password<br><input type="password" name="pass1" required></p>';
echo '<p class="sans">Confirm<br><input type="password" name="pass2" required></p>';
echo '<p><input type="submit" class="lt_button" value="Register"></p></form>';
$app->view->end();
