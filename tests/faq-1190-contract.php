<?php

$root = getenv('PLUGIN_ROOT');
if (!$root) $root = dirname(__DIR__);
require_once __DIR__ . '/release-contract.php';

$feature_path = $root . '/includes/class-parcs-ht-faq.php';
if (!is_file($feature_path)) {
    echo "FAQ 1.19.0 contract skipped: feature file absent.\n";
    exit(1);
}

$feature = file_get_contents($feature_path);
$bootstrap = file_get_contents($root . '/includes/class-parcs-ht-bootstrap.php');
$registry = file_get_contents($root . '/includes/class-parcs-ht-shortcode-registry.php');
$uninstall = file_get_contents($root . '/uninstall.php');
$script = file_get_contents($root . '/assets/faq-google-sheet-apps-script.txt');
if (!is_string($feature) || !is_string($bootstrap) || !is_string($registry) || !is_string($uninstall) || !is_string($script)) {
    fwrite(STDERR, "Unable to read FAQ 1.19.0 sources.\n");
    exit(1);
}

release_contract_require_all($feature, array(
    "const OPTION = 'parcs_ht_faq';",
    "const REVISIONS_OPTION = 'parcs_ht_faq_revisions';",
    "const PAGE = 'parcs-ht-faq';",
    "add_shortcode('parc_faq'",
    "add_shortcode('parc_faq_' . \$language",
    "admin_post_parcs_ht_faq_save_settings",
    "admin_post_parcs_ht_faq_check_google",
    "admin_post_parcs_ht_faq_apply_import",
    "admin_post_parcs_ht_faq_restore_revision",
    "check_admin_referer('parcs_ht_faq_save_settings')",
    "check_admin_referer('parcs_ht_faq_check_google')",
    "check_admin_referer('parcs_ht_faq_apply_import')",
    "wp_safe_remote_post",
    "do_action('litespeed_purge_all')",
    "add_revision(\$settings, 'Avant import Google Sheet')",
    "'has_import'=>'0'",
    "'enabled'=>'0'",
    "'show_search'=>'1'",
    "'show_categories'=>'1'",
    'Aucune suppression automatique',
    'Vérifier le Google Sheet',
    '[parc_faq_fr]',
    '[parc_faq_en]',
    '[parc_faq_de]',
    'data-htp-faq-item',
    '<details class=',
), 'FAQ feature');

release_contract_forbid($feature, array(
    "update_option(Parcs_HT_Defaults::OPTION",
    "update_option('parcs_ht_settings'",
    'wp_ajax_',
    'display:none',
    'visibility:hidden',
), 'FAQ isolation/public contract');

release_contract_require_all($bootstrap, array(
    "require_once PARCS_HT_DIR . 'includes/class-parcs-ht-faq.php';",
    'Parcs_HT_FAQ::init();',
), 'FAQ bootstrap');

release_contract_require_all($registry, array(
    "'parc_faq' => array('label'=>'FAQ','kind'=>'faq'",
    "\$definition['kind'] === 'faq'",
    'Parcs_HT_FAQ::render',
), 'FAQ shortcode registry');

release_contract_require_all($uninstall, array('parcs_ht_faq', 'parcs_ht_faq_revisions'), 'FAQ uninstall');

release_contract_require_all($script, array(
    'function installerConfiguration()',
    'function doPost(event)',
    "FAQ_ALLOWED_TABS = ['Montagne des Singes', 'Forêt des Singes']",
    "properties.getProperty('FAQ_SHARED_SECRET')",
    'SpreadsheetApp.openById',
    'getDataRange().getDisplayValues()',
), 'FAQ Google Sheet bridge');

release_contract_forbid($script, array(
    'setValue(',
    'setValues(',
    'appendRow(',
    'deleteRow(',
    'insertRow',
), 'FAQ Google Sheet bridge read-only contract');

echo "FAQ 1.19.0 contract OK.\n";
