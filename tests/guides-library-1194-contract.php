<?php

require_once __DIR__ . '/release-contract.php';

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$version = release_contract_plugin_version($root);
if (!release_contract_at_least($version, '1.19.4')) {
    echo "SKIP: contract 1.19.4 applies from 1.19.4.\n";
    exit(0);
}

$guides = file_get_contents($root . '/includes/class-parcs-ht-pedagogical-guides.php');
$stats = file_get_contents($root . '/includes/class-parcs-ht-guide-stats.php');
$integrity = file_get_contents($root . '/includes/class-parcs-ht-save-integrity.php');
$admin_guides = file_get_contents($root . '/includes/class-parcs-ht-admin-guides-1178.php');
$navigation = file_get_contents($root . '/includes/class-parcs-ht-admin-navigation.php');
$admin = file_get_contents($root . '/includes/class-parcs-ht-admin.php');
$general = file_get_contents($root . '/includes/class-parcs-ht-admin-general.php');
$quotes = file_get_contents($root . '/includes/class-parcs-ht-admin-group-quotes-1177.php');

foreach (array(
    'guides'=>$guides,
    'stats'=>$stats,
    'integrity'=>$integrity,
    'admin guides'=>$admin_guides,
    'navigation'=>$navigation,
    'admin'=>$admin,
    'general'=>$general,
    'quotes'=>$quotes,
) as $label=>$source) {
    if (!is_string($source)) {
        fwrite(STDERR, "Unable to read {$label}.\n");
        exit(1);
    }
}

release_contract_require_all($guides, array(
    'const STORE_VERSION = 4',
    "'library'",
    "'years'",
    'persist_admin_value',
    'canonical_id_map',
    'elseif ($year_is_configured)',
    '$library[\'guides\'][$index][\'enabled\'] = \'0\'',
), '1.19.4 permanent guide library');

if (strpos($guides, "add_action('admin_menu'") !== false || strpos($guides, "add_action('admin_footer'") !== false) {
    fwrite(STDERR, "Historical embedded guide admin is registered again.\n");
    exit(1);
}
echo "[OK] Historical embedded guide admin stays disabled.\n";

release_contract_require_all($stats, array(
    'return is_array($library) ? $library : array(\'guides\'=>array());',
    'canonical_id_map()',
    "'canonical_id'",
    'season_year',
), '1.19.4 guide analytics continuity');

release_contract_require_all($integrity, array(
    'Parcs_HT_Pedagogical_Guides::persist_admin_value',
    "Parcs_HT_Admin_Guides_1178::PAGE",
), '1.19.4 verified guide save path');

release_contract_require_all($admin_guides, array(
    'Bibliothèque permanente',
    'Les documents sont communs à toutes les années',
    'Les statistiques restent historisées par année',
), '1.19.4 guide admin wording');

release_contract_require_all($navigation, array(
    "array('Parcs_HT_Admin', 'preview_page')",
    "array('Parcs_HT_Admin', 'shortcodes_page')",
    "Parcs_HT_Admin_Guides_1178::PAGE",
    "Parcs_HT_Admin_Group_Quotes_1177::PAGE",
    "Parcs_HT_Admin_Communication_1179::POPUP_PAGE",
    "Parcs_HT_Admin_Communication_1179::ADVENT_PAGE",
    'if ($page === Parcs_HT_Admin::PAGE && $tab !== \'\')',
), '1.19.4 canonical admin routing');

if (strpos($navigation, "'parcs-ht-preview'    => 'htp-preview'") !== false || strpos($navigation, "'parcs-ht-shortcodes' => 'htp-shortcodes'") !== false) {
    fwrite(STDERR, "Preview or Shortcodes still uses a legacy bridge.\n");
    exit(1);
}
echo "[OK] Preview and Shortcodes no longer use legacy bridges.\n";

release_contract_require_all($admin, array(
    'public static function preview_page()',
    'public static function shortcodes_page()',
), '1.19.4 dedicated utility admin screens');

if (strpos($general, 'Ouvrir les réglages détaillés historiques') !== false) {
    fwrite(STDERR, "Historical general-admin escape link is present again.\n");
    exit(1);
}
if (strpos($quotes, 'legacy_quote') !== false) {
    fwrite(STDERR, "Legacy quote bypass is present again.\n");
    exit(1);
}

echo "OK: 1.19.4 permanent guides and canonical admin routes.\n";
