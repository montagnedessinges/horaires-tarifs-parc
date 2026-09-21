<?php

require_once __DIR__ . '/release-contract.php';

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$version = release_contract_plugin_version($root);
if (!release_contract_at_least($version, '1.17.10')) {
    echo "SKIP: contract 1.17.10 applies from 1.17.10.\n";
    exit(0);
}

$bootstrap = file_get_contents($root . '/includes/class-parcs-ht-bootstrap.php');
$cleanup = file_get_contents($root . '/includes/class-parcs-ht-admin-cleanup-11710.php');
$visibility = file_get_contents($root . '/includes/class-parcs-ht-public-visibility.php');
$portal = file_get_contents($root . '/includes/class-parcs-ht-group-portal.php');
$shared = file_get_contents($root . '/includes/class-parcs-ht-tariff-shared-1168.php');
$groups = file_get_contents($root . '/includes/class-parcs-ht-group-tariffs.php');
$quotes = file_get_contents($root . '/includes/class-parcs-ht-admin-group-quotes-1177.php');

foreach (array('bootstrap'=>$bootstrap,'cleanup'=>$cleanup,'visibility'=>$visibility,'portal'=>$portal,'shared'=>$shared,'groups'=>$groups,'quotes'=>$quotes) as $label=>$source) {
    if (!is_string($source)) {
        fwrite(STDERR, "Unable to read {$label}.\n");
        exit(1);
    }
}

release_contract_require_all($bootstrap, array(
    'class-parcs-ht-admin-cleanup-11710.php',
    'Parcs_HT_Admin_Cleanup_11710::init();',
), '1.17.10 cleanup bootstrap');

release_contract_require_all($cleanup, array(
    'route_obsolete_landings',
    'rebind_canonical_pages',
    "'parcs-ht-communication'",
    'Parcs_HT_Admin_Communication_1179::POPUP_PAGE',
    'remove_submenu_page',
    'Parcs_HT_Admin_Periods::PAGE',
    "array('Parcs_HT_Admin_Periods', 'page')",
), '1.17.10 obsolete admin navigation cleanup');

// Les deux rendus publics Groupes doivent dépendre de la même source annuelle.
release_contract_require_all($portal, array(
    'Parcs_HT_Public_Visibility::group_tariff_years()',
    'data-group-tariff-year',
), '1.17.10 group portal canonical tariff years');
release_contract_require_all($shared, array(
    'Parcs_HT_Public_Visibility::group_tariff_years()',
    'render_group_year',
), '1.17.10 shared tariff renderer canonical group years');

// Aucun ancien faux état d’indisponibilité des tarifs ne doit être rendu par ces couches.
release_contract_forbid($portal, array(
    'Les tarifs groupes ne sont pas disponibles pour cette année.',
    'data-group-tariff-unavailable',
    '$tariffs_unavailable',
), '1.17.10 group portal false tariff-unavailable cleanup');
release_contract_forbid($shared, array(
    'data-group-tariff-unavailable',
    '$tariffs_unavailable',
), '1.17.10 shared renderer false tariff-unavailable cleanup');
release_contract_forbid($groups, array(
    'Les tarifs groupes ne sont pas disponibles pour cette année.',
    'data-group-tariff-unavailable',
), '1.17.10 canonical group shortcode false unavailable cleanup');
release_contract_forbid($quotes, array(
    'Les tarifs groupes ne sont pas disponibles pour cette année.',
    'data-group-tariff-unavailable',
), '1.17.10 quote admin does not redefine tariff availability');

$tariffStart = strpos($visibility, 'public static function group_tariff_years()');
$quoteStart = strpos($visibility, 'public static function quote_years()');
$tariffEnd = $quoteStart;
if ($tariffStart === false || $quoteStart === false || $quoteStart <= $tariffStart) {
    fwrite(STDERR, "Unable to isolate canonical group/quote availability methods.\n");
    exit(1);
}
$tariffBody = substr($visibility, $tariffStart, $tariffEnd - $tariffStart);
$quoteBody = substr($visibility, $quoteStart);

release_contract_require_all($tariffBody, array(
    'group_tariff_grid_ready($year)',
    "'group_tariffs_visible'",
    'module_visible',
), '1.17.10 canonical group tariff availability');
release_contract_forbid($tariffBody, array(
    'group_quotes_enabled',
    'quote_years()',
    'season_for_year',
), '1.17.10 group tariff availability independent from quote state');
release_contract_require_all($quoteBody, array(
    "'group_quotes_enabled'",
    'module_visible',
), '1.17.10 quote availability remains separate');

// L’admin Devis doit toujours travailler sur l’année sélectionnée, sans secours inter-années.
release_contract_require_all($quotes, array(
    'binding_for_year($year',
    'season_for_year($year)',
    'canonical_tariffs($year)',
    '$raw[\'seasons\'][$year][\'tariffs\']',
), '1.17.10 quote year isolation');

// Les anciens correctifs publics restent conservés uniquement comme moteurs canoniques nécessaires.
release_contract_require_all($shared, array(
    "ReflectionMethod('Parcs_HT_Tariff_Public_Fixes'",
    'Renderer canonique groupes',
), '1.17.10 retained canonical compatibility layer documented');

echo "OK: 1.17.10 final cleanup keeps one group availability source, removes obsolete admin landings and preserves year isolation.\n";
