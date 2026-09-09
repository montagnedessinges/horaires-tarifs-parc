<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$file = $root . '/includes/class-parcs-ht-admin-shortcode-preview.php';
$source = file_get_contents($file);
$js = file_get_contents($root . '/assets/admin-shortcode-preview.js');

$checks = array(
    'ajax frame action exists' => strpos($source, "add_action('wp_ajax_' . self::ACTION") !== false,
    'preview endpoint checks capability' => strpos($source, "current_user_can('manage_options')") !== false,
    'preview endpoint checks nonce' => strpos($source, 'check_ajax_referer(self::NONCE_ACTION)') !== false,
    'registry renderer is called only for requested preview' => strpos($source, 'Parcs_HT_Shortcode_Registry::render_preview($base, $language)') !== false,
    'legacy pre-render source loop removed' => strpos($source, 'render_source') === false && strpos($source, 'data-htp-shortcode-preview-source') === false,
    'frame uses public frontend script' => strpos($source, 'assets/frontend.js') !== false,
    'date and time simulation are passed to frame' => strpos($source, 'preview_timestamp_ms') !== false,
    'client uses iframe request' => strpos($js, 'frame.src=frameUrl()') !== false,
    'client never reloads full admin page for preview refresh' => strpos($js, 'window.location.reload()') === false,
    'client keeps explicit FR EN DE controls' => strpos($js, "['fr','en','de']") !== false,
    'client adds global test time field' => strpos($js, 'data-htp-preview-time') !== false,
);

$failed = array();
foreach ($checks as $label => $ok) {
    if (!$ok) $failed[] = $label;
}

if ($failed) {
    fwrite(STDERR, "Admin shortcode preview script regression failed:\n- " . implode("\n- ", $failed) . "\n");
    exit(1);
}

echo "Admin shortcode preview script regression: OK\n";
