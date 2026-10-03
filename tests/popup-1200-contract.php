<?php

require_once __DIR__ . '/release-contract.php';

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$version = release_contract_plugin_version($root);
if (!release_contract_at_least($version, '1.20.0')) {
    echo "SKIP: popup 1.20.0 contract applies from 1.20.0.\n";
    exit(0);
}

$main = file_get_contents($root . '/horaires-tarifs-parc.php');
$engine = file_get_contents($root . '/includes/class-parcs-ht-popup-1200.php');
$admin = file_get_contents($root . '/includes/class-parcs-ht-popup-admin-1200.php');
$front = file_get_contents($root . '/assets/popup-1200.js');
$frontCss = file_get_contents($root . '/assets/popup-1200.css');
$adminJs = file_get_contents($root . '/assets/popup-admin-1200.js');
$adminCss = file_get_contents($root . '/assets/popup-admin-1200.css');

foreach (array('main'=>$main,'engine'=>$engine,'admin'=>$admin,'front'=>$front,'front css'=>$frontCss,'admin js'=>$adminJs,'admin css'=>$adminCss) as $label=>$source) {
    if (!is_string($source)) {
        fwrite(STDERR, "Unable to read {$label}.\n");
        exit(1);
    }
}

release_contract_require_all($main, array(
    'class-parcs-ht-popup-1200.php',
    'class-parcs-ht-popup-admin-1200.php',
    'Parcs_HT_Popup_1200::init();',
    'Parcs_HT_Popup_Admin_1200::init();',
), 'popup 1.20.0 bootstrap');

release_contract_forbid($main, array(
    "Parcs_HT_Alerts::init()",
), 'legacy public popup engine disabled');

release_contract_require_all($engine, array(
    "const OPTION = 'parcs_ht_popups_1200'",
    "register_rest_route('parcs-ht/v1', '/popups'",
    "'permission_callback' => '__return_true'",
    "wp_enqueue_style(",
    "wp_enqueue_script(",
    "Parcs_HT_Schedule::language()",
    "wp_get_attachment_image_url",
    "if (\$image_url === '') continue;",
    "'priority'",
    "'reappear_mode'",
    "'size_preset'",
    "'custom_width'",
    "Cache-Control",
    "no-store, no-cache",
), 'popup 1.20.0 public engine');

release_contract_require_all($admin, array(
    "wp_enqueue_media()",
    "Choisir dans la médiathèque",
    "Format conseillé : 1080 × 1080 px",
    "Petit — 480 px",
    "Moyen — 620 px",
    "Grand — 800 px",
    "Une seule fois sur cet appareil",
    "Réafficher après X heures",
    "data-popup1200-duplicate",
    "data-popup1200-preview",
    "link_url",
    "alt",
), 'popup 1.20.0 admin');

release_contract_require_all($front, array(
    "cache:'no-store'",
    "localStorage",
    "reappearMode",
    "parcs-ht-popup1200-overlay",
    "aria-modal",
    "Escape",
), 'popup 1.20.0 front runtime');

release_contract_forbid($front, array(
    'navigator.language',
    'navigator.languages',
), 'popup language must follow page language');

release_contract_require_all($frontCss, array(
    'object-fit:contain',
    '92vw',
    'max-height:92vh',
), 'popup no-crop responsive CSS');

release_contract_require_all($adminJs, array(
    'wp.media',
    'cleanupLegacyPopupControls',
    'data-popup1200-media-select',
), 'popup admin media and legacy cleanup');

release_contract_require_all($adminCss, array(
    '.htp-1174-periods .htp-popup-block{display:none!important}',
    'object-fit:contain',
), 'popup admin visual isolation');

echo "OK: popup 1.20.0 autonomous image-only engine contract validated.\n";
