<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$registry = file_get_contents($root . '/includes/class-parcs-ht-shortcode-registry.php');
$admin = file_get_contents($root . '/includes/class-parcs-ht-admin.php');
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

foreach ($required as $shortcode) {
    shortcode_registry_check(strpos($registry, "'" . $shortcode . "'") !== false, 'runtime registry contains ' . $shortcode);
    foreach (array('', '_fr', '_en', '_de') as $suffix) {
        $literal = '[' . $shortcode . $suffix . ']';
        shortcode_registry_check(strpos($admin, $literal) !== false, 'static Shortcodes page contains ' . $literal);
    }
}

shortcode_registry_check(strpos($admin, '<th>Automatique</th>') !== false, 'static Shortcodes page has the automatic-language column');
shortcode_registry_check(strpos($admin, 'Parcs_HT_Shortcode_Registry::public_rows()') === false, 'Shortcodes page does not depend on the runtime registry');
shortcode_registry_check(strpos($main, 'class-parcs-ht-feature-hub.php') === false && strpos($main, 'Parcs_HT_Feature_Hub::init()') === false, 'legacy Shortcodes overlay is no longer loaded');
shortcode_registry_check(!file_exists($root . '/includes/class-parcs-ht-feature-hub.php'), 'legacy Shortcodes overlay file is removed');
shortcode_registry_check(strpos($preview, 'Parcs_HT_Shortcode_Registry::public_rows()') !== false && strpos($preview, 'Parcs_HT_Shortcode_Registry::render_preview') !== false, 'Aperçu remains generated from the runtime registry and real renderers');
shortcode_registry_check(strpos($preview_js, "['fr','en','de']") !== false && strpos($preview_js, 'data-htp-preview-lang') !== false, 'selected preview can switch between FR EN DE');
shortcode_registry_check(strpos($preview_js, 'frame.src=frameUrl()') !== false, 'Aperçu loads only the selected shortcode and language on demand');
shortcode_registry_check(strpos($bootstrap, 'Parcs_HT_Shortcode_Registry::definitions()') !== false, 'runtime bootstrap still derives from the central registry');

echo "Static Shortcodes page contract: OK\n";
