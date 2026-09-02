<?php
declare(strict_types=1);
$import = ['auth', 'view', 'html', 'user', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->is('observer') && !$app->auth->atLeast('editor')) {
    $app->redirect('');
}
$u = $app->auth->user();
$ids = $app->user->observeeIds($u);
$oid = (int) ($_GET['o'] ?? 0);
if ($oid > 0 && in_array($oid, $ids, true)) {
    $w = $app->user->find($oid) ?: [];
    $app->view->start('Observing: ' . (string) ($w['name'] ?? ''), 'owrits', 'observer');
    echo '<h3 class="lt">Observing: ' . h((string) ($w['name'] ?? '')) . '<small> (' . h((string) ($w['username'] ?? '')) . ') ' . h((string) ($w['email'] ?? '')) . '</small></h3>';
    echo '<p>' . button('Observe from all', 'List work from all writers', 'observer.php', 'navButton') . '</p>';
    $app->writlist->renderObserver('writs-observer.php?o=' . $oid, [$oid]);
} else {
    $app->view->start('Observed Writs', 'owrits', 'observer');
    echo '<h2 class="lt">Observed Writs</h2>';
    $app->writlist->renderObserver('writs-observer.php', $ids);
}
$app->view->end();
