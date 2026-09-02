<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'notify', 'user', 'audit'];
require __DIR__ . '/lib/boot.php';
$u = $app->auth->requireUser();
$keys = Notify::keysFor($u['type']);
$labels = Notify::catalog();
$prefs = $app->user->prefs($u);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app->csrf->check()) {
    $in = [];
    $em = [];
    foreach ($keys as $k) {
        $in[$k] = !empty($_POST['inapp'][$k]);
        $em[$k] = !empty($_POST['email'][$k]);
    }
    $app->user->savePrefs($app->auth->id(), ['inapp' => $in, 'email' => $em]);
    $prefs = ['inapp' => $in, 'email' => $em];
    $saved = true;
    $app->audit->record($app->auth->id(), 'notify', 'self');
}

$app->view->start('Notification settings', 'locker', 'my');
echo '<h2 class="lt">Notifications</h2>';
echo '<p class="sans dk">In-app notices sit in the Notifications menu until acknowledged. Email requires SysAdmin mail in config.</p>';
if (!empty($saved)) {
    echo '<p class="sans noticegreen">Saved.</p>';
}
echo '<form method="post">' . $app->csrf->field();
echo '<table class="list"><tr><th>Event</th><th>In-app</th><th>Email</th></tr>';
foreach ($keys as $k) {
    echo '<tr><td>' . h($labels[$k] ?? $k) . '</td>';
    echo '<td><input type="checkbox" name="inapp[' . h($k) . ']" value="1"' . (!empty($prefs['inapp'][$k]) ? ' checked' : '') . '></td>';
    echo '<td><input type="checkbox" name="email[' . h($k) . ']" value="1"' . (!empty($prefs['email'][$k]) ? ' checked' : '') . '></td></tr>';
}
echo '</table><p><input type="submit" class="lt_button" value="Save"></p></form>';
$app->view->end();
