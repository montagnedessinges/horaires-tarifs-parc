<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$php = file_get_contents($root . '/includes/class-parcs-ht-admin-shortcode-preview.php');
$registry = file_get_contents($root . '/includes/class-parcs-ht-shortcode-registry.php');
$js = file_get_contents($root . '/assets/admin-shortcode-preview.js');
$css = file_get_contents($root . '/assets/admin-shortcode-preview.css');

function shortcode_preview_contract($condition, $message) {
    if (!$condition) { fwrite(STDERR, $message . PHP_EOL); exit(1); }
    echo '[OK] ' . $message . PHP_EOL;
}

foreach (array('page','today','calendar','tariffs','alert','header_hour','header_status','home_opening','quote_page','group_tariffs','guides') as $module) {
    shortcode_preview_contract(strpos($registry, "'module'=>'" . $module . "'") !== false, 'Registry includes preview module ' . $module);
}
shortcode_preview_contract(strpos($php, 'Parcs_HT_Shortcode_Registry::public_rows()') !== false, 'Preview enumerates the central shortcode registry');
shortcode_preview_contract(strpos($php, 'Parcs_HT_Shortcode_Registry::render_preview') !== false, 'Previews use each public renderer through the registry');
shortcode_preview_contract(strpos($php, "assets/pedagogical-guides.css") !== false && strpos($php, "'parcs-ht-pedagogical-guides'") !== false, 'Guide preview loads the real public guide stylesheet');
shortcode_preview_contract(strpos($php, 'Parcs_HT_Guide_Appearance::settings()') !== false && strpos($php, '--htp-guide-mobile-image-height') === false, 'Guide preview applies saved colors without overriding the fixed public image format');
shortcode_preview_contract(strpos($js, 'data-htp-shortcode-preview-source') !== false, 'Admin UI consumes each generated shortcode source');
shortcode_preview_contract(strpos($js, 'initGuidePreview') !== false && strpos($js, "[data-guide-cycle-filters]") !== false && strpos($js, "[data-guide-language-filters]") !== false, 'Guide preview restores the real filter interactions after moving shortcode markup');
shortcode_preview_contract(strpos($js, 'initGroupTariffPreview') !== false && strpos($js, 'data-htp-group-year-tab') !== false, 'Group tariff preview restores year switching after script stripping');
shortcode_preview_contract(strpos($js, "['fr','en','de']") !== false && strpos($js, 'data-htp-preview-lang-button') !== false, 'Every preview offers FR EN DE switching');
shortcode_preview_contract(strpos($js, 'Fond des aperçus') !== false && strpos($js, 'sessionStorage') !== false, 'One browser-only background color controls the previews');
shortcode_preview_contract(strpos($js, 'Mettre à jour les aperçus') !== false && strpos($js, 'data-htp-shortcode-preview-refresh') !== false, 'Admin preview exposes one explicit refresh button');
shortcode_preview_contract(strpos($js, 'window.location.reload()') !== false, 'Refresh button regenerates PHP shortcode previews through a page reload');
shortcode_preview_contract(strpos($js, 'fetch(') === false && strpos($js, 'XMLHttpRequest') === false && strpos($js, 'jQuery.ajax') === false, 'Preview UI adds no background network requests');
shortcode_preview_contract(strpos($css, '.htp-shortcode-preview-list') !== false && strpos($css, '.htp-real-shortcode-preview-canvas') !== false, 'Individual previews have dedicated admin layout styles');
shortcode_preview_contract(strpos($css, '.htp-real-shortcode-preview-tools') !== false, 'Refresh and background controls share a dedicated responsive toolbar');

echo "Admin shortcode previews contract: OK\n";
