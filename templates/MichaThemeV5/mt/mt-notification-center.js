/* Glocke: Panel mit X aktuellen, Modal "Alle anzeigen", Alle gelesen, Ausblenden (Daten vom Plugin) */
(function () {
  'use strict';
  var readKey = 'mt-notification-read-v1';
  var hideKey = 'mt-notification-hidden-v1';
  function load(key) {
    try { var v = JSON.parse(localStorage.getItem(key) || '[]'); return new Set(Array.isArray(v) ? v.filter(function (x) { return typeof x === 'string' && x.length < 120; }).slice(-500) : []); } catch (e) { return new Set(); }
  }
  function save(key, set) { try { localStorage.setItem(key, JSON.stringify(Array.from(set).slice(-500))); return true; } catch (e) { return false; } }
  var publicRead = load(readKey);
  var publicHidden = load(hideKey);

  function keyOf(item) { return (item.personal ? 'p' : 'o') + item.id + ':' + (item.published_at || ''); }
  function isRead(item) { return item.personal ? !!item.read : publicRead.has(keyOf(item)); }
  function safeURL(value) {
    try { var u = new URL(value, location.href); return value && (u.protocol === 'http:' || u.protocol === 'https:') && !u.username && !u.password ? u.href : ''; } catch (e) { return ''; }
  }
  // Inhalt mit Links, nur erlaubte Tags
  var allowed = { P: 1, BR: 1, STRONG: 1, B: 1, EM: 1, I: 1, U: 1, UL: 1, OL: 1, LI: 1, A: 1, H3: 1, H4: 1, BLOCKQUOTE: 1 };
  var dropped = { SCRIPT: 1, STYLE: 1, TEMPLATE: 1, IFRAME: 1, OBJECT: 1, EMBED: 1, NOSCRIPT: 1, SVG: 1, MATH: 1, TEXTAREA: 1, SELECT: 1 };
  function richText(html) {
    var src = new DOMParser().parseFromString('<div>' + String(html || '') + '</div>', 'text/html').body.firstChild;
    var out = document.createDocumentFragment();
    (function walk(node, target) {
      node.childNodes.forEach(function (child) {
        if (child.nodeType === 3) { target.append(child.textContent); return; }
        if (child.nodeType !== 1 || dropped[child.tagName.toUpperCase()]) { return; }
        if (!allowed[child.tagName]) { walk(child, target); return; }
        var el = document.createElement(child.tagName.toLowerCase());
        if (child.tagName === 'A') {
          var href = safeURL(child.getAttribute('href') || '');
          if (!href) { walk(child, target); return; }
          el.href = href;
          if (new URL(href).origin !== location.origin) { el.target = '_blank'; el.rel = 'noopener noreferrer'; }
        }
        walk(child, el); target.append(el);
      });
    }(src || document.createElement('div'), out));
    return out;
  }
  function plainText(html) {
    var d = document.createElement('div'); d.append(richText(html));
    var parts = [], tw = document.createTreeWalker(d, NodeFilter.SHOW_TEXT);
    while (tw.nextNode()) { parts.push(tw.currentNode.textContent); }
    return parts.join(' ').replace(/\s+/g, ' ').replace(/ ([.,;:!?])/g, '$1').trim();
  }
  function formatDate(v) {
    var d = new Date(String(v || '').replace(' ', 'T') + 'Z');
    return isNaN(d) ? '' : d.toLocaleDateString(document.documentElement.lang || 'de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });
  }
  function el(tag, cls, text) { var n = document.createElement(tag); if (cls) { n.className = cls; } if (text !== undefined) { n.textContent = text; } return n; }

  document.querySelectorAll('[data-mt-notification-center]').forEach(function (root) {
    var button = root.querySelector('.mt-notification-button');
    var panel = root.querySelector('.mt-notification-panel');
    var count = root.querySelector('.mt-notification-count');
    if (!button || !panel) { return; }
    function close(focusBack) { panel.hidden = true; button.setAttribute('aria-expanded', 'false'); if (focusBack) { button.focus(); } }
    button.addEventListener('click', function () {
      var open = button.getAttribute('aria-expanded') !== 'true';
      button.setAttribute('aria-expanded', String(open)); panel.hidden = !open;
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !panel.hidden) { close(true); } });
    document.addEventListener('click', function (e) { if (!panel.hidden && e.composedPath().indexOf(root) === -1) { close(false); } });

    var dataNode = root.querySelector('[data-mt-notification-data]');
    var data;
    try { data = JSON.parse(dataNode ? dataNode.textContent : ''); } catch (e) { return; } // altes Plugin: einfache Liste bleibt
    var items = Array.isArray(data.items) ? data.items : [];
    items.sort(function (a, b) { return String(b.published_at || '').localeCompare(String(a.published_at || '')); });
    var limit = Math.max(1, Math.min(30, Number(data.settings && data.settings.visible_limit) || 5));
    var hiddenPersonal = new Set();
    var busy = false;
    var token = root.getAttribute('data-token') || (document.querySelector('input.jtl_token') || {}).value || '';

    // JTL IO
    function io(action, id) {
      var body = new URLSearchParams();
      body.set('io', JSON.stringify({ name: 'mgdNotificationAction', params: [action, id || 0, token] }));
      return fetch(root.getAttribute('data-io'), { method: 'POST', credentials: 'same-origin', body: body })
        .then(function (r) { if (!r.ok) { throw new Error('io'); } return r.json(); })
        .then(function (res) { return res && res.success === true; })
        .catch(function () { return false; });
    }
    function isHidden(item) { return item.personal ? hiddenPersonal.has(item.id) : publicHidden.has(keyOf(item)); }
    function visible() { return items.filter(function (i) { return !isHidden(i); }); }

    function markRead(item) {
      if (isRead(item)) { return Promise.resolve(true); }
      if (!item.personal) { publicRead.add(keyOf(item)); save(readKey, publicRead); render(); return Promise.resolve(true); }
      return io('read', item.id).then(function (ok) { if (ok) { item.read = true; render(); } return ok; });
    }
    function hide(item) {
      if (!item.personal) { publicHidden.add(keyOf(item)); publicRead.add(keyOf(item)); save(hideKey, publicHidden); save(readKey, publicRead); render(); return Promise.resolve(true); }
      return io('dismiss', item.id).then(function (ok) { if (ok) { hiddenPersonal.add(item.id); render(); } return ok; });
    }
    function readAll() {
      var list = visible();
      list.forEach(function (i) { if (!i.personal) { publicRead.add(keyOf(i)); } });
      save(readKey, publicRead);
      var need = list.some(function (i) { return i.personal && !i.read; });
      return (need ? io('read_all') : Promise.resolve(true)).then(function (ok) {
        if (ok) { list.forEach(function (i) { if (i.personal) { i.read = true; } }); }
        render(); return ok;
      });
    }
    function hideRead() {
      var list = visible().filter(isRead);
      list.forEach(function (i) { if (!i.personal) { publicHidden.add(keyOf(i)); } });
      save(hideKey, publicHidden);
      var need = list.some(function (i) { return i.personal; });
      return (need ? io('dismiss_read') : Promise.resolve(true)).then(function (ok) {
        if (ok) { list.forEach(function (i) { if (i.personal) { hiddenPersonal.add(i.id); } }); }
        render(); return { ok: ok, count: list.length };
      });
    }

    // Panel neu aufbauen
    panel.replaceChildren();
    var head = el('div', 'mt-notification-head');
    var tools = el('div', 'mt-notification-tools');
    var readAllBtn = el('button', 'mt-notification-tool', 'Alle gelesen'); readAllBtn.type = 'button';
    var hideReadBtn = el('button', 'mt-notification-tool', 'Gelesene ausblenden'); hideReadBtn.type = 'button';
    tools.append(readAllBtn, hideReadBtn);
    head.append(el('strong', '', 'Benachrichtigungen'), tools);
    var status = el('p', 'mt-notification-status'); status.setAttribute('role', 'status'); status.setAttribute('aria-live', 'polite');
    var empty = el('p', 'mt-notification-empty', 'Keine neuen Benachrichtigungen.');
    var list = el('ul', 'mt-notification-list');
    var showAll = el('button', 'mt-notification-show-all', 'Alle anzeigen'); showAll.type = 'button'; showAll.setAttribute('aria-haspopup', 'dialog');
    panel.append(head, status, empty, list, showAll);

    // Modal
    var dialog = el('dialog', 'mt-notification-dialog');
    var dId = 'mt-notification-all-' + Math.random().toString(36).slice(2, 8);
    dialog.setAttribute('aria-labelledby', dId);
    var dHead = el('header');
    var dTitle = el('h2', '', 'Alle Benachrichtigungen'); dTitle.id = dId;
    var dReadAll = el('button', 'mt-notification-tool', 'Alle gelesen'); dReadAll.type = 'button';
    var dHideRead = el('button', 'mt-notification-tool', 'Gelesene ausblenden'); dHideRead.type = 'button';
    var dClose = el('button', 'mt-notification-dialog-close', '×'); dClose.type = 'button'; dClose.setAttribute('aria-label', 'Fenster schließen');
    dHead.append(dTitle, dReadAll, dHideRead, dClose);
    var dBody = el('div', 'mt-notification-dialog-body');
    var dStatus = el('p', 'mt-notification-status'); dStatus.setAttribute('role', 'status'); dStatus.setAttribute('aria-live', 'polite');
    var dEmpty = el('p', 'mt-notification-empty', 'Keine Benachrichtigungen.');
    var dList = el('div', 'mt-notification-dialog-list');
    dBody.append(dStatus, dEmpty, dList); dialog.append(dHead, dBody); document.body.append(dialog);
    function closeDialog() { if (typeof dialog.close === 'function') { dialog.close(); } else { dialog.removeAttribute('open'); button.focus(); } }
    dClose.addEventListener('click', closeDialog);
    dialog.addEventListener('close', function () { button.focus(); });
    dialog.addEventListener('click', function (e) { if (e.target === dialog) { closeDialog(); } });
    var dialogKeys = null;

    // Meldung dahin, wo der Nutzer gerade ist (Fenster offen > Status im Fenster)
    function say(text) { (dialog.open ? dStatus : status).textContent = text; }
    function meta(item) { return [formatDate(item.published_at), isRead(item) ? 'Gelesen' : 'Neu'].filter(Boolean).join(' · '); }
    function hideButton(item, cls, text) {
      var b = el('button', cls, text); b.type = 'button';
      b.setAttribute('aria-label', 'Meldung ausblenden: ' + String(item.title || ''));
      b.addEventListener('click', function (e) {
        e.preventDefault(); e.stopPropagation();
        hide(item).then(function (ok) { if (!ok) { say('Meldung konnte nicht ausgeblendet werden. Bitte erneut versuchen.'); } });
      });
      return b;
    }
    function panelItem(item) {
      var li = el('li'); li.classList.toggle('is-read', isRead(item));
      var title = el('button', 'mt-notification-item-title', String(item.title || '')); title.type = 'button'; title.setAttribute('aria-haspopup', 'dialog');
      title.addEventListener('click', function () { markRead(item); openDialog(item); });
      var text = plainText(item.body);
      var foot = el('div', 'mt-notification-foot');
      foot.append(el('small', '', meta(item)), hideButton(item, 'mt-notification-hide', 'Ausblenden'));
      li.append(title, el('span', 'mt-notification-text', text.length > 140 ? text.slice(0, 140) + ' …' : text), foot);
      return li;
    }
    function dialogItem(item) {
      var d = el('details', 'mt-notification-dialog-item'); d.dataset.key = keyOf(item); d.classList.toggle('is-read', isRead(item));
      var s = el('summary'); s.append(el('span', '', String(item.title || '')), el('small', '', meta(item)));
      var c = el('div', 'mt-notification-dialog-content'); c.append(richText(item.body));
      var actions = el('div', 'mt-notification-dialog-actions');
      var target = safeURL(item.target_url);
      if (target) { var a = el('a', '', 'Verlinkten Inhalt öffnen'); a.href = target; actions.append(a); }
      actions.append(hideButton(item, 'mt-notification-tool', 'Ausblenden'));
      c.append(actions); d.append(s, c);
      d.addEventListener('toggle', function () { if (d.open) { markRead(item); } });
      return d;
    }
    function openDialog(focusItem) {
      render(); close(false);
      if (typeof dialog.showModal === 'function') { dialog.showModal(); } else { dialog.setAttribute('open', ''); }
      var t = focusItem ? dList.querySelector('[data-key="' + CSS.escape(keyOf(focusItem)) + '"]') : null;
      if (t) { t.open = true; t.querySelector('summary').focus(); t.scrollIntoView({ block: 'nearest' }); } else { dClose.focus(); }
    }

    function render() {
      var vis = visible();
      var unread = vis.filter(function (i) { return !isRead(i); }).length;
      if (count) { count.textContent = String(unread); count.hidden = unread === 0; }
      button.setAttribute('aria-label', 'Benachrichtigungen, ' + unread + ' ungelesen');
      readAllBtn.disabled = dReadAll.disabled = busy || unread === 0;
      hideReadBtn.disabled = dHideRead.disabled = busy || vis.length === unread;
      empty.hidden = vis.length > 0;
      list.replaceChildren.apply(list, vis.slice(0, limit).map(panelItem));
      showAll.hidden = vis.length === 0;
      showAll.textContent = vis.length > limit ? 'Alle anzeigen (' + vis.length + ')' : 'Alle anzeigen';
      // Modal nur neu bauen wenn sich die Liste ändert (Fokus bleibt)
      var keys = vis.map(keyOf).join('|');
      if (keys !== dialogKeys) {
        var active = document.activeElement && dialog.contains(document.activeElement) ? document.activeElement.closest('details') : null;
        var focusKey = active ? active.dataset.key : null;
        var open = new Set(Array.from(dList.querySelectorAll('details[open]')).map(function (d) { return d.dataset.key; }));
        dList.replaceChildren.apply(dList, vis.map(function (i) { var d = dialogItem(i); d.open = open.has(d.dataset.key); return d; }));
        dialogKeys = keys;
        if (focusKey) { var f = dList.querySelector('[data-key="' + CSS.escape(focusKey) + '"] summary'); if (f) { f.focus(); } } else if (dialog.open && !dialog.contains(document.activeElement)) { dClose.focus(); }
      } else {
        vis.forEach(function (i) {
          var d = dList.querySelector('[data-key="' + CSS.escape(keyOf(i)) + '"]');
          if (d) { d.classList.toggle('is-read', isRead(i)); d.querySelector('summary small').textContent = meta(i); }
        });
      }
      dEmpty.hidden = vis.length > 0;
    }

    function onReadAll() {
      if (busy) { return; }
      busy = true; render(); say('Wird als gelesen markiert …');
      readAll().then(function (ok) { busy = false; render(); say(ok ? 'Alle Meldungen sind gelesen.' : 'Einige Meldungen konnten nicht als gelesen gespeichert werden.'); });
    }
    function onHideRead() {
      if (busy) { return; }
      busy = true; render();
      hideRead().then(function (r) { busy = false; render(); say(!r.ok ? 'Einige Meldungen konnten nicht ausgeblendet werden.' : (r.count === 1 ? '1 gelesene Meldung ausgeblendet.' : r.count + ' gelesene Meldungen ausgeblendet.')); });
    }
    readAllBtn.addEventListener('click', onReadAll); dReadAll.addEventListener('click', onReadAll);
    hideReadBtn.addEventListener('click', onHideRead); dHideRead.addEventListener('click', onHideRead);
    showAll.addEventListener('click', function () { openDialog(null); });
    render();
  });
}());
