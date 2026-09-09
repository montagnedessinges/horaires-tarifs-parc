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
shortcode_preview_contract(strpos($php, 'Parcs_HT_Shortcode_Registry::public_rows()') !== false, 'Preview navigation is built from the central shortcode registry');
shortcode_preview_contract(strpos($php, "add_action('wp_ajax_' . self::ACTION") !== false, 'Preview exposes one authenticated on-demand frame endpoint');
shortcode_preview_contract(strpos($php, 'current_user_can(\'manage_options\')') !== false && strpos($php, 'check_ajax_referer(self::NONCE_ACTION)') !== false, 'Preview endpoint requires administrator capability and nonce');
shortcode_preview_contract(strpos($php, 'Parcs_HT_Shortcode_Registry::render_preview($base, $language)') !== false, 'Requested preview uses the real renderer through the registry');
shortcode_preview_contract(strpos($php, "assets/frontend.css") !== false && strpos($php, "assets/frontend.js") !== false, 'Isolated preview frame loads the real public frontend assets');
shortcode_preview_contract(strpos($php, "assets/pedagogical-guides.css") !== false, 'Guide preview frame loads the real public guide stylesheet');
shortcode_preview_contract(strpos($php, 'preview_timestamp_ms') !== false && strpos($php, 'class PreviewDate extends RealDate') !== false, 'Preview frame can simulate the requested site date and time');
shortcode_preview_contract(strpos($php, 'admin_footer') === false && strpos($php, 'render_source') === false, 'Page load no longer renders every shortcode preview in advance');
shortcode_preview_contract(strpos($js, 'data-htp-shortcode-preview-nav') !== false && strpos($js, 'data-htp-preview-base') !== false, 'Admin UI provides compact shortcode navigation');
shortcode_preview_contract(strpos($js, "['fr','en','de']") !== false && strpos($js, 'data-htp-preview-lang') !== false, 'Selected preview offers FR EN DE switching');
shortcode_preview_contract(strpos($js, 'data-htp-preview-time') !== false && strpos($js, 'Heure à tester') !== false, 'Existing date simulation is extended with a global time control');
shortcode_preview_contract(strpos($js, 'frame.src=frameUrl()') !== false, 'Only the selected shortcode/language preview frame is requested');
shortcode_preview_contract(strpos($js, 'Mettre à jour l’aperçu') !== false && strpos($js, 'window.location.reload()') === false, 'Refresh reloads only the selected preview without reloading the admin page');
shortcode_preview_contract(strpos($js, "event.target.closest('[data-htp-preview-button]')") !== false, 'Global date/time test button also refreshes the selected shortcode preview');
shortcode_preview_contract(strpos($css, '.htp-shortcode-preview-workbench') !== false && strpos($css, '.htp-shortcode-preview-nav') !== false, 'Preview uses a compact navigation plus viewer layout');
shortcode_preview_contract(strpos($css, '.htp-real-shortcode-preview-frame') !== false, 'Selected preview has a dedicated responsive frame');

echo "Admin shortcode previews contract: OK\n";
