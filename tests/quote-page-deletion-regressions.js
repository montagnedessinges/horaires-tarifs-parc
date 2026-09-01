const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');

const root = process.env.PLUGIN_ROOT || path.resolve(__dirname, '..');
const plugin = fs.readFileSync(path.join(root, 'horaires-tarifs-parc.php'), 'utf8');
const saveLayer = fs.readFileSync(path.join(root, 'includes/class-parcs-ht-quote-page-save.php'), 'utf8');

const versionMatch = plugin.match(/Version:\s*([0-9]+(?:\.[0-9]+)+)/);
assert.ok(versionMatch, 'La version du plugin doit être détectable pour appliquer le bon contrat de release.');
const pluginVersion = versionMatch[1];

function versionAtLeast(current, minimum) {
  const a = current.split('.').map(Number);
  const b = minimum.split('.').map(Number);
  const length = Math.max(a.length, b.length);
  for (let i = 0; i < length; i += 1) {
    const av = a[i] || 0;
    const bv = b[i] || 0;
    if (av > bv) return true;
    if (av < bv) return false;
  }
  return true;
}

assert.match(plugin, /class-parcs-ht-quote-page-save\.php/, 'La couche de sauvegarde du devis doit être chargée.');
assert.match(plugin, /Parcs_HT_Quote_Page_Save::init\(\)/, 'La couche de sauvegarde du devis doit être initialisée.');
assert.match(saveLayer, /\['_complete'\]/, 'La suppression ne doit être interprétée que lorsque l’onglet Devis est complet.');
assert.match(saveLayer, /\['quote_page'\]/, 'La suppression doit être limitée à l’onglet Devis.');

['important_messages', 'quick_links', 'info_blocks', 'accordions'].forEach((list) => {
  assert.ok(saveLayer.includes(`'${list}'`), `La liste ${list} doit accepter une suppression complète.`);
});

if (versionAtLeast(pluginVersion, '1.10.0')) {
  // Contrat 1.10.0+ : les données POST sont d'abord vérifiées puis déslashées,
  // la suppression est appliquée sur une copie locale et réinjectée via wp_slash().
  assert.match(
    saveLayer,
    /private\s+static\s+function\s+is_main_admin_save\s*\(\s*\)[\s\S]*?wp_verify_nonce\s*\(/,
    'Depuis 1.10.0, la sauvegarde doit être protégée par contrôle des droits et nonce.'
  );
  assert.match(
    saveLayer,
    /private\s+static\s+function\s+posted_settings\s*\(\s*\)[\s\S]*?wp_unslash\s*\(\s*\$_POST\['settings'\]\s*\)/,
    'Depuis 1.10.0, les réglages POST doivent être déslashés avant traitement.'
  );
  assert.match(
    saveLayer,
    /if\s*\(\s*!array_key_exists\(\$list,\s*\$quote_page\)\s*\)\s*\{[\s\S]*?\$quote_page\[\$list\]\s*=\s*null\s*;/,
    'Une liste absente doit être explicitement marquée comme supprimée avant la fusion.'
  );
  assert.match(
    saveLayer,
    /\$posted_settings\['quote_page'\]\s*=\s*\$quote_page\s*;[\s\S]*?\$_POST\['settings'\]\s*=\s*wp_slash\s*\(\s*\$posted_settings\s*\)\s*;/,
    'Le POST normalisé doit être réinjecté dans le format attendu par WordPress.'
  );
} else {
  // Contrat historique avant 1.10.0.
  assert.match(
    saveLayer,
    /if\s*\(\s*!array_key_exists\(\$list,\s*\$_POST\['settings'\]\['quote_page'\]\)\s*\)\s*\{[\s\S]*?\$_POST\['settings'\]\['quote_page'\]\[\$list\]\s*=\s*null\s*;/,
    'Avant 1.10.0, une liste absente devait être marquée directement dans le POST.'
  );
}

// Invariant commun à toutes les versions : après sanitization/merge,
// une liste absente, null ou invalide doit réellement devenir un tableau vide.
assert.match(
  saveLayer,
  /if\s*\(\s*!array_key_exists\(\$list,\s*\$posted_quote\)\s*\|\|\s*\$posted_quote\[\$list\]\s*===\s*null\s*\|\|\s*!is_array\(\$posted_quote\[\$list\]\)\s*\)\s*\{[\s\S]*?\$new_value\['quote_page'\]\[\$list\]\s*=\s*array\(\)\s*;/,
  'Une liste supprimée, absente ou invalide doit rester vide après la fusion des réglages.'
);

console.log(`Quote page deletion regressions: OK for ${pluginVersion}`);
