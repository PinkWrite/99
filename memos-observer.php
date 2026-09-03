<?php
declare(strict_types=1);
$import = ['auth', 'view', 'html', 'note', 'user', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->is('observer') && !$app->auth->atLeast('editor')) {
    $app->redirect('');
}
$u = $app->auth->user();
$ids = $app->user->observeeIds($u);
$wid = (int) ($_GET['w'] ?? 0);
if ($wid > 0 && in_array($wid, $ids, true)) {
    $ids = [$wid];
}
$app->view->start('Memos', 'memos', 'observer');
echo '<h2 class="lt">Memos for observees</h2>';
if ($wid > 0 && $ids === [$wid]) {
    $one = $app->user->find($wid);
    echo '<p>' . button('Observe from all', 'All observees', 'memos-observer.php', 'navButton') . '</p>';
    if ($one) {
        echo '<h3 class="lt">' . h($one['name']) . '</h3>';
    }
}
$where = $wid > 0 ? 'memos-observer.php?w=' . $wid : 'memos-observer.php';
$app->writlist->renderObserverMemos($where, $ids);
$app->view->end();
