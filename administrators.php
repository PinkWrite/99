<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'text', 'facility', 'user', 'notify'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->is('superintendent')) {
    $app->redirect('');
}
$msg = $err = '';
$facilities = $app->facility->all();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app->csrf->check()) {
    if (isset($_POST['save_admin_facilities'], $_POST['u'])) {
        $aid = (int) $_POST['u'];
        $picked = array_map('intval', (array) ($_POST['facilities'] ?? []));
        $app->user->setFacility($aid, $picked[0] ?? null);
        $msg = 'Administrator facilities saved.';
    } elseif (isset($_POST['new_admin'])) {
        $fid = (int) ($_POST['facility_id'] ?? 0);
        $un = (string) $_POST['username'];
        $em = (string) $_POST['email'];
        if (!preg_match('/^[A-Za-z0-9]{4,32}$/', $un) || !filter_var($em, FILTER_VALIDATE_EMAIL)) {
            $err = 'Username or email not valid.';
        } elseif ($app->user->findByUsername($un) || $app->user->findByEmail($em)) {
            $err = 'Username or email already used.';
        } else {
            $app->user->create([
                'type' => 'admin',
                'facility_id' => $fid ?: null,
                'username' => $un,
                'email' => $em,
                'name' => clean_title($_POST['name'] ?? $un, 80),
                'pass' => password_hash((string) $_POST['pass'], PASSWORD_DEFAULT),
            ]);
            $app->notify->send($app->auth->id(), 'new_admin', 'Admin ' . $un, 'administrators.php');
            $msg = 'Administrator created.';
        }
    }
}
$app->view->start('Administrators', 'admins', 'super');
echo '<h2 class="lt">Administrators</h2>';
if ($err) {
    echo '<p class="sans noticered">' . h($err) . '</p>';
}
if ($msg) {
    echo '<p class="sans noticegreen">' . h($msg) . '</p>';
}

echo '<table class="list roll lt sans"><tr><th>Name</th><th>Username</th><th>Facilities</th><th></th></tr>';
$cc = 'lr';
foreach ($app->user->listByType('admin') as $a) {
    echo '<tr class="' . $cc . '"><td>' . h($a['name']) . '</td><td>' . h($a['username']) . '</td><td>';
    echo '<form method="post">' . $app->csrf->field() . '<input type="hidden" name="u" value="' . (int) $a['id'] . '">';
    $have = (int) ($a['facility_id'] ?? 0);
    foreach ($facilities as $f) {
        $ck = $have === (int) $f['id'] ? ' checked' : '';
        echo '<label><input type="checkbox" name="facilities[]" value="' . (int) $f['id'] . '"' . $ck . '> ' . h($f['name']) . '</label> ';
    }
    echo '<input type="submit" name="save_admin_facilities" class="lt_button small" value="Save"></form></td><td>';
    echo button('Edit', 'Edit account', 'account.php?u=' . (int) $a['id'], 'editNoteButton') . '</td></tr>';
    $cc = $cc === 'lr' ? 'dr' : 'lr';
}
echo '</table>';

echo '<h2 class="lt">New Administrator</h2>';
echo '<form method="post">' . $app->csrf->field();
echo '<p class="field sans"><label for="facility_id">Facility</label>';
$fopts = [];
foreach ($facilities as $f) {
    $fopts[(int) $f['id']] = $f['name'];
}
echo form_select('facility_id', $fopts, 0, 'Choose…', 'formselect') . '</p>';
echo '<p class="field sans"><label for="name">Name</label><input name="name" id="name"></p>';
echo '<p class="field sans"><label for="email">Email</label><input type="email" name="email" id="email" required></p>';
echo '<p class="field sans"><label for="username">Username</label><input name="username" id="username" required></p>';
echo '<p class="field sans"><label for="pass">Password</label><input type="password" name="pass" id="pass" required></p>';
echo '<p><input type="submit" name="new_admin" class="lt_button" value="Create admin"></p></form>';
$app->view->end();
