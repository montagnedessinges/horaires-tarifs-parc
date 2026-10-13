'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { JSDOM } = require('jsdom');
const jquery = require('jquery');

const root = process.env.PLUGIN_ROOT || path.resolve(__dirname, '..');
const script = fs.readFileSync(path.join(root, 'assets/popup-admin-1200.js'), 'utf8');
const css = fs.readFileSync(path.join(root, 'assets/popup-admin-1200.css'), 'utf8');
assert.match(css, /\.htp-1200-card\.is-template\s*\{\s*display\s*:\s*none/);

const dom = new JSDOM(`<!doctype html><html><body>
  <button type="button" data-popup1200-add>Ajouter un pop-up</button>
  <div data-popup1200-list></div>
  <template data-popup1200-template>
    <article class="htp-1200-card is-template" data-popup1200-row data-popup1200-index="__INDEX__">
      <input type="hidden" name="popups[__INDEX__][id]" value="" data-popup1200-id>
      <div class="htp-1200-summary"><strong data-popup1200-summary>Nouveau pop-up</strong><span></span></div>
      <input type="text" name="popups[__INDEX__][internal_name]" data-popup1200-name>
      <input type="checkbox" data-popup1200-enabled>
      <select data-popup1200-published><option value="0">Brouillon</option></select>
      <button type="button" data-popup1200-duplicate>Dupliquer</button>
    </article>
  </template>
</body></html>`, { runScripts: 'outside-only', url: 'https://example.test/wp-admin/admin.php?page=parcs-ht-popup-1179' });
const { window } = dom;
const $ = jquery(window);
window.jQuery = $;
window.eval(script);

$('[data-popup1200-add]').trigger('click');
let rows = $('[data-popup1200-list] [data-popup1200-row]');
assert.equal(rows.length, 1, 'Clicking add should insert a popup');
assert.equal(rows.first().hasClass('is-template'), false, 'New popup should not be hidden as a template');
const index = rows.first().attr('data-popup1200-index');
assert.notEqual(index, '__INDEX__');
assert.equal(rows.first().find('[data-popup1200-name]').attr('name'), 'popups[' + index + '][internal_name]');

rows.first().find('[data-popup1200-duplicate]').trigger('click');
rows = $('[data-popup1200-list] [data-popup1200-row]');
assert.equal(rows.length, 2, 'Duplicate should insert a second popup');
assert.equal(rows.last().hasClass('is-template'), false, 'Duplicate should be visible too');
assert.notEqual(rows.first().attr('data-popup1200-index'), rows.last().attr('data-popup1200-index'));

dom.window.close();
console.log('OK: adding and duplicating a popup creates visible form rows.');
