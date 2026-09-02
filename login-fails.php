<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'clickathon'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->atLeast('supervisor')) {
    $app->redirect('');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlock']) && $app->csrf->check()) {
    $app->clickathon->unlock((int) $_POST['unlock']);
}
$app->view->start('Failed logins', 'locker', 'admin');
echo '<h2 class="lt">Failed logins</h2>';
echo '<p class="sans dk">After six failed tries from one IP, login is blocked for one hour, then opens again on its own. Unlock is only needed to let them in sooner.</p>';
echo '<table class="list"><tr><th>When</th><th>IP</th><th>Usernames</th><th></th></tr>';
foreach ($app->clickathon->recentFails() as $r) {
    echo '<tr><td>' . h($r['time_stamp']) . '</td><td>' . h($r['ip']) . '</td><td>' . h($r['username_list']) . '</td><td>';
    if (empty($r['unlocked'])) {
        echo post_button('Unlock', 'Unlock IP', 'login-fails.php', 'unlock', (string) $r['id'], 'set_gray', $app->csrf->token());
    } else {
        echo '<span class="dk">unlocked</span>';
    }
    echo '</td></tr>';
}
echo '</table>';
$app->view->end();
