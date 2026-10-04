<?php

$root = getenv('PLUGIN_ROOT');
if (!$root) $root = dirname(__DIR__);
require_once __DIR__ . '/release-contract.php';

$version = release_contract_plugin_version($root);
if (!release_contract_at_least($version, '1.20.1')) {
    echo "Indexation 1.20.1 contract skipped for {$version}.\n";
    exit(0);
}

$main = file_get_contents($root . '/horaires-tarifs-parc.php');
$feature = file_get_contents($root . '/includes/class-parcs-ht-calendar-semantic.php');
if (!is_string($main) || !is_string($feature)) {
    fwrite(STDERR, "Unable to read Indexation 1.20.1 sources.\n");
    exit(1);
}

release_contract_require_all($main, array(
    "Version: 1.20.1",
    "define('PARCS_HT_VERSION', '1.20.1');",
    "require_once PARCS_HT_DIR . 'includes/class-parcs-ht-calendar-semantic.php';",
    'Parcs_HT_Calendar_Semantic::init();',
), 'Indexation 1.20.1 bootstrap');

release_contract_require_all($feature, array(
    'final class Parcs_HT_Calendar_Semantic',
    "add_filter('do_shortcode_tag'",
    'Parcs_HT_Defaults::all_settings()',
    'Parcs_HT_Public_Visibility::scheduled_state',
    "'regular_periods'",
    "'exceptions'",
    "'special_periods'",
    "'public_holidays'",
    "'show_on_calendar'",
    "'show_public_marker'",
    'parc_calendrier',
    'parc_horaires_tarifs',
    'calendar-semantic',
    '<details>',
    '<time datetime=',
    'parcs_ht_calendar_semantic_enabled',
    'Détails des horaires et du calendrier',
    'Detailed opening hours and calendar',
    'Detaillierte Öffnungszeiten und Kalender',
), 'Indexation 1.20.1 semantic calendar');

release_contract_forbid($feature, array(
    'display:none',
    'visibility:hidden',
    "'@type'=>'Event'",
    '"@type"=>"Event"',
    'application/ld+json',
    'update_option(',
    'internal_label',
), 'Indexation 1.20.1 public contract');

echo "Indexation 1.20.1 contract OK.\n";
