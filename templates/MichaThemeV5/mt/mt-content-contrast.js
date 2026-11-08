/* Eingefügte Textfarben lesbar halten. Inhalte und gespeicherte Einstellungen bleiben unverändert. */
(function () {
  'use strict';

  function rgb(value) {
    value = value.trim();
    if (/^#[0-9a-f]{3}(?:[0-9a-f]{3})?$/i.test(value)) {
      var hex = value.slice(1);
      if (hex.length === 3) { hex = hex.split('').map(function (c) { return c + c; }).join(''); }
      return [parseInt(hex.slice(0, 2), 16), parseInt(hex.slice(2, 4), 16), parseInt(hex.slice(4, 6), 16), 1];
    }
    var match = /^rgba?\(\s*([\d.]+)[, ]+([\d.]+)[, ]+([\d.]+)(?:\s*[,/]\s*([\d.]+))?\s*\)$/.exec(value);
    return match ? [Number(match[1]), Number(match[2]), Number(match[3]), match[4] === undefined ? 1 : Number(match[4])] : null;
  }
  function luminance(color) {
    return color.slice(0, 3).map(function (v) {
      v /= 255;
      return v <= 0.04045 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
    }).reduce(function (sum, v, i) { return sum + v * [0.2126, 0.7152, 0.0722][i]; }, 0);
  }
  function ratio(first, second) {
    var a = luminance(first), b = luminance(second);
    return (Math.max(a, b) + 0.05) / (Math.min(a, b) + 0.05);
  }
  function neutral(color) {
    return color && color[3] === 1 && Math.max.apply(null, color.slice(0, 3)) - Math.min.apply(null, color.slice(0, 3)) <= 16;
  }
  function selectToken(color, background, muted, text) {
    if (!neutral(color) || ratio(color, background) >= 4.5) { return null; }
    if (muted && ratio(muted, background) >= 4.5) { return 'muted'; }
    return text && ratio(text, background) >= 4.5 ? 'text' : null;
  }
  if (typeof document === 'undefined') {
    if (typeof module !== 'undefined' && module.exports) {
      module.exports = { rgb: rgb, ratio: ratio, neutral: neutral, selectToken: selectToken };
    }
    return;
  }

  // Bei Fotos ist die Hintergrundfarbe kein Kontrastnachweis. Solche Bereiche nicht automatisch umfärben.
  function backgroundFor(element) {
    var layers = [];
    for (var e = element; e; e = e.parentElement) {
      var style = getComputedStyle(e);
      if (style.backgroundImage !== 'none' || e.classList.contains('parallax-window')) { return null; }
      var color = rgb(style.backgroundColor);
      if (color) { layers.push(color); if (color[3] === 1) { break; } }
    }
    var result = [255, 255, 255, 1];
    layers.reverse().forEach(function (layer) {
      result = result.slice(0, 3).map(function (v, i) { return layer[i] * layer[3] + v * (1 - layer[3]); }).concat(1);
    });
    return result;
  }
  function refresh() {
    document.querySelectorAll('#main-wrapper [style], #content-wrapper [style]').forEach(function (element) {
      if (!element.style.color || element.isContentEditable || element.closest('a, button, svg, input, select, textarea, #opc')) { return; }
      element.classList.remove('mt-copy-contrast', 'mt-copy-contrast-muted');
      var background = backgroundFor(element);
      if (!background) { return; }
      var style = getComputedStyle(element);
      var color = rgb(style.color);
      var muted = rgb(style.getPropertyValue('--mt-text-muted'));
      var text = rgb(style.getPropertyValue('--mt-text'));
      var token = color && selectToken(color, background, muted, text);
      if (token) { element.classList.add(token === 'muted' ? 'mt-copy-contrast-muted' : 'mt-copy-contrast'); }
    });
  }
  var scheduled = false;
  function soon() {
    if (scheduled) { return; }
    scheduled = true;
    window.requestAnimationFrame(function () { scheduled = false; refresh(); });
  }
  function start() {
    refresh();
    new MutationObserver(soon).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme', 'data-accent', 'style'] });
    // Nachgeladene Inhalte prüfen; die eigenen Klassen erzeugen keine neuen Knoten.
    var content = document.querySelector('#main-wrapper, #content-wrapper');
    if (content) { new MutationObserver(soon).observe(content, { childList: true, subtree: true }); }
    var media = window.matchMedia('(prefers-color-scheme: dark)');
    if (media.addEventListener) { media.addEventListener('change', soon); }
  }
  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', start); } else { start(); }
}());
