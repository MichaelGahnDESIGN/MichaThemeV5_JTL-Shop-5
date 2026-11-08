const test = require('node:test');
const assert = require('node:assert/strict');
const {rgb, ratio, neutral, selectToken} = require('../templates/MichaThemeV5/mt/mt-content-contrast.js');

test('Schwarz und Weiß ergeben den Referenzkontrast 21:1', () => {
  assert.equal(ratio([0, 0, 0], [255, 255, 255]), 21);
});

test('Die eingefügte Word-Farbe wird auf dunklem Grund lesbar ersetzt', () => {
  const background = [28, 29, 32];
  const muted = [180, 184, 190];
  assert.ok(ratio([118, 113, 113], background) < 4.5);
  assert.equal(selectToken([118, 113, 113, 1], background, muted, [236, 236, 238]), 'muted');
  assert.ok(ratio(muted, background) >= 4.5);
});

test('Ausreichend dunkler Text bleibt im Hellmodus unverändert', () => {
  assert.equal(selectToken([22, 24, 28, 1], [245, 246, 247], [70, 76, 84], [22, 24, 28]), null);
});

test('Weißer Text auf heller Fläche bekommt den passenden Theme-Text', () => {
  assert.equal(selectToken([255, 255, 255, 1], [255, 255, 255], [70, 76, 84], [22, 24, 28]), 'muted');
});

test('Bewusste Akzentfarben und halbtransparente Texte bleiben erhalten', () => {
  assert.equal(neutral([200, 22, 26, 1]), false);
  assert.equal(neutral([118, 113, 113, 0.5]), false);
  assert.equal(selectToken([255, 180, 0, 1], [255, 255, 255], [70, 76, 84], [22, 24, 28]), null);
});

test('Ungeeignete eigene Theme-Farben werden nicht als Korrektur verwendet', () => {
  assert.equal(selectToken([118, 113, 113, 1], [28, 29, 32], [40, 40, 40], [50, 50, 50]), null);
  assert.equal(selectToken([118, 113, 113, 1], [28, 29, 32], [40, 40, 40], [236, 236, 238]), 'text');
});

test('Browserfarbwerte einschließlich CSS-4-Alpha werden sicher gelesen', () => {
  assert.deepEqual(rgb('rgb(118, 113, 113)'), [118, 113, 113, 1]);
  assert.deepEqual(rgb('rgb(118 113 113 / 0.5)'), [118, 113, 113, 0.5]);
  assert.deepEqual(rgb('#767171'), [118, 113, 113, 1]);
  assert.deepEqual(rgb(' #fff '), [255, 255, 255, 1]);
  assert.equal(rgb('color(display-p3 1 0 0)'), null);
});
