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

// Le comportement actuel passe par deux protections complémentaires :
// 1. une liste absente du POST complet est explicitement marquée comme supprimée ;
// 2. après sanitization/merge, toute valeur absente, null ou non-tableau est forcée à [].
// On vérifie le comportement attendu sans dépendre de la mise en forme exacte d'une seule ligne PHP.
assert.match(
  saveLayer,
  /if\s*\(\s*!array_key_exists\(\$list,\s*\$_POST\['settings'\]\['quote_page'\]\)\s*\)\s*\{[\s\S]*?\$_POST\['settings'\]\['quote_page'\]\[\$list\]\s*=\s*null\s*;/,
  'Une liste absente du POST complet doit être explicitement marquée comme supprimée avant la fusion des réglages.'
);

assert.match(
  saveLayer,
  /if\s*\(\s*!array_key_exists\(\$list,\s*\$posted_quote\)\s*\|\|\s*\$posted_quote\[\$list\]\s*===\s*null\s*\|\|\s*!is_array\(\$posted_quote\[\$list\]\)\s*\)\s*\{[\s\S]*?\$new_value\['quote_page'\]\[\$list\]\s*=\s*array\(\)\s*;/,
  'Une liste supprimée, absente ou invalide doit être réellement vidée après la fusion au lieu d’être restaurée depuis l’ancienne valeur.'
);

console.log('Quote page deletion regressions: OK');
