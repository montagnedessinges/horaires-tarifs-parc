<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$registry = file_get_contents($root . '/includes/class-parcs-ht-shortcode-registry.php');
$hub = file_get_contents($root . '/includes/class-parcs-ht-feature-hub.php');
$preview = file_get_contents($root . '/includes/class-parcs-ht-admin-shortcode-preview.php');
$preview_js = file_get_contents($root . '/assets/admin-shortcode-preview.js');
$bootstrap = file_get_contents($root . '/includes/class-parcs-ht-bootstrap.php');
$main = file_get_contents($root . '/horaires-tarifs-parc.php');

function shortcode_registry_check($condition, $message) {
    if (!$condition) { fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL); exit(1); }
    echo '[OK] ' . $message . PHP_EOL;
}

$required = array(
    'parc_horaires_tarifs','parc_horaires_aujourdhui','parc_calendrier','parc_tableau_tarifs','parc_tarifs_groupes',
    'parc_fermeture_exceptionnelle','parc_horaire','parc_statut','parc_horaire_accueil','parc_devis_groupe','parc_devis','parc_guides_pedagogiques',
);
foreach ($required as $shortcode) shortcode_registry_check(strpos($registry, "'" . $shortcode . "'") !== false, 'registry contains ' . $shortcode);
shortcode_registry_check(strpos($registry, "array('fr','en','de')") !== false, 'registry exposes FR EN DE variants');
shortcode_registry_check(strpos($registry, "'shortcodes'=>array('auto'=>self::shortcode($base))") !== false, 'registry exposes the automatic-language shortcode for every module');
shortcode_registry_check(strpos($hub, 'Parcs_HT_Shortcode_Registry::public_rows()') !== false, 'Shortcodes tab is generated from registry');
shortcode_registry_check(strpos($hub, "['auto','fr','en','de']") !== false, 'Shortcodes tab lists automatic, FR, EN and DE variants');
shortcode_registry_check(strpos($hub, '<th>Automatique</th>') !== false, 'Shortcodes tab labels the automatic-language column');
shortcode_registry_check(strpos($preview, 'Parcs_HT_Shortcode_Registry::public_rows()') !== false && strpos($preview, 'Parcs_HT_Shortcode_Registry::render_preview') !== false, 'Aperçu is generated from the same registry and real renderers');
shortcode_registry_check(strpos($preview_js, "['fr','en','de']") !== false && strpos($preview_js, 'data-htp-preview-lang-button') !== false, 'each preview can switch between FR EN DE');
shortcode_registry_check(strpos($bootstrap, 'Parcs_HT_Shortcode_Registry::definitions()') !== false, 'core runtime bootstrap derives from central registry');
shortcode_registry_check(strpos($main, 'admin-shortcodes-guides.js') === false, 'legacy guide shortcode injection is no longer enqueued');
shortcode_registry_check(strpos($hub, "['Tarifs groupes uniquement'") === false, 'Feature Hub no longer maintains a second hardcoded shortcode list');

echo "Shortcode registry contract: OK\n";
