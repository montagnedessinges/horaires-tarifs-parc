<?php

require_once __DIR__ . '/release-contract.php';

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$version = release_contract_plugin_version($root);
if (!release_contract_at_least($version, '1.17.9')) {
    echo "SKIP: contract Communication applies from 1.17.9.\n";
    exit(0);
}

$main = file_get_contents($root . '/horaires-tarifs-parc.php');
$communication = file_get_contents($root . '/includes/class-parcs-ht-admin-communication-1179.php');
$advent = file_get_contents($root . '/includes/class-parcs-ht-advent.php');
$adventAdmin = file_get_contents($root . '/includes/class-parcs-ht-advent-admin.php');

foreach (array('main'=>$main,'communication'=>$communication,'advent'=>$advent,'advent admin'=>$adventAdmin) as $label=>$source) {
    if (!is_string($source)) {
        fwrite(STDERR, "Unable to read {$label}.\n");
        exit(1);
    }
}

release_contract_require_all($communication, array(
    "const POPUP_PAGE = 'parcs-ht-popup-1179'",
    "const ADVENT_PAGE = 'parcs-ht-advent-1179'",
    'Parcs_HT_Advent_Admin::render_workspace()',
    '[parc_calendrier_avent campagne=',
    '[parc_reglement_avent campagne=',
), 'Communication routes retained');

release_contract_require_all($advent, array(
    "const OPTION = 'parcs_ht_advent'",
    "add_shortcode('parc_calendrier_avent'",
    "add_shortcode('parc_reglement_avent'",
), 'Advent public engine preserved');

release_contract_require_all($adventAdmin, array(
    'render_workspace()',
    'parcs_ht_advent_save_campaign',
    'parcs_ht_advent_save_content',
    'parcs_ht_advent_save_partner',
    'parcs_ht_advent_save_result',
), 'Advent admin engine preserved');

if (!release_contract_at_least($version, '1.20.0')) {
    echo "OK: legacy Communication contract retained before 1.20.0.\n";
    exit(0);
}

$popup = file_get_contents($root . '/includes/class-parcs-ht-popup-1200.php');
$popupAdmin = file_get_contents($root . '/includes/class-parcs-ht-popup-admin-1200.php');
$popupJs = file_get_contents($root . '/assets/popup-1200.js');
$popupAdminJs = file_get_contents($root . '/assets/popup-admin-1200.js');
$popupCss = file_get_contents($root . '/assets/popup-1200.css');

foreach (array('popup engine'=>$popup,'popup admin'=>$popupAdmin,'popup js'=>$popupJs,'popup admin js'=>$popupAdminJs,'popup css'=>$popupCss) as $label=>$source) {
    if (!is_string($source)) {
        fwrite(STDERR, "Unable to read {$label}.\n");
        exit(1);
    }
}

release_contract_require_all($main, array(
    'Version: ' . $version,
    "define('PARCS_HT_VERSION', '" . $version . "')",
    'class-parcs-ht-popup-1200.php',
    'class-parcs-ht-popup-admin-1200.php',
    'Parcs_HT_Popup_1200::init();',
    'Parcs_HT_Popup_Admin_1200::init();',
), '1.20.0+ popup wiring');
release_contract_forbid($main, array(
    "Parcs_HT_Alerts::init()",
    "class-parcs-ht-alerts.php';",
), '1.20.0+ obsolete public popup bootstrap');

release_contract_require_all($popup, array(
    "const OPTION = 'parcs_ht_popups_1200'",
    "register_rest_route('parcs-ht/v1', '/popups'",
    "Cache-Control",
    "cache",
    "'image_id'",
    "'link_url'",
    "'alt'",
    "'size_preset'",
    "'priority'",
    "'reappear_mode'",
    "Parcs_HT_Schedule::language()",
), '1.20.0 autonomous visual popup engine');

release_contract_require_all($popupAdmin, array(
    "const PAGE = 'parcs-ht-popup-1179'",
    "wp_enqueue_media()",
    "Choisir dans la médiathèque",
    "1080 × 1080 px",
    "Petit — 480 px",
    "Moyen — 620 px",
    "Grand — 800 px",
    "data-popup1200-duplicate",
    "data-popup1200-preview",
    "array('fr'=>'FR','en'=>'EN','de'=>'DE')",
), '1.20.0 popup administration');

release_contract_require_all($popupJs, array(
    "cache:'no-store'",
    "localStorage",
    "reappearMode",
    "parcs-ht-popup1200-overlay",
    "document.body.style.overflow",
), '1.20.0 public popup runtime');

release_contract_require_all($popupAdminJs, array(
    "wp.media",
    "data-popup1200-media-select",
    "data-popup1200-duplicate",
    "data-popup1200-preview",
    "cleanupLegacyPopupControls",
), '1.20.0 popup admin interactions');

release_contract_require_all($popupCss, array(
    "object-fit:contain",
    "92vw",
    "max-height:92vh",
), '1.20.0 responsive no-crop rendering');

echo "OK: 1.20.0+ Communication uses one autonomous visual popup engine and keeps Advent independent.\n";

if (release_contract_at_least($version, '1.17.10')) {
    require __DIR__ . '/admin-save-integrity-11710-contract.php';
}
