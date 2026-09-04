<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$main = file_get_contents($root . '/horaires-tarifs-parc.php');
$preview_class = file_get_contents($root . '/includes/class-parcs-ht-admin-shortcode-preview.php');
$js = file_get_contents($root . '/assets/admin-shortcode-preview.js');
$css = file_get_contents($root . '/assets/admin-shortcode-preview.css');

function live_preview_contract($condition, $message) {
    if (!$condition) { fwrite(STDERR, $message . PHP_EOL); exit(1); }
    echo '[OK] ' . $message . PHP_EOL;
}

live_preview_contract(strpos($main, "Version: 1.12.2") !== false && strpos($main, "PARCS_HT_VERSION', '1.12.2'") !== false, 'Preview simplification is versioned as 1.12.2');
live_preview_contract(strpos($main, 'class-parcs-ht-admin-shortcode-preview.php') !== false && strpos($main, 'Parcs_HT_Admin_Shortcode_Preview::init()') !== false, 'Real shortcode preview is initialized from the admin bootstrap');
live_preview_contract(strpos($main, 'assets/admin-live-visual-preview.js') === false && strpos($main, 'assets/admin-preview-enhanced.js') === false, 'Legacy simulated live previews are no longer loaded');
live_preview_contract(strpos($preview_class, "const SCREEN = 'toplevel_page_parcs-horaires-tarifs'") !== false, 'Shortcode preview assets remain scoped to the plugin admin screen');
live_preview_contract(strpos($preview_class, "Parcs_HT_Shortcodes::render('page', 'fr', array())") !== false, 'Admin preview uses the real full public shortcode renderer');
live_preview_contract(strpos($preview_class, "assets/frontend.css") !== false, 'Admin preview uses the real frontend shortcode stylesheet');
live_preview_contract(strpos($js, 'sessionStorage') !== false, 'Preview background is browser-session only');
live_preview_contract(strpos($js, 'fetch(') === false && strpos($js, 'XMLHttpRequest') === false && strpos($js, 'jQuery.ajax') === false, 'Preview background changes do not make network requests');
live_preview_contract(strpos($js, 'Fond de l’aperçu') !== false && strpos($js, 'input type="color"') !== false, 'Preview exposes only the requested background color control');
live_preview_contract(strpos($js, "document.getElementById('htp-preview')") !== false, 'Real shortcode preview is confined to the dedicated Preview tab');
live_preview_contract(strpos($css, '.htp-real-shortcode-preview-canvas') !== false, 'Real shortcode preview has an isolated simulation canvas');

echo "Admin real shortcode preview contract: OK\n";
