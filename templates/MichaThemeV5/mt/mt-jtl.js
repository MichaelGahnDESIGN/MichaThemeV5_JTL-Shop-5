/* MichaThemeV5 JTL: kleiner Kleber zwischen NOVA (Ajax) und der Icon-Leiste. Hand gepflegt. */
(function () {
  'use strict';

  // Merkliste: NOVA tauscht den Zähler per Ajax > Icon nur sichtbar mit Einträgen, aria-label nachziehen
  function wishSync() {
    var item = document.querySelector('[data-mt-wish-item]');
    var badge = document.getElementById('badge-wl-count');
    if (!item || !badge) { return; }
    var n = parseInt(badge.textContent, 10) || 0;
    var hidden = n === 0 || badge.classList.contains('d-none');
    item.classList.toggle('mt-hdr-item--empty', hidden);
    var link = item.querySelector('a, button');
    if (link) {
      var name = item.getAttribute('data-mt-wish-name') || '';
      var unit = item.getAttribute('data-mt-wish-unit') || '';
      var unitOne = item.getAttribute('data-mt-wish-unit-one') || unit;
      // Einzahl bei genau 1
      link.setAttribute('aria-label', name + ', ' + n + ' ' + (n === 1 ? unitOne : unit));
    }
  }

  // nachgeladene Produktkarten (Filter, Endlso-Scroll) > Herzen neu abgleichen
  var favTimer = null;
  function favSoon() {
    if (favTimer) { return; }
    favTimer = window.setTimeout(function () {
      favTimer = null;
      document.dispatchEvent(new Event('mt:favs-sync'));
    }, 50);
  }

  function start() {
    wishSync();
    var badge = document.getElementById('badge-wl-count');
    if (badge && 'MutationObserver' in window) {
      new MutationObserver(wishSync).observe(badge, { attributes: true, childList: true, characterData: true, subtree: true });
    }
    if ('MutationObserver' in window && document.body) {
      new MutationObserver(function (list) {
        for (var i = 0; i < list.length; i++) {
          var added = list[i].addedNodes;
          for (var j = 0; j < added.length; j++) {
            var node = added[j];
            if (node.nodeType === 1 && (node.matches('[data-mt-fav-toggle]') || node.querySelector('[data-mt-fav-toggle]'))) { favSoon(); return; }
          }
        }
      }).observe(document.body, { childList: true, subtree: true });
    }
  }
  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', start); } else { start(); }
}());
