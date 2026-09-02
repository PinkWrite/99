<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'text', 'user', 'notify'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('supervisor')) {
    $app->redirect('');
}
$err = $msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app->csrf->check() && isset($_POST['create'])) {
    $type = (string) $_POST['type'];
    if (!in_array($type, ['editor', 'supervisor', 'observer', 'writer', 'admin'], true)) {
        $err = 'Bad type.';
    } elseif ($type === 'admin' && !$app->auth->is('superintendent')) {
        $err = 'Only a Superintendent creates Administrators.';
    } else {
        $un = (string) $_POST['username'];
        $em = (string) $_POST['email'];
        if ($app->user->findByUsername($un) || $app->user->findByEmail($em)) {
            $err = 'Username or email used.';
        } else {
            $app->user->create([
                'type' => $type,
                'facility_id' => $app->auth->facilityId(),
                'username' => $un,
                'email' => $em,
                'name' => clean_title($_POST['name'] ?? $un, 80),
                'pass' => password_hash((string) $_POST['pass'], PASSWORD_DEFAULT),
            ]);
            $msg = 'Created.';
        }
    }
}
if (isset($_POST['status'], $_POST['u']) && $app->csrf->check()) {
    $app->user->setStatus((int) $_POST['u'], $_POST['status'] === 'dormant' ? 'dormant' : 'active');
}
$app->view->start('Staffing', 'admin');
echo '<h2 class="lt">Staffing</h2>';
if ($err) {
    echo '<p class="sans noticered">' . h($err) . '</p>';
}
if ($msg) {
    echo '<p class="sans noticegreen">' . h($msg) . '</p>';
}
echo '<form method="post">' . $app->csrf->field();
echo '<p class="sans">Type<br><select class="formselect" name="type"><option>editor</option><option>supervisor</option><option>observer</option><option>writer</option></select></p>';
echo '<p class="sans">Name<br><input name="name"></p>';
echo '<p class="sans">Email<br><input type="email" name="email" required></p>';
echo '<p class="sans">Username<br><input name="username" required></p>';
echo '<p class="sans">Password<br><input type="password" name="pass" required></p>';
echo '<p><input type="submit" name="create" class="lt_button" value="Create"></p></form>';
echo '<table class="list"><tr><th>Name</th><th>Type</th><th>Status</th><th></th></tr>';
foreach ($app->user->listByFacility($app->auth->facilityId()) as $row) {
    echo '<tr><td>' . h($row['name']) . '</td><td>' . h($row['type']) . '</td><td>' . h($row['status']) . '</td><td>';
    echo '<form method="post" style="display:inline">' . $app->csrf->field();
    echo '<input type="hidden" name="u" value="' . (int) $row['id'] . '">';
    echo '<input type="hidden" name="status" value="' . ($row['status'] === 'active' ? 'dormant' : 'active') . '">';
    echo '<input type="submit" class="set_gray" value="' . ($row['status'] === 'active' ? 'Dormant' : 'Activate') . '"></form></td></tr>';
}
echo '</table>';
$app->view->end();
