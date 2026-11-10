/*
 * MGD Suche setzt hier nur die Akzentfarbe. Am Suchfeld selbst wird nichts eingefügt,
 * das Suchformular gehört dem Theme. Kein Ajax, kein LocalStorage, kein Tracking.
 */
(function () {
  'use strict';

  var script = document.currentScript;
  if (!script) return;

  var accent = script.getAttribute('data-mgd-search-accent');
  if (accent && /^#[0-9a-f]{6}$/i.test(accent)) {
    document.documentElement.style.setProperty('--mgd-search-accent', accent);
  }
}());
