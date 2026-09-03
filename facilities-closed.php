<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'facility', 'writlist'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->is('superintendent')) {
    $app->redirect('');
}
$app->view->start('Closed facilities', 'facilities', 'super');
echo '<h2 class="lt">Facilities</h2>';
echo '<h3 class="lt" style="display:inline-block;margin-right:0.75em">Closed facilities</h3>';
echo button('View open facilities', 'Open facilities', 'facilities.php', 'editNoteButton');
$app->writlist->renderSuperFacilities('facilities-closed.php', 'closed');
$app->view->end();
