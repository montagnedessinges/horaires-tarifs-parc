<?php

$root = getenv('PLUGIN_ROOT');
if (!$root) $root = dirname(__DIR__);
require_once $root . '/tests/release-contract.php';

$version = release_contract_plugin_version($root);
if (!release_contract_at_least($version, '1.18.0')) {
    echo "AI / Google 1.18.0 contract skipped for {$version}.\n";
    exit(0);
}

$main = file_get_contents($root . '/horaires-tarifs-parc.php');
$feature = file_get_contents($root . '/includes/class-parcs-ht-ai-google.php');
$uninstall = file_get_contents($root . '/uninstall.php');
if (!is_string($main) || !is_string($feature) || !is_string($uninstall)) {
    fwrite(STDERR, "Unable to read AI / Google 1.18.0 sources.\n");
    exit(1);
}

release_contract_require_all($main, array(
    "require_once PARCS_HT_DIR . 'includes/class-parcs-ht-ai-google.php';",
    'Parcs_HT_AI_Google::init();',
), 'AI / Google bootstrap');

release_contract_require_all($feature, array(
    "const OPTION = 'parcs_ht_ai_google';",
    "'schema_enabled'=>'1'",
    "'rules_visible'=>'1'",
    "'category'=>'visit_rules'",
    'Règles de visite',
    'popcorn',
    'TouristAttraction',
    'LocalBusiness',
    'OpeningHoursSpecification',
    'openingHoursSpecification',
    'specialOpeningHoursSpecification',
    'validFrom',
    'validThrough',
    "add_action('wp_head'",
    "add_filter('do_shortcode_tag'",
    'wp_json_encode',
    'SEOPRESS_PRO_VERSION',
    "apply_filters('parcs_ht_ai_google_emit_schema'",
    "do_action('litespeed_purge_all')",
    '<details>',
), 'AI / Google feature');

release_contract_forbid($feature, array(
    "'@type'=>'FAQPage'",
    'display:none',
    'visibility:hidden',
    'wp_ajax_',
), 'AI / Google public contract');

release_contract_require_all($uninstall, array('parcs_ht_ai_google'), 'AI / Google uninstall');

echo "AI / Google 1.18.0 contract OK.\n";
