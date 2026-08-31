const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');

const root = process.env.PLUGIN_ROOT || path.resolve(__dirname, '..');
const plugin = fs.readFileSync(path.join(root, 'horaires-tarifs-parc.php'), 'utf8');
const saveLayer = fs.readFileSync(path.join(root, 'includes/class-parcs-ht-quote-page-save.php'), 'utf8');

assert.match(plugin, /class-parcs-ht-quote-page-save\.php/, 'La couche de sauvegarde du devis doit être chargée.');
assert.match(plugin, /Parcs_HT_Quote_Page_Save::init\(\)/, 'La couche de sauvegarde du devis doit être initialisée.');
assert.match(saveLayer, /\['_complete'\]/, 'La suppression ne doit être interprétée que lorsque l’onglet Devis est complet.');
assert.match(saveLayer, /\['quote_page'\]/, 'La suppression doit être limitée à l’onglet Devis.');

['important_messages', 'quick_links', 'info_blocks', 'accordions'].forEach((list) => {
  assert.ok(saveLayer.includes(`'${list}'`), `La liste ${list} doit accepter une suppression complète.`);
});

assert.match(
  saveLayer,
  /if \(!array_key_exists\(\$list, \$posted_quote\)\) \$new_value\['quote_page'\]\[\$list\] = array\(\);/,
  'Une liste absente du POST complet doit être réellement vidée au lieu d’être restaurée depuis l’ancienne valeur.'
);

console.log('Quote page deletion regressions: OK');
