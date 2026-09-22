<?php

require_once __DIR__ . '/release-contract.php';

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$version = release_contract_plugin_version($root);
if (!release_contract_at_least($version, '1.17.7')) {
    echo "SKIP: contract 1.17.7 applies from 1.17.7.\n";
    exit(0);
}

$main = file_get_contents($root . '/horaires-tarifs-parc.php');
$context = file_get_contents($root . '/includes/class-parcs-ht-admin-year-context.php');
$quotes = file_get_contents($root . '/includes/class-parcs-ht-admin-group-quotes-1177.php');
$normalizer = file_get_contents($root . '/includes/class-parcs-ht-group-quote-state-normalizer.php');
$portal = file_get_contents($root . '/includes/class-parcs-ht-group-portal.php');

foreach (array('main'=>$main,'year context'=>$context,'quote admin'=>$quotes,'normalizer'=>$normalizer,'group portal'=>$portal) as $label=>$content) {
    if (!is_string($content)) {
        fwrite(STDERR, "Unable to read {$label}.\n");
        exit(1);
    }
}

release_contract_require_regex($main, array(
    'plugin version remains 1.17.7 or newer'=>'/Version:\s*([0-9.]+)/',
    'PARCS_HT_VERSION constant remains defined'=>"/define\\('PARCS_HT_VERSION',\\s*'[0-9.]+'\\)/",
), '1.17.7 plugin version');
if (!release_contract_at_least($version, '1.17.7')) {
    fwrite(STDERR, "1.17.7 plugin wiring: plugin version regressed.\n");
    exit(1);
}

release_contract_require_all($main, array(
    'class-parcs-ht-admin-year-context.php',
    'class-parcs-ht-admin-group-quotes-1177.php',
    'Parcs_HT_Admin_Year_Context::init();',
    'Parcs_HT_Admin_Group_Quotes_1177::init();',
    'upgrader_process_complete',
    'litespeed_purge_all',
), '1.17.7 plugin wiring');

release_contract_require_all($context, array(
    "option_' . Parcs_HT_Defaults::OPTION",
    'isset($season[\'tariffs\'])',
    '$value[\'tariffs\'] = $season[\'tariffs\'];',
    'strpos($action, \'parcs_ht_\')',
    'season_year',
), '1.17.7 exact year context');

release_contract_require_all($quotes, array(
    "const PAGE = 'parcs-ht-group-quotes-1177'",
    'Parcs_HT_Admin_Navigation::render_year_context',
    'Parcs_HT_Group_Quotes::binding_for_year',
    'Parcs_HT_Group_Quotes::season_for_year',
    'Parcs_HT_Public_Visibility::group_tariff_grid_ready',
    'get_option(Parcs_HT_Defaults::OPTION',
    '$raw[\'seasons\'][$year][\'tariffs\']',
    'parcs_ht_save_quote_binding_1177',
    'parcs_ht_save_quote_forms_1177',
    'parcs_ht_save_quote_gate_1177',
    'parcs_ht_save_quote_engine_1177',
    'Les tarifs groupes sont bien disponibles.',
), '1.17.7 quote admin');

release_contract_require_all($normalizer, array(
    'parcs_ht_group_quote_state_normalized_v2',
    'array_key_exists(\'group_quotes_enabled\', $season)',
    'Parcs_HT_Group_Quotes::STATE_OPTION',
    'update_option(Parcs_HT_Group_Quotes::STATE_OPTION',
), '1.17.7 quote state reconciliation');

release_contract_forbid($portal, array(
    'Les tarifs groupes ne sont pas disponibles pour cette année.',
    'data-group-tariff-unavailable',
    '$tariffs_unavailable',
), '1.17.7 group portal false unavailable message');

echo "OK: 1.17.7 quote admin, annual context and false-unavailable safeguards present.\n";

if (release_contract_at_least($version, '1.17.12')) {
    $cf7 = file_get_contents($root . '/includes/class-parcs-ht-cf7-form-11712.php');
    if (!is_string($cf7)) {
        fwrite(STDERR, "Unable to read CF7 1.17.12 updater.\n");
        exit(1);
    }

    release_contract_require_all($main, array(
        'class-parcs-ht-cf7-form-11712.php',
        'Parcs_HT_CF7_Form_11712::init();',
    ), '1.17.12 CF7 updater wiring');

    release_contract_require_all($cf7, array(
        "const ACTION = 'parcs_ht_apply_cf7_form_11712'",
        "const BACKUP_OPTION = 'parcs_ht_cf7_form_backup_11712'",
        'WPCF7_ContactForm::get_instance',
        "CF7CF::parse_conditions",
        "CF7CF::setConditions",
        "'ageenfants'",
        "'heurevisite'",
        "'responsablejour_nom'",
        "'responsablejour_tel'",
        "'langues'",
        "'langueautre'",
        "'moyenpaiement'",
        "'typediffere'",
        "'siretfacturation'",
        "'codeservice'",
        "'referenceengagement'",
        'show [group-scolaire] if [groupedevis] equals "Groupe"',
        'show [group-handicape] if [groupedevis] equals "Groupe en situation de handicap"',
        'show [group-langue-autre] if [langues] equals "Autre"',
        'show [group-paiement-differe] if [moyenpaiement] equals "Paiement différé"',
        'show [group-chorus] if [typediffere] equals "Chorus Pro"',
        '[group group-scolaire clear_on_hide]',
        '[group group-handicape clear_on_hide]',
        '[group group-langue-autre clear_on_hide]',
        '[group group-paiement-differe clear_on_hide]',
        '[group group-chorus clear_on_hide]',
        'Le PDF existant n’a pas été modifié.',
    ), '1.17.12 controlled CF7 form update');

    echo "OK: 1.17.12 controlled CF7 form updater and conditional rules present.\n";
}

if (release_contract_at_least($version, '1.17.8')) {
    require_once __DIR__ . '/admin-guides-1178-contract.php';
}
