const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const { JSDOM } = require('jsdom');

const root = process.env.PLUGIN_ROOT || path.resolve(__dirname, '..');
const plugin = fs.readFileSync(path.join(root, 'horaires-tarifs-parc.php'), 'utf8');
assert.match(plugin, /add_action\('wp_enqueue_scripts',\s*array\('Parcs_HT_Group_Quotes',\s*'assets'\),\s*30\)/, 'Le moteur devis doit être chargé assez tôt pour que le contrôle de date puisse démarrer.');
assert.match(plugin, /admin-save-guard\.js/, 'La protection indépendante de sauvegarde doit être chargée dans l’administration.');

const html = `<!doctype html><html><body>
<div class="htp-admin">
<form action="admin-post.php" method="post">
<input type="hidden" name="action" value="parcs_ht_save">
<input type="hidden" data-htp-active-tab-input value="htp-tariffs">
<section id="htp-general" class="htp-card"><input name="general" value="old"></section>
<section id="htp-tariffs" class="htp-card"><input name="tariff" value="new"></section>
<button type="submit" name="htp_save_active" value="1">Enregistrer cet onglet</button>
<button type="submit" name="htp_save_all" value="1">Tout enregistrer</button>
</form>
</div>
</body></html>`;

const dom = new JSDOM(html, { runScripts: 'outside-only', url: 'https://example.test/wp-admin/admin.php' });
const context = dom.getInternalVMContext();
const guard = fs.readFileSync(path.join(root, 'assets/admin-save-guard.js'), 'utf8');
vm.runInContext(guard, context);
dom.window.document.dispatchEvent(new dom.window.Event('DOMContentLoaded', { bubbles: true }));

const form = dom.window.document.querySelector('form');
const scoped = form.querySelector('[name="htp_save_active"]');
const event = new dom.window.SubmitEvent('submit', { bubbles: true, cancelable: true, submitter: scoped });
form.dispatchEvent(event);
assert.equal(form.querySelector('#htp-general input').disabled, true, 'Les onglets inactifs doivent être exclus du POST pour éviter la troncature PHP.');
assert.equal(form.querySelector('#htp-tariffs input').disabled, false, 'L’onglet actif doit rester envoyé et enregistrable.');

console.log('Emergency 1.9.20 regressions: OK');
