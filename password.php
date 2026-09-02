<?php
declare(strict_types=1);
$import = ['auth', 'csrf', 'view', 'html', 'user', 'notify', 'passkey', 'oauth'];
require __DIR__ . '/lib/boot.php';
$u = $app->auth->requireUser();
$msg = $err = '';
$uid = $app->auth->id();
$pks = $app->passkey->list($uid);
$oauths = $app->oauth->list($uid);
$canDisable = $pks !== [] && $oauths !== [];
$noPass = empty($u['pass']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $app->csrf->check()) {
    if (isset($_POST['disable_password']) && $canDisable) {
        $app->user->clearPassword($uid);
        $u = $app->user->find($uid) ?? $u;
        $noPass = true;
        $msg = 'Password login is off.';
    } else {
        $cur = (string) ($_POST['current'] ?? '');
        $p1 = (string) ($_POST['pass1'] ?? '');
        $p2 = (string) ($_POST['pass2'] ?? '');
        if ($p1 === '' && $p2 === '') {
            // checkbox uncheck does not POST here
        } elseif (!$noPass && !password_verify($cur, (string) $u['pass'])) {
            $err = 'Current password is incorrect.';
        } elseif (strlen($p1) < 8 || $p1 !== $p2) {
            $err = 'New passwords must match and be at least 8 characters.';
        } else {
            $app->user->setPassword($uid, $p1);
            $msg = 'Password changed.';
            $noPass = false;
            $u = $app->user->find($uid) ?? $u;
            if ($u['editor_id']) {
                $app->notify->send((int) $u['editor_id'], 'password_change', $u['username'] . ' changed their password', 'writer.php?u=' . $uid);
            }
        }
    }
}

$app->view->start('Password', 'locker');
echo '<h2 class="lt">Password</h2>';
if ($err) {
    echo '<p class="sans noticered">' . h($err) . '</p>';
}
if ($msg) {
    echo '<p class="sans noticegreen">' . h($msg) . '</p>';
}
if ($canDisable) {
    echo '<form method="post" id="nopwform" class="sans">' . $app->csrf->field();
    echo '<p><label><input type="checkbox" name="disable_password" id="disable_password" value="1"'
        . ($noPass ? ' checked' : '') . '> Disable password login</label></p>';
    echo '</form>';
}
echo '<form method="post" id="pwform" class="pw-pass-fields' . ($noPass && $canDisable ? ' pw-off' : '') . '">' . $app->csrf->field();
if (!$noPass) {
    echo '<p class="sans">Current<br><input type="password" name="current" required' . ($noPass && $canDisable ? ' disabled' : '') . '></p>';
}
echo '<p class="sans">New<br><input type="password" name="pass1" required' . ($noPass && $canDisable ? ' disabled' : '') . '></p>';
echo '<p class="sans">Confirm<br><input type="password" name="pass2" required' . ($noPass && $canDisable ? ' disabled' : '') . '></p>';
echo '<p><input type="submit" class="lt_button" value="' . ($noPass ? 'Set password' : 'Change password') . '"'
    . ($noPass && $canDisable ? ' disabled' : '') . '></p></form>';
echo '<script>
(function(){
  var cb = document.getElementById("disable_password");
  var form = document.getElementById("pwform");
  var cut = document.getElementById("nopwform");
  if (!cb || !form) return;
  function apply() {
    var on = cb.checked;
    form.classList.toggle("pw-off", on);
    Array.prototype.forEach.call(form.querySelectorAll("input"), function (i) {
      if (i.type === "hidden") return;
      i.disabled = on;
    });
  }
  apply();
  cb.addEventListener("change", function () {
    apply();
    if (cb.checked && cut) cut.submit();
  });
})();
</script>';
$app->view->end();
