<?php

require_once __DIR__ . '/release-contract.php';

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$version = release_contract_plugin_version($root);
if (!release_contract_at_least($version, '1.17.9')) {
    echo "SKIP: contract 1.17.9 applies from 1.17.9.\n";
    exit(0);
}

$main = file_get_contents($root . '/horaires-tarifs-parc.php');
$admin = file_get_contents($root . '/includes/class-parcs-ht-admin-communication-1179.php');
$alerts = file_get_contents($root . '/includes/class-parcs-ht-alerts.php');
$defaults = file_get_contents($root . '/includes/class-parcs-ht-defaults.php');
$advent = file_get_contents($root . '/includes/class-parcs-ht-advent.php');
$adventAdmin = file_get_contents($root . '/includes/class-parcs-ht-advent-admin.php');
$adventAppearance = file_get_contents($root . '/includes/class-parcs-ht-advent-appearance.php');
$popupJs = file_get_contents($root . '/assets/admin-communication-1179.js');
$adventJs = file_get_contents($root . '/assets/admin-advent-1179.js');
$css = file_get_contents($root . '/assets/admin-communication-1179.css');

foreach (array('main'=>$main,'communication admin'=>$admin,'alerts'=>$alerts,'defaults'=>$defaults,'advent'=>$advent,'advent admin'=>$adventAdmin,'advent appearance'=>$adventAppearance,'popup js'=>$popupJs,'advent js'=>$adventJs,'communication css'=>$css) as $label=>$source) {
    if (!is_string($source)) {
        fwrite(STDERR, "Unable to read {$label}.\n");
        exit(1);
    }
}

release_contract_require_all($main, array(
    'Version: 1.17.9',
    "define('PARCS_HT_VERSION', '1.17.9')",
    'class-parcs-ht-admin-communication-1179.php',
    'Parcs_HT_Admin_Communication_1179::init();',
    'Parcs_HT_Defaults::has_popup_source_fast()',
), '1.17.9 plugin wiring');

release_contract_require_all($admin, array(
    "const POPUP_PAGE = 'parcs-ht-popup-1179'",
    "const ADVENT_PAGE = 'parcs-ht-advent-1179'",
    "'htp-alerts'",
    "'htp-advent'",
    "name=\"action\" value=\"parcs_ht_save\"",
    "name=\"settings[_complete][alerts]\" value=\"1\"",
    "wp_nonce_field('parcs_ht_save')",
    'data-popup-enabled',
    'data-popup-details',
    'data-popup-button-enabled',
    'data-popup-button-details',
    'Gérer les événements et exceptions',
    'Parcs_HT_Admin_Navigation::PERIODS_PAGE',
    'Parcs_HT_Advent_Admin::render_workspace()',
    '[parc_calendrier_avent campagne=',
    '[parc_reglement_avent campagne=',
    'Aucun contexte d’année de saison',
), '1.17.9 dedicated Communication pages');

release_contract_require_all($admin, array(
    "array('fr'=>'FR','en'=>'EN','de'=>'DE')",
    '[title][', '[message][', '[button_label][', '[button_url][',
    'alert_bg_color', 'alert_title_color', 'alert_text_color',
    'alert_button_bg_color', 'alert_button_text_color', 'alert_reappear_hours',
    'Réglages avancés d’apparence',
), '1.17.9 popup editorial and appearance controls');

release_contract_require_all($alerts, array(
    "isset(\$settings['alerts'])",
    "['exceptions']",
    "['special_periods']",
    'render_auto_popup',
), '1.17.9 shared popup engine preserved');

release_contract_require_all($defaults, array(
    'has_popup_source_fast',
    "['alerts']",
    "['exceptions']",
    "['special_periods']",
), '1.17.9 popup source detection preserved');

release_contract_require_all($advent, array(
    "const OPTION = 'parcs_ht_advent'",
    "add_shortcode('parc_calendrier_avent'",
    "add_shortcode('parc_reglement_avent'",
    "add_shortcode('parc_calendrier_avent_' . \$language",
    "add_shortcode('parc_reglement_avent_' . \$language",
    "isset(\$atts['campagne'])",
    "wp_enqueue_style('parcs-ht-advent')",
    "wp_enqueue_script('parcs-ht-advent')",
), '1.17.9 Advent public engine preserved');

release_contract_require_all($adventAdmin, array(
    "const TAB = 'htp-advent'",
    'render_workspace()',
    'parcs_ht_advent_save_campaign',
    'parcs_ht_advent_save_content',
    'parcs_ht_advent_save_partner',
    'parcs_ht_advent_save_result',
    'parcs_ht_advent_import_csv',
), '1.17.9 Advent administration engine preserved');

release_contract_require_all($adventAppearance, array(
    "const OPTION = 'parcs_ht_advent_appearance'",
    "'campaigns' => array()",
    "'primary' => ''",
    "'secondary' => ''",
    "'open_day' => ''",
    "'today' => ''",
    "'locked' => ''",
    "'special' => ''",
), '1.17.9 Advent per-campaign appearance preserved');

release_contract_require_all($popupJs, array(
    '[data-popup-enabled]',
    '[data-popup-button-enabled]',
    '[data-popup-preview]',
    '[data-popup-add]',
    '[data-popup-remove]',
), '1.17.9 popup conditional UI');

release_contract_require_all($adventJs, array(
    "newPage='parcs-ht-advent-1179'",
    "parsed.searchParams.get('tab')==='htp-advent'",
    "parsed.searchParams.delete('season')",
    "requestUrl.searchParams.set('advent_fragment','1')",
    '[data-advent-campaign-select]',
), '1.17.9 Advent dedicated navigation');

release_contract_require_all($css, array('.htp-1179-popup-details','.htp-1179-advent','.htp-1179-preview-modal'), '1.17.9 Communication page styles');

echo "OK: 1.17.9 Communication separates Pop-up and Advent while preserving shared public engines and stores.\n";
