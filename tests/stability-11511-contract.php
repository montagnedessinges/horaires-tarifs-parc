<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$stability = file_get_contents($root . '/includes/class-parcs-ht-stability-11511.php');
$policy = file_get_contents($root . '/includes/class-parcs-ht-display-policy.php');
$seasons = file_get_contents($root . '/includes/class-parcs-ht-tariff-seasons.php');
$retail_js = file_get_contents($root . '/assets/retail-channels.js');
$tabs_js = file_get_contents($root . '/assets/stability-11511.js');
$admin_groups_js = file_get_contents($root . '/assets/admin-groups.js');
$quote_gate = file_get_contents($root . '/includes/class-parcs-ht-quote-gate.php');
$main = file_get_contents($root . '/horaires-tarifs-parc.php');

function stability_11511_check($condition, $message) {
    if (!$condition) { fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL); exit(1); }
    echo '[OK] ' . $message . PHP_EOL;
}

stability_11511_check(strpos($main, 'Version: 1.15.13') !== false && strpos($main, "define('PARCS_HT_VERSION', '1.15.13')") !== false, 'release version is 1.15.13');
stability_11511_check(strpos($main, "class-parcs-ht-stability-11511.php") !== false && strpos($main, 'Parcs_HT_Stability_11511::init();') !== false, 'stability layer is loaded');
stability_11511_check(strpos($stability, "remove_filter('pre_do_shortcode_tag', array('Parcs_HT_Public_Seasons', 'prepare_year_scope'), 6)") !== false, 'legacy shortcode year scoping is disabled');
stability_11511_check(strpos($stability, "remove_filter('do_shortcode_tag', array('Parcs_HT_Public_Seasons', 'wrap_year_tabs'), 20)") !== false, 'legacy reload year links are disabled');
stability_11511_check(strpos($stability, "remove_action('admin_init', array('Parcs_HT_Verifier', 'maybe_verify_version_once'))") !== false, 'blocking automatic admin verifier is disabled');

stability_11511_check(strpos($policy, "'retail_tariffs_visible'") !== false, 'retail tariff visibility has an explicit yearly control');
stability_11511_check(strpos($policy, "'groups_schedule_visible'=>'Afficher les horaires aux groupes'") !== false, 'group schedule visibility has an explicit yearly control');
stability_11511_check(strpos($policy, 'public static function retail_years()') !== false, 'retail years have a dedicated visibility resolver');
stability_11511_check(strpos($seasons, 'Parcs_HT_Display_Policy::retail_years()') !== false, 'public tariff season selection uses retail visibility, not schedule publication alone');
stability_11511_check(strpos($seasons, "in_array(\$current, \$years, true)") !== false, 'current year remains selected when 2026 and 2027 are both visible');
stability_11511_check(strpos($seasons, "\$value['general']['year'] = \$year") !== false, 'selected tariff year and rendered settings stay synchronized');

stability_11511_check(strpos($retail_js, 'new MutationObserver') === false, 'retail channel renderer no longer watches the entire document');
stability_11511_check(strpos($retail_js, 'parcsht:tariffs-updated') !== false, 'retail channel renderer refreshes only after a tariff-year switch');

stability_11511_check(strpos($admin_groups_js, '$old.remove();') === false && strpos($admin_groups_js, '$quoteTab.remove();') === false, 'group admin no longer deletes canonical tabs after page load');
stability_11511_check(strpos($admin_groups_js, 'canonicalActivate') !== false, 'group admin delegates panel activation to the canonical admin tab engine');
stability_11511_check(strpos($admin_groups_js, 'parcsHTGroupTariffView:') !== false, 'group tariff context survives a main-form save');
stability_11511_check(strpos($quote_gate, '$closed = true;') !== false && strpos($quote_gate, '$closed = false;') === false, 'quote date resolution fails closed when schedules are unavailable');

echo "1.15.13 stabilization contract: OK\n";
