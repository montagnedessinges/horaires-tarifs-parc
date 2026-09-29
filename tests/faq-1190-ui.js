'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require('jsdom');
const root = process.env.PLUGIN_ROOT || path.join(__dirname, '..');
async function testCopy(mode) {
    const dom = new JSDOM('<main class="htp-faq-admin"><button data-faq-copy="script">Copier</button><textarea id="script">Read-only source</textarea><p data-faq-copy-status></p></main>', {runScripts:'outside-only'});
    const w = dom.window;
    let copied = '';
    if (mode !== 'missing') Object.defineProperty(w.navigator, 'clipboard', {value:{writeText: async value => {if (mode === 'denied') throw Error('denied'); copied = value;}}});
    w.eval(fs.readFileSync(path.join(root,'assets/faq-admin.js'),'utf8'));
    w.document.querySelector('button').click();
    await new Promise(resolve=>setImmediate(resolve));
    const field = w.document.querySelector('textarea');
    if (mode === 'ok') { assert.equal(copied,field.value); assert.match(w.document.querySelector('p').textContent,/Copié/); }
    else { assert.equal(w.document.activeElement,field); assert.equal(field.selectionEnd,field.value.length); assert.match(w.document.querySelector('p').textContent,/Ctrl\+C/); }
    w.close();
}
(async()=>{for(const mode of ['ok','denied','missing']) await testCopy(mode); console.log('FAQ copy UI OK: clipboard success and manual fallback.');})().catch(error=>{console.error(error);process.exitCode=1;});
