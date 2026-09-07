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

group_publication_check(strpos($settings, "const OPTION = 'parcs_ht_group_tariff_settings'") !== false, 'group publication and presentation keep their own settings store');
group_publication_check(strpos($settings, 'const STORE_VERSION = 3') !== false, 'group display settings store migrated to version 3');
group_publication_check(strpos($settings, 'public static function is_published') !== false, 'group publication exposes an explicit per-year status');
group_publication_check(strpos($settings, "(string)(\$season['published'] ?? '0') !== '1'") !== false, 'group tariffs can only publish inside a published season');
group_publication_check(strpos($seasons, 'hide_unpublished_groups') !== false && strpos($seasons, "\$tariffs['groups'] = array();") !== false, 'general public tariff rendering cannot expose unpublished group rates');
group_publication_check(strpos($quotes, 'Parcs_HT_Group_Tariff_Settings::is_published($year)') !== false, 'quote calculation requires group rates published for the visit year');

group_publication_check(strpos($shortcode, 'Parcs_HT_Defaults::settings()') !== false, 'dedicated group shortcode reads canonical tariff settings');
group_publication_check(strpos($shortcode, 'Parcs_HT_Tariff_Seasons::select_season_tariffs') !== false, 'dedicated group shortcode uses the public tariff season selector');
group_publication_check(strpos($shortcode, "\$rows = isset(\$tariffs['groups'])") !== false, 'group prices still come from canonical tariffs.groups');
group_publication_check(strpos($shortcode, 'Parcs_HT_Group_Tariff_Settings::settings') !== false, 'presentation comes from the dedicated display settings');
group_publication_check(strpos($shortcode, 'is_mds(') === false && strpos($shortcode, 'site_type') === false, 'public renderer is site-agnostic');
group_publication_check(strpos($shortcode, 'Bon de commande / voucher') === false && strpos($shortcode, 'Chorus Pro') === false, 'public renderer contains no hardcoded MDS payment content');
group_publication_check(strpos($shortcode, "do_shortcode('[parc_tableau_tarifs") === false, 'dedicated group shortcode renders its own visual instead of hiding the full tariff table');

group_publication_check(strpos($settings, "'payment_methods'=>array()") !== false && strpos($settings, "'info_blocks'=>array()") !== false, 'generic installations start with editable empty presentation lists');
group_publication_check(strpos($settings, 'legacy_mds_payment_methods') !== false && strpos($settings, 'version < 2') !== false, '1.13.6 MDS content is preserved only through a one-time migration');
group_publication_check(strpos($admin, 'Ajouter un moyen de paiement') !== false, 'admin can add payment methods');
group_publication_check(strpos($admin, 'Ajouter un bloc d’information') !== false, 'admin can add information blocks');
group_publication_check(strpos($admin, "data-gt-move=\"up\"") !== false && strpos($admin, 'data-gt-remove') !== false, 'admin can reorder and remove configurable group content');
group_publication_check(strpos($admin, 'Montagne des Singes, à la Forêt des Singes ou sur un autre site') !== false, 'admin explains that presentation settings are installation-specific');

echo "Group tariff publication contract: OK\n";