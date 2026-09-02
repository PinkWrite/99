<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'totp', 'passkey', 'user', 'oauth'];
require __DIR__ . '/lib/boot.php';
$u = $app->auth->requireUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app->csrf->check()) {
    if (isset($_POST['totp_start'])) {
        $_SESSION['totp_pending'] = $app->totp->secret();
    } elseif (isset($_POST['totp_confirm']) && !empty($_SESSION['totp_pending'])) {
        if ($app->totp->verify($_SESSION['totp_pending'], (string) $_POST['code'])) {
            $app->user->setTotp($app->auth->id(), $_SESSION['totp_pending'], true);
            unset($_SESSION['totp_pending']);
            $u = $app->user->find($app->auth->id());
        }
    } elseif (isset($_POST['totp_off'])) {
        $app->user->setTotp($app->auth->id(), null, false);
        $u = $app->user->find($app->auth->id());
    } elseif (isset($_POST['id'], $_POST['spki'])) {
        $app->passkey->register($app->auth->id(), (string) $_POST['id'], (string) $_POST['spki'], (string) ($_POST['name'] ?? 'Passkey'));
        $app->redirect('security.php');
    } elseif (isset($_POST['del_pk'])) {
        $app->passkey->delete((int) $_POST['del_pk'], $app->auth->id());
    } elseif (isset($_POST['unlink_oauth'])) {
        $app->oauth->unlink($app->auth->id(), (string) $_POST['unlink_oauth']);
    }
}

$app->view->start('Security', 'locker');
if (!empty($_SESSION['oauth_err'])) {
    echo '<p class="sans noticered">' . h((string) $_SESSION['oauth_err']) . '</p>';
    unset($_SESSION['oauth_err']);
}
echo '<h2 class="lt">Authenticator (TOTP)</h2>';
if (!empty($u['totp_enabled'])) {
    echo '<p class="sans noticegreen">Authenticator is on.</p>';
    echo '<form method="post">' . $app->csrf->field() . '<input type="submit" name="totp_off" class="set_gray" value="Turn off"></form>';
} elseif (!empty($_SESSION['totp_pending'])) {
    $secret = $_SESSION['totp_pending'];
    $uri = $app->totp->uri($secret, $u['username'], $app->title());
    echo '<p class="sans">Add this account in your authenticator app, then enter a code.</p>';
    echo '<p class="sans"><code>' . h($secret) . '</code></p>';
    echo '<p class="dk sans"><small>' . h($uri) . '</small></p>';
    echo '<form method="post">' . $app->csrf->field();
    echo '<p>Code <input name="code" inputmode="numeric" required></p>';
    echo '<p><input type="submit" name="totp_confirm" class="lt_button" value="Confirm"></p></form>';
} else {
    echo '<form method="post">' . $app->csrf->field() . '<input type="submit" name="totp_start" class="lt_button" value="Set up authenticator"></form>';
}

echo '<h2 class="lt">Passkeys</h2>';
echo '<p class="sans dk">Works on https hosts. Platform or hardware key (YubiKey, etc.).</p>';
echo '<p><button type="button" class="lt_button" id="pkadd">Add a passkey</button></p>';
echo '<ul class="sans">';
foreach ($app->passkey->list($app->auth->id()) as $pk) {
    echo '<li>' . h($pk['name']) . ' · ' . h($pk['created_at']) . ' ';
    echo post_button('Remove', 'Delete', 'security.php', 'del_pk', (string) $pk['id'], 'set_gray', $app->csrf->token());
    echo '</li>';
}
echo '</ul>';
echo '<script src="js/pw99.js"></script><script>document.getElementById("pkadd").onclick=function(){pwPasskeyRegister("passkey-create.php","security.php",' . json_encode($app->csrf->token()) . ');};</script>';

echo '<h2 class="lt">Linked logins</h2>';
$have = [];
foreach ($app->oauth->list($app->auth->id()) as $row) {
    $have[$row['provider']] = $row;
}
echo '<table class="id-link"><tbody>';
foreach (['google' => 'Google', 'github' => 'GitHub', 'apple' => 'Apple'] as $p => $lab) {
    $on = isset($have[$p]);
    echo '<tr><td class="id-who">' . brand_icon($p) . '<span class="id-lab">' . h($lab) . '</span></td>';
    echo '<td class="id-mark">';
    echo $on ? brand_icon('check') : '&nbsp;';
    echo '</td><td class="id-act">';
    if ($on) {
        echo post_button('Disconnect', 'Stop using this login', 'security.php', 'unlink_oauth', $p, 'id-disconnect', $app->csrf->token());
    } else {
        echo '<a class="id-connect" href="oauth.php?p=' . h($p) . '&link=1">Connect</a>';
    }
    echo '</td></tr>';
}
echo '</tbody></table>';
$app->view->end();
