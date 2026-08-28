<?php
declare(strict_types=1);
/**
 * CLI updater. From the web root:
 *   php bin/update.php
 */
$root = dirname(__DIR__);
chdir($root);
$import = ['update'];
require $root . '/lib/boot.php';
if (!$app->db) {
    fwrite(STDERR, "No database. Check config.php\n");
    exit(1);
}
foreach ($app->update->run(true) as $line) {
    echo $line, "\n";
}
