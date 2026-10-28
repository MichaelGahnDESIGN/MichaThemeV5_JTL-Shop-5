/* Glocke: Panel auf/zu, Escape + Klick daneben schliesst */
(function () {
  'use strict';
  document.querySelectorAll('[data-mt-notification-center]').forEach(function (root) {
    var button = root.querySelector('button');
    var panel = root.querySelector('section');
    if (!button || !panel) { return; }
    function close(focusBack) {
      panel.hidden = true;
      button.setAttribute('aria-expanded', 'false');
      if (focusBack) { button.focus(); }
    }
    button.addEventListener('click', function () {
      var open = button.getAttribute('aria-expanded') !== 'true';
      button.setAttribute('aria-expanded', String(open));
      panel.hidden = !open;
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !panel.hidden) { close(true); }
    });
    document.addEventListener('click', function (e) {
      if (!panel.hidden && !root.contains(e.target)) { close(false); }
    });
  });
}());
