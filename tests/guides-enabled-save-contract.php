<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$js = file_get_contents($root . '/assets/admin-shortcodes-guides.js');
$integrity = file_get_contents($root . '/includes/class-parcs-ht-save-integrity.php');
$main = file_get_contents($root . '/horaires-tarifs-parc.php');

function guides_enabled_save_contract($condition, $message) {
    if (!$condition) { fwrite(STDERR, $message . PHP_EOL); exit(1); }
    echo '[OK] ' . $message . PHP_EOL;
}

guides_enabled_save_contract(strpos($integrity, "'enabled' => isset(\$item['enabled'])") !== false, 'Server treats a missing visibility checkbox as 0 without JavaScript mutation');
guides_enabled_save_contract(strpos($integrity, "(string)\$item['enabled'] === '1' ? '1' : '0'") !== false, 'Server stores guide visibility explicitly as 1 or 0');
guides_enabled_save_contract(strpos($js, 'protectGuideVisibilitySave') === false, 'Guide visibility no longer relies on a submit-time JavaScript patch');
guides_enabled_save_contract(strpos($js, 'data-htp-guide-enabled-fallback') === false && strpos($js, "hidden.value = '0'") === false, 'No hidden visibility fallback is injected by JavaScript');
guides_enabled_save_contract(strpos($main, 'parcs-ht-admin-save-guard') === false, 'Duplicate standalone save guard is no longer enqueued');
guides_enabled_save_contract(strpos($integrity, 'persist_guides_value') !== false && strpos($integrity, 'get_option(Parcs_HT_Pedagogical_Guides::OPTION') !== false, 'Guide save is reread from WordPress after writing');
guides_enabled_save_contract(strpos($integrity, 'same_value($clean, $stored_year)') !== false, 'Guide save confirmation depends on persisted data matching requested data');

echo "Guide visibility save contract: OK\n";
