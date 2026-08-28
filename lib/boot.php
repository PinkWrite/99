<?php
/**
 * PinkWrite 99 boot.
 *
 * Each page declares $import = ['auth','view',...]; then requires this file.
 * Only those modules load. Boot itself loads config + PDO. Nothing else.
 */
declare(strict_types=1);

if (!isset($import) || !is_array($import)) {
    $import = [];
}

$pw99Root = dirname(__DIR__);

require $pw99Root . '/lib/App.php';
require $pw99Root . '/lib/Db.php';
require $pw99Root . '/functions/text.php';

$configFile = $pw99Root . '/config.php';
$installing = (defined('PW99_INSTALLING') && PW99_INSTALLING === true);

if (!is_file($configFile)) {
    if ($installing) {
        $app = new App($pw99Root, [
            'configured' => false,
            'host' => '',
            'site_title' => 'PinkWrite 99',
            'db' => [],
            'mail' => ['transport' => 'off'],
        ], null);
        $app->load($import);
        return;
    }
    header('Location: install.php');
    exit;
}

$config = require $configFile;
if (!is_array($config) || empty($config['configured'])) {
    if (!$installing) {
        header('Location: install.php');
        exit;
    }
}

$db = null;
if (!empty($config['db']['name'])) {
    try {
        $db = Db::connect($config['db']);
    } catch (Throwable $e) {
        if ($installing) {
            $app = new App($pw99Root, $config, null);
            $app->bootError = $e->getMessage();
            return;
        }
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Database connection failed. The SysAdmin needs to check config.php.\n";
        echo "Use 127.0.0.1 (TCP), not localhost (Unix socket).\n";
        exit;
    }
}

$app = new App($pw99Root, $config, $db);
$app->load($import);
