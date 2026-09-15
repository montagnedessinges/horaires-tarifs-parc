const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const {execFileSync} = require('node:child_process');
const assert = require('node:assert/strict');
const {JSDOM} = require('jsdom');
const root = process.env.PLUGIN_ROOT || path.resolve(__dirname, '..');
const temp = fs.mkdtempSync(path.join(process.cwd(), '.htp-ui-'));
try {
  const file = path.join(temp, 'render.html');
  const log = execFileSync('php', [path.join(__dirname, 'visibility-11512-runtime.php')], {env: {...process.env, HTP_RENDER_PATH: file}, encoding: 'utf8'});
  assert(!/Fatal error|FAIL:/.test(log), log);
  const html = fs.readFileSync(file, 'utf8');
  const dom = new JSDOM(html + html.replaceAll('test-', 'second-'), {runScripts: 'outside-only'});
  const w = dom.window;
  w.fetch = () => { throw new Error('Year switches must not request a page'); };
  w.eval(fs.readFileSync(path.join(root, 'assets/stability-11511.js'), 'utf8'));
  const roots = w.document.querySelectorAll('[data-htp-tariff-years]');
  for (let i = 0; i < 20; i++) {
    roots[0].querySelector('[data-htp-retail-year="2027"]').click();
    assert.equal(roots[0].querySelector('[data-htp-year-panel="2027"]').hidden, false);
    roots[0].querySelector('[data-htp-retail-year="2026"]').click();
    assert.equal(roots[0].querySelector('[data-htp-year-panel="2026"]').hidden, false);
  }
  assert.equal(roots[1].querySelector('[data-htp-year-panel="2026"]').hidden, false);
  const first = roots[0].querySelector('[data-htp-retail-year="2026"]');
  first.dispatchEvent(new w.KeyboardEvent('keydown', {key: 'ArrowRight', bubbles: true}));
  assert.equal(w.document.activeElement.dataset.htpRetailYear, '2027');
  assert.equal(roots[0].querySelectorAll('[data-htp-retail-year][aria-selected="true"]').length, 1);
  assert.equal(roots[0].querySelector('[data-htp-year-panel="2026"] [data-htp-channel="online"]'), null);
  assert.equal(roots[0].querySelector('[data-htp-year-panel="2026"] [data-htp-channel="onsite"] b').textContent, '12 €');
  dom.window.close();
  console.log('PASS: real PHP markup; 40 year switches, keyboard, isolated instances, 2026 onsite only, zero requests');
} finally { fs.rmSync(temp, {recursive: true, force: true}); }
