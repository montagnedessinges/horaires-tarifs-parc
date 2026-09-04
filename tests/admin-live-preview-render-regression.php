<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$js = file_get_contents($root . '/assets/admin-live-visual-preview.js');

function live_render_regression($condition, $message) {
    if (!$condition) { fwrite(STDERR, $message . PHP_EOL); exit(1); }
    echo '[OK] ' . $message . PHP_EOL;
}

live_render_regression(strpos($js, "var raf=0") === false, 'Live previews do not share one global animation frame');
live_render_regression(strpos($js, 'panel._htpPreviewRaf') !== false, 'Each preview panel owns its render scheduling state');
live_render_regression(strpos($js, "renderer(section,stage)") !== false, 'Scheduled rendering targets the current panel stage');
live_render_regression(strpos($js, 'htp-preview-today') !== false && strpos($js, 'htp-preview-calendar') !== false, 'Hours and calendar preview contains its real demo components');
live_render_regression(strpos($js, "Object.keys(configs).forEach") !== false, 'All configured visual sections are initialized independently');

echo "Admin live preview render regression: OK\n";
