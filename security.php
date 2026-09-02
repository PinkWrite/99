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
    } elseif (isset($_POST['rename_pk'])) {
        $app->passkey->rename((int) $_POST['rename_pk'], $app->auth->id(), (string) ($_POST['pk_name'] ?? ''));
    } elseif (isset($_POST['del_pk'])) {
        $pkId = (int) $_POST['del_pk'];
        $pks = $app->passkey->list($app->auth->id());
        $oauths = $app->oauth->list($app->auth->id());
        if (empty($u['pass']) && count($pks) < 2 && $oauths === []) {
            // keep at least one way in
        } else {
            $app->passkey->delete($pkId, $app->auth->id());
        }
    } elseif (isset($_POST['unlink_oauth'])) {
        $pks = $app->passkey->list($app->auth->id());
        $oauths = $app->oauth->list($app->auth->id());
        if (empty($u['pass']) && $pks === [] && count($oauths) < 2) {
            // keep at least one way in
        } else {
            $app->oauth->unlink($app->auth->id(), (string) $_POST['unlink_oauth']);
        }
    } elseif (isset($_POST['disable_password'])) {
        $pks = $app->passkey->list($app->auth->id());
        $oauths = $app->oauth->list($app->auth->id());
        if ($pks !== [] && $oauths !== []) {
            $app->user->clearPassword($app->auth->id());
            $u = $app->user->find($app->auth->id()) ?? $u;
        }
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
    echo '<p class="sans">Scan this with your authenticator app, or type the secret below.</p>';
    echo '<div class="totp-qr">' . $app->totp->qrSvg($uri) . '</div>';
    echo '<p class="sans totp-secret"><code>' . h($secret) . '</code></p>';
    echo '<form method="post">' . $app->csrf->field();
    echo '<p class="field sans"><label for="totp_code">Code</label>';
    echo '<input name="code" id="totp_code" inputmode="numeric" autocomplete="one-time-code" required></p>';
    echo '<p><input type="submit" name="totp_confirm" class="lt_button" value="Confirm"></p></form>';
} else {
    echo '<form method="post">' . $app->csrf->field() . '<input type="submit" name="totp_start" class="lt_button" value="Set up authenticator"></form>';
}

echo '<h2 class="lt">Passkeys</h2>';
echo '<p class="sans dk">Works on https hosts. Platform or hardware key (YubiKey, etc.).</p>';
echo '<p><button type="button" class="lt_button" id="pkadd">Add a passkey</button></p>';
$pks = $app->passkey->list($app->auth->id());
if ($pks) {
    echo '<table class="id-link pk-list"><tbody>';
    foreach ($pks as $pk) {
        echo '<tr><td class="pk-name"><form method="post">' . $app->csrf->field();
        echo '<input type="hidden" name="rename_pk" value="' . (int) $pk['id'] . '">';
        echo '<input type="text" name="pk_name" value="' . h((string) $pk['name']) . '" maxlength="80" aria-label="Passkey name"> ';
        echo '<input type="submit" class="lt_button" value="Save"></form></td>';
        echo '<td class="sans dk pk-when">' . h((string) $pk['created_at']) . '</td><td>';
        echo post_button('Remove', 'Delete', 'security.php', 'del_pk', (string) $pk['id'], 'set_gray', $app->csrf->token());
        echo '</td></tr>';
    }
    echo '</tbody></table>';
}
echo '<script src="js/pw99.js"></script><script>document.getElementById("pkadd").onclick=function(){pwPasskeyRegister("passkey-create.php","security.php",' . json_encode($app->csrf->token()) . ');};</script>';

$have = [];
foreach ($app->oauth->list($app->auth->id()) as $row) {
    $have[$row['provider']] = $row;
}
$linkRows = '';
foreach (['google' => 'Google', 'github' => 'GitHub', 'apple' => 'Apple'] as $p => $lab) {
    $on = isset($have[$p]);
    if (!$on && !$app->oauth->enabled($p)) {
        continue;
    }
    $linkRows .= '<tr><td class="id-who">' . brand_icon($p) . '<span class="id-lab">' . h($lab) . '</span></td>';
    $linkRows .= '<td class="id-mark">' . ($on ? brand_icon('check') : '&nbsp;') . '</td><td class="id-act">';
    if ($on) {
        $linkRows .= post_button('Disconnect', 'Stop using this login', 'security.php', 'unlink_oauth', $p, 'id-disconnect', $app->csrf->token());
    } else {
        $linkRows .= '<a class="id-connect" href="oauth.php?p=' . h($p) . '&link=1">Connect</a>';
    }
    $linkRows .= '</td></tr>';
}
if ($linkRows !== '') {
    echo '<h2 class="lt">Linked logins</h2>';
    echo '<table class="id-link"><tbody>' . $linkRows . '</tbody></table>';
}
$pksNow = $app->passkey->list($app->auth->id());
$oauthNow = $app->oauth->list($app->auth->id());
if ($pksNow !== [] && $oauthNow !== []) {
    echo '<form method="post" id="nopwform" class="sans">' . $app->csrf->field();
    echo '<p><label><input type="checkbox" name="disable_password" id="disable_password" value="1"'
        . (empty($u['pass']) ? ' checked' : '') . '> Disable password login</label></p>';
    echo '</form>';
    echo '<script>
(function(){
  var cb = document.getElementById("disable_password");
  if (!cb) return;
  cb.addEventListener("change", function () {
    if (cb.checked) cb.form.submit();
    else window.location = "password.php";
  });
})();
</script>';
}
$app->view->end();
