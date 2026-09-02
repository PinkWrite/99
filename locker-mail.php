<?php
declare(strict_types=1);
$import = ['auth', 'view', 'html', 'csrf'];
require __DIR__ . '/lib/boot.php';
$app->auth->requireUser();
if (!$app->auth->is('superintendent')) {
    $app->redirect('locker.php');
}

function inkmail_serf(string $name, array $args = []): array
{
    $allow = ['inkmail', 'setbimi', 'inkdnsaddbimi'];
    if (!in_array($name, $allow, true)) {
        return [1, 'unknown'];
    }
    $bin = '/opt/verb/serfs/' . $name;
    if (!is_executable($bin)) {
        return [1, 'inkMail serfs are not on this host'];
    }
    $cmd = '/usr/bin/sudo -n ' . escapeshellarg($bin);
    foreach ($args as $a) {
        $cmd .= ' ' . escapeshellarg((string) $a);
    }
    $out = [];
    $code = 0;
    exec($cmd . ' 2>&1', $out, $code);
    return [$code, implode("\n", $out)];
}

$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app->csrf->check()) {
    $act = (string) ($_POST['act'] ?? '');
    $d = strtolower(trim((string) ($_POST['domain'] ?? '')));
    $u = strtolower(trim((string) ($_POST['user'] ?? '')));
    $e = strtolower(trim((string) ($_POST['email'] ?? '')));
    if ($act === 'domain' && $d !== '') {
        [, $flash] = inkmail_serf('inkmail', ['domain', $d]);
    } elseif ($act === 'box' && $u !== '' && $d !== '') {
        [, $flash] = inkmail_serf('inkmail', ['box', $u, $d]);
    } elseif ($act === 'alias' && $u !== '' && $d !== '' && $e !== '') {
        [, $flash] = inkmail_serf('inkmail', ['alias', $u, $d, $e]);
    } elseif ($act === 'bimi' && $d !== '' && is_uploaded_file($_FILES['svg']['tmp_name'] ?? '')) {
        $raw = (string) file_get_contents($_FILES['svg']['tmp_name']);
        if (stripos($raw, '<svg') === false) {
            $flash = 'Not an SVG.';
        } else {
            @mkdir('/srv/vip/files', 0750, true);
            $vip = '/srv/vip/files/' . $d . '.bimi.svg';
            if (@file_put_contents($vip, $raw) === false) {
                $flash = 'Could not write VIP drop.';
            } else {
                [, $flash] = inkmail_serf('setbimi', [$d, 'vip']);
            }
        }
    }
}

[, $which] = inkmail_serf('inkmail', ['which']);
[, $domains] = inkmail_serf('inkmail', ['showdomains']);

$app->view->start('Mail (Superintendent)', 'locker', 'super');
echo '<h2 class="lt">inkMail</h2>';
echo '<p class="sans dk">Postfix-Maddy agnostic control plane. Roundcube stays webmail. Uses 99 styling in this locker; the Go panel lives at po.emailURI.</p>';
if ($flash !== '') {
    echo '<p class="sans noticegreen">' . h($flash) . '</p>';
}
echo '<p class="sans"><code>' . h($which) . '</code></p>';

echo '<h3 class="lt">Domains</h3>';
echo '<pre class="readBox">' . h($domains) . '</pre>';
echo '<form method="post">' . $app->csrf->field() . '<input type="hidden" name="act" value="domain">';
echo '<p>Domain <input name="domain" required></p>';
echo '<p><input type="submit" class="lt_button" value="Add domain"></p></form>';

echo '<h3 class="lt">Mailbox</h3>';
echo '<form method="post">' . $app->csrf->field() . '<input type="hidden" name="act" value="box">';
echo '<p>User <input name="user" required> Domain <input name="domain" required></p>';
echo '<p><input type="submit" class="lt_button" value="Create box"></p></form>';

echo '<h3 class="lt">Alias</h3>';
echo '<form method="post">' . $app->csrf->field() . '<input type="hidden" name="act" value="alias">';
echo '<p>User <input name="user" required> Domain <input name="domain" required> Forward to <input name="email" required></p>';
echo '<p><input type="submit" class="lt_button" value="Add alias"></p></form>';

echo '<h3 class="lt">BIMI</h3>';
echo '<p class="sans dk">Uploads to <code>/srv/vip/files/domain.tld.bimi.svg</code> then <code>ink set bimi -p vip -d domain.tld</code>.</p>';
echo '<form method="post" enctype="multipart/form-data">' . $app->csrf->field() . '<input type="hidden" name="act" value="bimi">';
echo '<p>Domain <input name="domain" required> SVG <input type="file" name="svg" accept="image/svg+xml,.svg" required></p>';
echo '<p><input type="submit" class="lt_button" value="Install BIMI"></p></form>';

$app->view->end();
