<?php
declare(strict_types=1);
$import = ['auth', 'view', 'html', 'user', 'block', 'facility'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('supervisor')) {
    $app->redirect('');
}
$fid = $app->auth->facilityId();
$app->view->start('Admin', 'admin');
echo '<h2 class="lt">Facility</h2>';
if ($fid) {
    $f = $app->facility->find($fid);
    echo '<p class="sans">' . h($f['name'] ?? '') . '</p>';
}
echo '<p>' . button('Staffing', 'Users', 'staffing.php', 'navDarkButton') . ' ';
echo button('Enrollment', 'Writers', 'enrollment.php', 'navDarkButton') . ' ';
echo button('Blocks', 'Blocks', 'blocks-editor.php', 'navDarkButton') . ' ';
echo button('Failed logins', 'Clickathon', 'login-fails.php', 'navDarkButton') . '</p>';
echo '<h3 class="lt">People</h3><table class="list"><tr><th>Name</th><th>Type</th><th>Username</th><th>Status</th></tr>';
foreach ($app->user->listByFacility($fid) as $row) {
    echo '<tr><td>' . h($row['name']) . '</td><td>' . h($row['type']) . '</td><td>' . h($row['username']) . '</td><td>' . h($row['status']) . '</td></tr>';
}
echo '</table>';
$app->view->end();
