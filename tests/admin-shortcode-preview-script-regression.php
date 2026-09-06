<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$file = $root . '/includes/class-parcs-ht-admin-shortcode-preview.php';
$source = file_get_contents($file);

$checks = array(
    'preview helper exists' => strpos($source, 'private static function preview_html') !== false,
    'script blocks are stripped from preview HTML' => strpos($source, "preg_replace('#<script\\b[^>]*>.*?</script\\s*>#is', '', \$html)") !== false,
    'registry previews are sanitized before output' => strpos($source, 'self::preview_html(Parcs_HT_Shortcode_Registry::render_preview(') !== false,
    'preview enumerates central registry only' => strpos($source, 'Parcs_HT_Shortcode_Registry::public_rows()') !== false,
    'legacy hardcoded preview list removed' => strpos($source, "array('key'=>'full'") === false,
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
