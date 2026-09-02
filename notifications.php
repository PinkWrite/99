<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'notify'];
require __DIR__ . '/lib/boot.php';
$u = $app->auth->requireUser();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ack']) && $app->csrf->check()) {
    $app->notify->ack((int) $_POST['ack'], $app->auth->id());
    $app->redirect('notifications.php');
}
$app->view->start('Notifications', 'notify', 'auto');
echo '<h2 class="lt">Notifications</h2>';
echo '<p class="sans dk">Open the item, then acknowledge — that deletes the notice for good.</p>';
$rows = $app->notify->list($app->auth->id());
if (!$rows) {
    echo '<p class="sans">None.</p>';
}
foreach ($rows as $n) {
    echo '<p class="sans"><b>' . h($n['title']) . '</b> <small class="dk">' . h($n['created_at']) . '</small><br>';
    echo h($n['body'] ?? '');
    if ($n['link']) {
        echo ' ' . button('View', 'Open', $n['link'], 'editNoteButton');
    }
    echo ' ' . post_button('Acknowledge', 'Delete this notice', 'notifications.php', 'ack', (string) $n['id'], 'set_gray', $app->csrf->token());
    echo '</p>';
}
$app->view->end();
