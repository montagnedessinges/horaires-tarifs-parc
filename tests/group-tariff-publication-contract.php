<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$settings = file_get_contents($root . '/includes/class-parcs-ht-group-tariff-settings.php');
$seasons = file_get_contents($root . '/includes/class-parcs-ht-tariff-seasons.php');
$quotes = file_get_contents($root . '/includes/class-parcs-ht-group-quotes.php');
$shortcode = file_get_contents($root . '/includes/class-parcs-ht-group-tariffs.php');
$admin = file_get_contents($root . '/assets/admin-groups.js');

function group_publication_check($condition, $message) {
    if (!$condition) { fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL); exit(1); }
    echo '[OK] ' . $message . PHP_EOL;
}

group_publication_check(strpos($settings, "const OPTION = 'parcs_ht_group_tariff_settings'") !== false, 'group publication has its own persistent settings store');
group_publication_check(strpos($settings, 'public static function is_published') !== false, 'group publication exposes an explicit per-year status');
group_publication_check(strpos($settings, "(string)(\$season['published'] ?? '0') !== '1'") !== false, 'group tariffs can only publish inside a published season');
group_publication_check(strpos($seasons, 'hide_unpublished_groups') !== false && strpos($seasons, "\$tariffs['groups'] = array();") !== false, 'general public tariff rendering cannot expose unpublished group rates');
group_publication_check(strpos($quotes, 'Parcs_HT_Group_Tariff_Settings::is_published($year)') !== false, 'quote calculation requires group rates published for the visit year');
group_publication_check(strpos($shortcode, 'published_years()') !== false && strpos($shortcode, 'future_notice') !== false, 'dedicated group shortcode renders only published years and supports future-year notice');
group_publication_check(strpos($shortcode, "do_shortcode('[parc_tableau_tarifs") === false && strpos($shortcode, 'parcs-ht-group-tariffs-source') === false, 'dedicated group shortcode no longer renders and hides the full tariff table');
group_publication_check(strpos($admin, 'Publier les tarifs groupes de cette année') !== false && strpos($admin, 'show_future_notice') !== false, 'group publication and future notice are editable in Groupes → Tarifs');
group_publication_check(strpos($admin, 'Un devis ne sera jamais calculé avec les tarifs d’une autre année.') !== false, 'admin explicitly documents no cross-year quote fallback');

echo "Group tariff publication contract: OK\n";
