/* PinkWrite 99 — save, wordcount, no-paste, passkeys */
(function () {
  function wordCount(s) {
    s = (s || '').trim();
    if (!s) return 0;
    return s.replace(/[\u2013\u2014]/g, ' ').replace(/\s+/g, ' ').split(' ').length;
  }

  window.pwWord = function (areaId, displayId, hiddenId) {
    var a = document.getElementById(areaId);
    if (!a) return;
    function tick() {
      var n = wordCount(a.value);
      var d = document.getElementById(displayId);
      var h = document.getElementById(hiddenId);
      if (d) d.textContent = n;
      if (h) h.value = n;
    }
    ['input', 'keyup', 'change', 'blur'].forEach(function (ev) {
      a.addEventListener(ev, tick);
    });
    tick();
  };

  window.pwNoPaste = function (id) {
    var a = document.getElementById(id);
    if (!a) return;
    ['paste', 'cut', 'drop', 'drag'].forEach(function (ev) {
      a.addEventListener(ev, function (e) { e.preventDefault(); return false; });
    });
  };

  window.pwAjaxForm = function (formId, postTo, updateId, extra) {
    var form = document.getElementById(formId);
    var box = document.getElementById(updateId);
    if (!form) return;
    var fd = new FormData(form);
    fd.append('ajax', '1');
    if (extra) Object.keys(extra).forEach(function (k) { fd.append(k, extra[k]); });
    var x = new XMLHttpRequest();
    x.open('POST', postTo);
    x.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    x.onload = function () {
      if (!box) return;
      var t = x.responseText;
      try {
        var j = JSON.parse(t);
        if (j.ok) {
          box.innerHTML = '<span class="noticegreen noticehide sans">' + (j.msg || 'Saved') + '</span>';
          if (j.source && form.source) form.source.value = j.source;
          if (j.id && form.test_id) form.test_id.value = j.id;
        } else {
          box.innerHTML = '<span class="noticered sans">' + (j.error || 'Save failed') + '</span>';
        }
      } catch (e) {
        box.innerHTML = t || '<span class="noticered sans">Save failed</span>';
      }
    };
    x.onerror = function () {
      if (box) box.innerHTML = '<span class="noticered sans">Network error</span>';
    };
    x.send(fd);
    window.onbeforeunload = null;
  };

  window.pwBindSave = function (formId, postTo, updateId, extra) {
    document.addEventListener('keydown', function (e) {
      if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.keyCode === 83)) {
        e.preventDefault();
        pwAjaxForm(formId, postTo, updateId, extra);
      }
    });
  };

  window.onNavWarn = function () {
    window.onbeforeunload = function () { return ''; };
  };
  window.offNavWarn = function () { window.onbeforeunload = null; };

  function b64uToBuf(s) {
    s = s.replace(/-/g, '+').replace(/_/g, '/');
    while (s.length % 4) s += '=';
    var bin = atob(s);
    var u = new Uint8Array(bin.length);
    for (var i = 0; i < bin.length; i++) u[i] = bin.charCodeAt(i);
    return u.buffer;
  }
  function bufToB64u(buf) {
    var u = new Uint8Array(buf);
    var s = '';
    for (var i = 0; i < u.length; i++) s += String.fromCharCode(u[i]);
    return btoa(s).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
  }

  window.pwPasskeyRegister = async function (optUrl, saveUrl, csrf) {
    var opt = await (await fetch(optUrl, { credentials: 'same-origin' })).json();
    opt.challenge = b64uToBuf(opt.challenge);
    opt.user.id = b64uToBuf(opt.user.id);
    var cred = await navigator.credentials.create({ publicKey: opt });
    var spki = cred.response.getPublicKey();
    var body = new URLSearchParams();
    body.set('_csrf', csrf);
    body.set('id', bufToB64u(cred.rawId));
    body.set('spki', bufToB64u(spki));
    body.set('name', 'Passkey');
    var r = await fetch(saveUrl, { method: 'POST', body: body, credentials: 'same-origin' });
    location.reload();
    return r;
  };

  window.pwPasskeyLogin = async function (optUrl) {
    var opt = await (await fetch(optUrl, { credentials: 'same-origin' })).json();
    opt.challenge = b64uToBuf(opt.challenge);
    if (opt.allowCredentials) {
      opt.allowCredentials.forEach(function (c) { c.id = b64uToBuf(c.id); });
    }
    var cred = await navigator.credentials.get({ publicKey: opt });
    var fd = new FormData();
    fd.set('passkey', '1');
    fd.set('id', bufToB64u(cred.rawId));
    fd.set('clientData', bufToB64u(cred.response.clientDataJSON));
    fd.set('authData', bufToB64u(cred.response.authenticatorData));
    fd.set('sig', bufToB64u(cred.response.signature));
    var x = await fetch('login.php', { method: 'POST', body: fd, credentials: 'same-origin' });
    if (x.redirected) { location.href = x.url; return; }
    location.href = './';
  };

  function pwConfirmMask() {
    var m = document.getElementById('pw-confirm-mask');
    if (m) return m;
    m = document.createElement('div');
    m.id = 'pw-confirm-mask';
    m.setAttribute('title', 'Cancel');
    document.body.appendChild(m);
    m.addEventListener('click', pwConfirmClose);
    return m;
  }

  function pwConfirmClose() {
    var nodes = document.querySelectorAll('.pw-confirm-wrap.is-open');
    for (var i = 0; i < nodes.length; i++) {
      nodes[i].classList.remove('is-open');
      var go = nodes[i].querySelector('.pw-confirm-go');
      var cancel = nodes[i].querySelector('.pw-confirm-cancel');
      var yes = nodes[i].querySelector('.pw-confirm-yes');
      if (go) go.hidden = false;
      if (cancel) cancel.hidden = true;
      if (yes) {
        yes.hidden = true;
        yes.disabled = true;
      }
    }
    var m = document.getElementById('pw-confirm-mask');
    if (m) m.style.display = 'none';
  }

  function pwConfirmOpen(wrap) {
    if (!wrap) return;
    pwConfirmClose();
    pwConfirmMask().style.display = 'block';
    wrap.classList.add('is-open');
    var go = wrap.querySelector('.pw-confirm-go');
    var cancel = wrap.querySelector('.pw-confirm-cancel');
    var yes = wrap.querySelector('.pw-confirm-yes');
    if (go) go.hidden = true;
    if (cancel) cancel.hidden = false;
    if (yes) {
      yes.hidden = false;
      yes.disabled = false;
    }
  }

  document.addEventListener('click', function (e) {
    var go = e.target.closest ? e.target.closest('.pw-confirm-go') : null;
    if (go) {
      e.preventDefault();
      pwConfirmOpen(go.closest('.pw-confirm-wrap'));
      return;
    }
    if (e.target.closest && e.target.closest('.pw-confirm-cancel')) {
      e.preventDefault();
      pwConfirmClose();
    }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' || e.keyCode === 27) pwConfirmClose();
  });
})();
