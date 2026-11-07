/*
 * MGD Suche ergänzt Suchfelder ausschließlich um eine Erklärung. Die Suche
 * selbst bleibt bei JTL; es gibt weder Ajax-Requests noch LocalStorage noch Tracking.
 */
(function () {
  'use strict';

  var script = document.currentScript;
  if (!script) return;

  var accent = script.getAttribute('data-mgd-search-accent');
  if (accent && /^#[0-9a-f]{6}$/i.test(accent)) {
    document.documentElement.style.setProperty('--mgd-search-accent', accent);
  }
  if (script.getAttribute('data-mgd-search-field-hint') !== 'true') return;

  document.querySelectorAll('input[name="qs"]').forEach(function (input) {
    if (input.dataset.mgdSearchHintBound === 'true') return;
    input.dataset.mgdSearchHintBound = 'true';

    var hint = document.createElement('small');
    hint.className = 'mgd-search-field-hint';
    hint.textContent = 'Passende Merkmale werden auf der Ergebnisseite angezeigt.';
    input.insertAdjacentElement('afterend', hint);
  });
}());
