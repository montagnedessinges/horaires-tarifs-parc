const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const { JSDOM } = require('jsdom');

const root = process.env.PLUGIN_ROOT || path.resolve(__dirname, '..');
const bootstrap = fs.readFileSync(path.join(root, 'includes/class-parcs-ht-bootstrap.php'), 'utf8');
const shortcodes = fs.readFileSync(path.join(root, 'includes/class-parcs-ht-shortcodes.php'), 'utf8');
assert.match(bootstrap, /add_action\('wp_enqueue_scripts',\s*array\(__CLASS__,\s*'maybe_preload_assets'\),\s*20\)/, 'Le bootstrap doit précharger assez tôt les ressources nécessaires aux shortcodes.');
assert.match(bootstrap, /Parcs_HT_Shortcodes::maybe_enqueue_assets\(\)/, 'Le préchargement doit déléguer au moteur de shortcodes.');
assert.match(shortcodes, /Parcs_HT_Group_Quotes::assets\(\)/, 'Le moteur de shortcodes doit conserver le chargement des ressources du devis.');
assert.match(shortcodes, /admin-save-guard\.js/, 'La protection indépendante de sauvegarde doit être chargée dans l’administration.');

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
