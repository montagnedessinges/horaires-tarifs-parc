<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$appearance = file_get_contents($root . '/includes/class-parcs-ht-advent-appearance.php');
$css = file_get_contents($root . '/assets/advent.css');
$js = file_get_contents($root . '/assets/advent-appearance-admin.js');

function advent_appearance_check($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL);
        exit(1);
    }
    echo '[OK] ' . $message . PHP_EOL;
}

advent_appearance_check(strpos($appearance, "const OPTION = 'parcs_ht_advent_appearance'") !== false, 'appearance data stays in its own option');
advent_appearance_check(strpos($appearance, "'primary' => ''") !== false && strpos($appearance, "'special' => ''") !== false, 'default palette inherits the existing display');
advent_appearance_check(strpos($appearance, 'sanitize_hex_color') !== false, 'saved colors are sanitized');
advent_appearance_check(strpos($appearance, 'data-campaign-id') !== false, 'public overrides are scoped to a campaign');
advent_appearance_check(strpos($appearance, 'color-mix(in srgb,') !== false, 'custom colors can add a subtle tinted background');
advent_appearance_check(strpos($css, '--htp-advent-open-day-bg: transparent') !== false && strpos($css, '--htp-advent-special-bg: transparent') !== false, 'public stylesheet remains visually neutral without custom colors');
advent_appearance_check(strpos($js, 'Revenir aux couleurs héritées') !== false, 'admin can reset the campaign palette');
advent_appearance_check(strpos($js, "currentView()!=='campaign'") !== false, 'appearance controls remain inside the campaign view');

echo "Advent appearance regression: OK\n";
