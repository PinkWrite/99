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
          if (j.work != null && form.work && !String(form.work.value || '').trim()) {
            form.work.value = j.work;
          }
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

  window.pwDrawTotpQr = function (elId) {
    var el = document.getElementById(elId);
    if (!el || typeof qrcodegen === 'undefined') return;
    var uri = el.getAttribute('data-otpauth') || '';
    if (!uri) return;
    var qr = qrcodegen.QrCode.encodeText(uri, qrcodegen.QrCode.Ecc.MEDIUM);
    var n = qr.size;
    var q = 4;
    var dim = n + 2 * q;
    var parts = ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + dim + ' ' + dim + '" width="240" height="240" shape-rendering="crispEdges" aria-hidden="true">'];
    parts.push('<rect width="' + dim + '" height="' + dim + '" fill="#ffffff"/>');
    for (var y = 0; y < n; y++) {
      for (var x = 0; x < n; x++) {
        if (qr.getModule(x, y)) {
          parts.push('<rect x="' + (x + q) + '" y="' + (y + q) + '" width="1" height="1" fill="#111111"/>');
        }
      }
    }
    parts.push('</svg>');
    el.innerHTML = parts.join('');
  };

  window.pwPkEdit = function (btn) {
    var form = btn.closest ? btn.closest('.pk-row') : btn.form;
    if (!form) return;
    var rows = document.querySelectorAll('.pk-row.is-editing');
    for (var i = 0; i < rows.length; i++) {
      if (rows[i] !== form) pwPkClose(rows[i]);
    }
    pwPkOpen(form);
  };

  function pwPkOpen(form) {
    form.classList.add('is-editing');
    var label = form.querySelector('.pk-label');
    var input = form.querySelector('.pk-input');
    var edit = form.querySelector('.pk-edit');
    var save = form.querySelector('.pk-save');
    if (label) label.hidden = true;
    if (edit) edit.hidden = true;
    if (save) save.hidden = false;
    if (input) {
      input.setAttribute('data-orig', input.value);
      input.hidden = false;
      input.focus();
      input.select();
    }
  }

  function pwPkClose(form) {
    form.classList.remove('is-editing');
    var label = form.querySelector('.pk-label');
    var input = form.querySelector('.pk-input');
    var edit = form.querySelector('.pk-edit');
    var save = form.querySelector('.pk-save');
    if (label) label.hidden = false;
    if (input) input.hidden = true;
    if (edit) edit.hidden = false;
    if (save) save.hidden = true;
  }

  window.pwPkCancel = function (btn) {
    var form = btn.closest ? btn.closest('.pk-row') : btn.form;
    if (!form) return;
    var input = form.querySelector('.pk-input');
    var orig = input ? input.getAttribute('data-orig') : null;
    if (input && orig !== null) input.value = orig;
    pwPkClose(form);
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

  var pwConfirmOpenWrap = null;

  function pwConfirmClearPos(el) {
    if (!el) return;
    el.style.top = '';
    el.style.left = '';
  }

  function pwConfirmPlace(wrap) {
    if (!wrap) return;
    var go = wrap.querySelector('.pw-confirm-go');
    var cancel = wrap.querySelector('.pw-confirm-cancel');
    var yes = wrap.querySelector('.pw-confirm-yes');
    if (!go || !cancel || !yes) return;
    var r = go.getBoundingClientRect();
    var gap = 8;
    var ch = cancel.offsetHeight || r.height;
    var top = r.top - gap - ch;
    if (top < 8) top = r.bottom + gap;
    cancel.style.top = top + 'px';
    cancel.style.left = r.left + 'px';
    var cr = cancel.getBoundingClientRect();
    var yesLeft = cr.right + gap;
    var yesW = yes.offsetWidth || r.width;
    if (yesLeft + yesW > window.innerWidth - 8) {
      yesLeft = Math.max(8, window.innerWidth - 8 - yesW);
    }
    yes.style.top = cr.top + 'px';
    yes.style.left = yesLeft + 'px';
  }

  function pwConfirmClose() {
    var nodes = document.querySelectorAll('.pw-confirm-wrap.is-open');
    for (var i = 0; i < nodes.length; i++) {
      nodes[i].classList.remove('is-open');
      var cancel = nodes[i].querySelector('.pw-confirm-cancel');
      var yes = nodes[i].querySelector('.pw-confirm-yes');
      if (cancel) {
        cancel.hidden = true;
        pwConfirmClearPos(cancel);
      }
      if (yes) {
        yes.hidden = true;
        yes.disabled = true;
        pwConfirmClearPos(yes);
      }
    }
    pwConfirmOpenWrap = null;
    var m = document.getElementById('pw-confirm-mask');
    if (m) m.style.display = 'none';
  }

  function pwConfirmOpen(wrap) {
    if (!wrap) return;
    pwConfirmClose();
    pwConfirmMask().style.display = 'block';
    wrap.classList.add('is-open');
    var cancel = wrap.querySelector('.pw-confirm-cancel');
    var yes = wrap.querySelector('.pw-confirm-yes');
    if (cancel) cancel.hidden = false;
    if (yes) {
      yes.hidden = false;
      yes.disabled = false;
    }
    pwConfirmOpenWrap = wrap;
    pwConfirmPlace(wrap);
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
  window.addEventListener('scroll', function () {
    if (pwConfirmOpenWrap) pwConfirmPlace(pwConfirmOpenWrap);
  }, true);
  window.addEventListener('resize', function () {
    if (pwConfirmOpenWrap) pwConfirmPlace(pwConfirmOpenWrap);
  });
})();
