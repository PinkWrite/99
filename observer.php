<?php
declare(strict_types=1);
$import = ['auth', 'view', 'html', 'user', 'writ', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->is('observer') && !$app->auth->atLeast('editor')) {
    $app->redirect('');
}
$u = $app->auth->user();
$ids = $app->user->observeeIds($u);
$app->view->start('Observer Dash for ' . $u['name'], 'observer', 'observer');
echo '<h2 class="lt">Observees</h2>';
if (!$ids) {
    echo '<p class="lt sans">No observees yet.</p>';
} else {
    echo '<table class="list lt sans"><tbody>';
    $cc = 'lr';
    foreach ($ids as $id) {
        $w = $app->user->find($id);
        if (!$w) {
            continue;
        }
        echo '<tr class="' . $cc . '">';
        echo '<td><b>' . h($w['name']) . '</b></td>';
        echo '<td>' . button('Observe writs', 'List work from this writer', 'writs-observer.php?o=' . $id, 'navDarkButton') . '</td>';
        echo '<td>' . button('Observe memos', 'List memos for this writer', 'memos-observer.php?w=' . $id, 'navDarkButton') . '</td>';
        echo '<td><small>(' . h((string) $w['username']) . ')</small></td>';
        echo '<td><small>' . h((string) $w['email']) . '</small></td>';
        echo '</tr>';
        $cc = $cc === 'lr' ? 'dr' : 'lr';
    }
    echo '</tbody></table>';
}
echo '<h2 class="lt">Writs</h2>';
$app->writlist->renderObserver('observer.php', $ids);
$app->view->end();
