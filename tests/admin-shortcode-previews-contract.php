<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$php = file_get_contents($root . '/includes/class-parcs-ht-admin-shortcode-preview.php');
$js = file_get_contents($root . '/assets/admin-shortcode-preview.js');
$css = file_get_contents($root . '/assets/admin-shortcode-preview.css');

function shortcode_preview_contract($condition, $message) {
    if (!$condition) { fwrite(STDERR, $message . PHP_EOL); exit(1); }
    echo '[OK] ' . $message . PHP_EOL;
}

foreach (array('page','today','calendar','tariffs','alert','header_hour','header_status','home_opening','quote_page') as $module) {
    shortcode_preview_contract(strpos($php, "'module'=>'" . $module . "'") !== false, 'Real preview includes module ' . $module);
}
shortcode_preview_contract(strpos($php, "shortcode_exists('parc_guides_pedagogiques_fr')") !== false, 'Pedagogical guides preview uses the registered real shortcode');
shortcode_preview_contract(strpos($php, 'Parcs_HT_Shortcodes::render') !== false, 'Core previews use the public shortcode renderer');
shortcode_preview_contract(strpos($js, 'data-htp-shortcode-preview-source') !== false, 'Admin UI consumes each generated shortcode source');
shortcode_preview_contract(strpos($js, 'Fond des aperçus') !== false && strpos($js, 'sessionStorage') !== false, 'One browser-only background color controls the previews');
shortcode_preview_contract(strpos($js, 'Mettre à jour les aperçus') !== false && strpos($js, 'data-htp-shortcode-preview-refresh') !== false, 'Admin preview exposes one explicit refresh button');
shortcode_preview_contract(strpos($js, 'window.location.reload()') !== false, 'Refresh button regenerates PHP shortcode previews through a page reload');
shortcode_preview_contract(strpos($js, 'fetch(') === false && strpos($js, 'XMLHttpRequest') === false && strpos($js, 'jQuery.ajax') === false, 'Preview UI adds no background network requests');
shortcode_preview_contract(strpos($css, '.htp-shortcode-preview-list') !== false && strpos($css, '.htp-real-shortcode-preview-canvas') !== false, 'Individual previews have dedicated admin layout styles');
shortcode_preview_contract(strpos($css, '.htp-real-shortcode-preview-tools') !== false, 'Refresh and background controls share a dedicated responsive toolbar');

echo "Admin shortcode previews contract: OK\n";
