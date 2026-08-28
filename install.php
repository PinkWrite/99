<?php
declare(strict_types=1);
define('PW99_INSTALLING', true);
$import = ['html', 'text', 'csrf', 'user', 'auth', 'update'];
require __DIR__ . '/lib/boot.php';

$errors = [];
$notice = '';
$hasConfig = is_file(__DIR__ . '/config.php');
$allowSuper = $hasConfig && !empty($app->config['allow_create_super']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    $db_name = preg_replace('/[^A-Za-z0-9_]/', '', (string) ($_POST['db_name'] ?? ''));
    $db_user = preg_replace('/[^A-Za-z0-9_]/', '', (string) ($_POST['db_user'] ?? ''));
    $db_pass = (string) ($_POST['db_pass'] ?? '');
    $db_host = trim((string) ($_POST['db_host'] ?? '127.0.0.1'));
    if ($db_host === 'localhost') {
        $db_host = '127.0.0.1';
    }
    $db_port = (int) ($_POST['db_port'] ?? 3306);
    $host = trim((string) ($_POST['host'] ?? ''), '/');
    $host = preg_replace('#^https?://#i', '', $host) ?? $host;
    $site = trim((string) ($_POST['site_title'] ?? 'PinkWrite 99'));
    $mail_from = trim((string) ($_POST['mail_from'] ?? ''));
    $username = (string) ($_POST['username'] ?? '');
    $email = (string) ($_POST['email'] ?? '');
    $name = trim((string) ($_POST['name'] ?? ''));
    $pass1 = (string) ($_POST['pass1'] ?? '');
    $pass2 = (string) ($_POST['pass2'] ?? '');

    if (!$hasConfig) {
        if ($db_name === '' || $db_user === '') {
            $errors[] = 'Database name and user are required.';
        }
        if ($host === '') {
            $errors[] = 'Host is required (no https://).';
        }
        try {
            $probe = Db::connect([
                'host' => $db_host, 'port' => $db_port, 'name' => $db_name,
                'user' => $db_user, 'pass' => $db_pass, 'charset' => 'utf8mb4',
            ]);
        } catch (Throwable $e) {
            $errors[] = 'Could not connect: ' . $e->getMessage() . ' — use 127.0.0.1 not localhost.';
            $probe = null;
        }
    }

    if (!preg_match('/^[A-Za-z0-9]{4,32}$/', $username)) {
        $errors[] = 'Username: 4–32 letters or digits.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email required.';
    }
    if (strlen($pass1) < 8 || $pass1 !== $pass2) {
        $errors[] = 'Password must match and be at least 8 characters.';
    }
    if ($name === '') {
        $errors[] = 'Name required.';
    }

    if (!$errors && !$hasConfig) {
        $export = static fn ($s) => var_export((string) $s, true);
        $from = $mail_from !== '' ? $mail_from : 'noreply@' . preg_replace('#/.*$#', '', $host);
        $cfg = "<?php\nreturn [\n"
            . "    'configured' => true,\n"
            . "    'allow_create_super' => false,\n"
            . "    'host' => " . $export($host) . ",\n"
            . "    'site_title' => " . $export($site) . ",\n"
            . "    'db' => [\n"
            . "        'host' => " . $export($db_host) . ",\n"
            . "        'port' => " . $db_port . ",\n"
            . "        'name' => " . $export($db_name) . ",\n"
            . "        'user' => " . $export($db_user) . ",\n"
            . "        'pass' => " . $export($db_pass) . ",\n"
            . "        'charset' => 'utf8mb4',\n"
            . "    ],\n"
            . "    'mail' => [\n"
            . "        'transport' => 'mail',\n"
            . "        'from' => " . $export($from) . ",\n"
            . "        'from_name' => " . $export($site) . ",\n"
            . "        'smtp_host' => '127.0.0.1',\n"
            . "        'smtp_port' => 587,\n"
            . "        'smtp_user' => '',\n"
            . "        'smtp_pass' => '',\n"
            . "        'smtp_secure' => 'tls',\n"
            . "    ],\n"
            . "    'github' => 'https://github.com/PinkWrite/99.git',\n"
            . "    'github_branch' => 'master',\n"
            . "];\n";
        if (file_put_contents(__DIR__ . '/config.php', $cfg) === false) {
            $errors[] = 'Could not write config.php — check folder permissions.';
        } else {
            $app->config = require __DIR__ . '/config.php';
            $app->db = Db::connect($app->config['db']);
            $app->user = new UserRepo($app);
            require_once __DIR__ . '/sql/migrate.php';
            pw99_migrate($app);
        }
    }

    if (!$errors && $hasConfig && !$allowSuper) {
        $errors[] = 'Already installed. Set allow_create_super in config.php for walk-in Superintendent recovery.';
    }

    if (!$errors && $app->db) {
        require_once __DIR__ . '/sql/migrate.php';
        pw99_migrate($app);
        if ($app->user->findByUsername($username) || $app->user->findByEmail($email)) {
            $errors[] = 'That username or email already exists.';
        } else {
            $id = $app->user->create([
                'type' => 'superintendent',
                'facility_id' => null,
                'username' => $username,
                'email' => $email,
                'name' => $name,
                'pass' => password_hash($pass1, PASSWORD_DEFAULT),
                'notify_prefs' => json_enc(['inapp' => [], 'email' => []]),
            ]);
            $notice = 'Superintendent created (id ' . $id . '). Log in. Then create a Facility and an Administrator.';
        }
    }
}

function app_export($s) { return var_export((string)$s, true); }

?><!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Install PinkWrite 99</title>
<link rel="stylesheet" href="css/styles.css">
</head><body><div id="wrap"><div class="page"><div class="content">
<h1 class="lt">Install PinkWrite 99</h1>
<p class="sans dk">SysAdmin only. Mail, host, and database live in <code>config.php</code>.</p>
<?php
foreach ($errors as $e) {
    echo '<p class="sans noticered">' . h($e) . '</p>';
}
if ($notice) {
    echo '<p class="sans noticegreen">' . h($notice) . ' <a href="login.php">Login</a></p>';
}
if ($hasConfig && !$allowSuper && !$notice) {
    echo '<p class="sans">Installed. <a href="login.php">Login</a></p>';
    echo '<p class="sans dk">Walk-in Superintendent recovery: set <code>allow_create_super</code> to true in the config, reload this page, then set it false again.</p>';
} elseif (!$notice) {
    echo '<form method="post" class="sans">';
    echo '<input type="hidden" name="install" value="1">';
    if (!$hasConfig) {
        echo '<h3 class="lt">Database (TCP)</h3>';
        echo '<p>Host <input name="db_host" value="127.0.0.1"> Port <input name="db_port" value="3306" size="5"></p>';
        echo '<p>Name <input name="db_name" required> User <input name="db_user" required> Password <input type="password" name="db_pass"></p>';
        echo '<h3 class="lt">Public host</h3>';
        echo '<p class="dk">No http:// — examples: <code>write.pink</code>, <code>99.example.org</code>, <code>example.org/99</code>. Always served as https://</p>';
        echo '<p><input name="host" placeholder="write.pink/99" required></p>';
        echo '<p>Site title <input name="site_title" value="PinkWrite 99"></p>';
        echo '<p>Mail from <input name="mail_from" placeholder="noreply@write.pink"> (SMTP later in config)</p>';
    }
    echo '<h3 class="lt">First Superintendent</h3>';
    echo '<p>Name <input name="name" required> Username <input name="username" required></p>';
    echo '<p>Email <input type="email" name="email" required></p>';
    echo '<p>Password <input type="password" name="pass1" required> Confirm <input type="password" name="pass2" required></p>';
    echo '<p><input type="submit" class="lt_button" value="Install"></p>';
    echo '</form>';
}
?>
</div></div></div></body></html>
