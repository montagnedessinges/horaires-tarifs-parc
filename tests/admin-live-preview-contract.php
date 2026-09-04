<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$main = file_get_contents($root . '/horaires-tarifs-parc.php');
$js = file_get_contents($root . '/assets/admin-live-visual-preview.js');
$css = file_get_contents($root . '/assets/admin-live-visual-preview.css');

function live_preview_contract($condition, $message) {
    if (!$condition) { fwrite(STDERR, $message . PHP_EOL); exit(1); }
    echo '[OK] ' . $message . PHP_EOL;
}

live_preview_contract(strpos($main, "if (\$hook !== 'toplevel_page_parcs-horaires-tarifs') return;") !== false, 'Live preview assets remain scoped to the plugin admin screen');
live_preview_contract(strpos($main, 'assets/admin-live-visual-preview.js') !== false && strpos($main, 'assets/admin-live-visual-preview.css') !== false, 'Global live preview assets are registered from the admin bootstrap');
$publicHook = strpos($main, "add_action('wp_footer'");
$adminAsset = strpos($main, 'admin-live-visual-preview');
$pluginsLoaded = strpos($main, "add_action('plugins_loaded'");
live_preview_contract($publicHook !== false && $adminAsset !== false && $pluginsLoaded !== false && $adminAsset > $publicHook && $adminAsset < $pluginsLoaded, 'Live preview assets are isolated inside the admin bootstrap area');
live_preview_contract(strpos($js, 'sessionStorage') !== false, 'Preview environment is browser-session only');
live_preview_contract(strpos($js, 'fetch(') === false && strpos($js, 'XMLHttpRequest') === false && strpos($js, 'jQuery.ajax') === false, 'Live visual changes do not make network requests');
live_preview_contract(strpos($js, "'htp-general'") !== false && strpos($js, "'htp-regular'") !== false && strpos($js, "'htp-holidays'") !== false && strpos($js, "'htp-alerts'") !== false && strpos($js, "'htp-tariffs'") !== false, 'Visual preview coverage includes all core appearance sections');
live_preview_contract(strpos($js, 'data-guide-appearance-panel') !== false && strpos($js, 'htp-guide-full-preview') !== false, 'Guide full shortcode preview participates in the shared preview environment');
live_preview_contract(strpos($js, 'Desktop') !== false && strpos($js, 'Mobile') !== false && strpos($js, 'Fond') !== false, 'Preview environment exposes background and device simulation');
live_preview_contract(strpos($css, '--htp-preview-page-bg') !== false && strpos($css, '[data-preview-device=mobile]') !== false, 'Preview canvas supports simulated page backgrounds and mobile width');

echo "Admin live visual preview contract: OK\n";
