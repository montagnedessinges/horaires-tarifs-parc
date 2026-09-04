<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$js = file_get_contents($root . '/assets/admin-shortcodes-guides.js');
$php = file_get_contents($root . '/includes/class-parcs-ht-pedagogical-guides.php');

function guides_enabled_save_contract($condition, $message) {
    if (!$condition) { fwrite(STDERR, $message . PHP_EOL); exit(1); }
    echo '[OK] ' . $message . PHP_EOL;
}

guides_enabled_save_contract(strpos($php, '[enabled]') !== false && strpos($php, 'checked($g') !== false, 'Guide visibility checkbox keeps its enabled field and checked state');
guides_enabled_save_contract(strpos($php, "'enabled'=>!empty(") !== false && strpos($php, "?'1':'0'") !== false, 'Server stores enabled as an explicit 1 or 0');
guides_enabled_save_contract(strpos($js, 'parcs_ht_save_pedagogical_guides') !== false, 'Visibility persistence logic is scoped to the guides save form');
guides_enabled_save_contract(strpos($js, 'data-htp-guide-enabled-fallback') !== false, 'Guides save adds one explicit fallback value for unchecked visibility boxes');
guides_enabled_save_contract(strpos($js, "hidden.value = '0'") !== false, 'Unchecked visibility is submitted explicitly as 0');
guides_enabled_save_contract(strpos($js, 'checkbox.parentNode.insertBefore(hidden, checkbox)') !== false, 'Fallback is inserted before the checkbox so a checked value 1 wins');
guides_enabled_save_contract(strpos($js, 'fetch(') === false && strpos($js, 'XMLHttpRequest') === false && strpos($js, 'jQuery.ajax') === false, 'Guide save fix adds no network or live-preview layer');

echo "Guide visibility save contract: OK\n";
