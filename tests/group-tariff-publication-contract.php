<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);
$settings = file_get_contents($root . '/includes/class-parcs-ht-group-tariff-settings.php');
$seasons = file_get_contents($root . '/includes/class-parcs-ht-tariff-seasons.php');
$quotes = file_get_contents($root . '/includes/class-parcs-ht-group-quotes.php');
$shortcode = file_get_contents($root . '/includes/class-parcs-ht-group-tariffs.php');
$admin = file_get_contents($root . '/assets/admin-groups.js');
$switch_admin = file_get_contents($root . '/includes/class-parcs-ht-group-tariff-switch-admin.php');
$switch_js = file_get_contents($root . '/assets/admin-group-tariff-switch.js');

function group_publication_check($condition, $message) {
    if (!$condition) { fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL); exit(1); }
    echo '[OK] ' . $message . PHP_EOL;
}

group_publication_check(strpos($settings, "const OPTION = 'parcs_ht_group_tariff_settings'") !== false, 'group publication and presentation keep their own settings store');
group_publication_check(strpos($settings, 'const STORE_VERSION = 4') !== false, 'group display settings store migrated to version 4');
group_publication_check(strpos($settings, "'display_from'=>''") !== false && strpos($settings, 'save_display_from') !== false, 'each group year can own a commercial display date');
group_publication_check(strpos($settings, 'public static function is_published') !== false, 'group publication exposes an explicit per-year status');
group_publication_check(strpos($settings, 'if (!$season || !self::has_grid($year)) return false;') !== false, 'group publication requires its own usable canonical grid');
group_publication_check(strpos($settings, "(string)(\$season['published'] ?? '0') !== '1'") === false, 'group publication no longer depends on the general public season publication');
group_publication_check(strpos($settings, 'public static function public_year') !== false && strpos($settings, 'effective_display_date') !== false, 'group public year follows the commercial switch date');
group_publication_check(strpos($settings, "return \$configured !== '' ? \$configured : \$year . '-01-01';") !== false, 'years without a switch date retain the standard January first fallback');
group_publication_check(strpos($settings, 'quote_binding_valid') !== false && strpos($settings, 'readiness') !== false, 'admin readiness covers the quote binding and public year');

group_publication_check(strpos($seasons, 'hide_unpublished_groups') !== false && strpos($seasons, "\$tariffs['groups'] = array();") !== false, 'general public tariff rendering cannot expose unpublished group rates');
group_publication_check(strpos($quotes, 'private static function quote_enabled_for_year') !== false, 'quote engine owns per-year quote activation resolution');
group_publication_check(strpos($quotes, "array_key_exists('group_quotes_enabled', \$season)") !== false, 'explicit annual quote switch remains authoritative');
group_publication_check(strpos($quotes, 'Parcs_HT_Group_Tariff_Settings::quote_enabled($year)') === false, 'quote availability no longer depends on commercial group publication state');
group_publication_check(strpos($quotes, "get_option(self::OPTION, array())") !== false, 'legacy quote activation is recovered from the historical quote store itself');
group_publication_check(strpos($quotes, "array_keys((array)(\$all['seasons'] ?? array()))") !== false, 'online quote settings discover canonical future seasons instead of relying on legacy quote storage');
group_publication_check(strpos($quotes, 'private static function stable_binding') !== false && strpos($quotes, 'krsort($previous, SORT_NUMERIC)') !== false, 'future seasons can inherit a stable quote binding from a previous season');
group_publication_check(strpos($quotes, 'private static function binding_matches_year') !== false, 'cross-year quote bindings are validated against the target year');
group_publication_check(strpos($quotes, 'private static function legacy_binding_for_year') !== false, 'missing stable bindings can be recovered from the historical target-year mapping');
group_publication_check(strpos($quotes, 'public static function season_for_year') !== false, 'quote engine exposes one canonical per-year availability accessor');

group_publication_check(strpos($shortcode, 'Parcs_HT_Group_Tariff_Settings::public_year()') !== false, 'dedicated group shortcode selects the commercial group year');
group_publication_check(strpos($shortcode, "\$settings['tariffs'] = isset(\$season['tariffs'])") !== false, 'dedicated group shortcode reads the selected year canonical tariff payload directly');
group_publication_check(strpos($shortcode, 'Parcs_HT_Tariff_Seasons::select_season_tariffs') === false, 'dedicated group shortcode is independent from the general public tariff year');
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
group_publication_check(strpos($switch_js, 'Bascule commerciale des tarifs groupes') !== false && strpos($switch_js, 'data-gts-date') !== false, 'group admin exposes a dedicated commercial switch date');
group_publication_check(strpos($switch_js, 'Saison publique générale') !== false && strpos($switch_js, 'Liaison devis') !== false, 'group admin exposes readiness without forcing the public season online');
group_publication_check(strpos($switch_admin, "do_action('litespeed_purge_all')") !== false && strpos($switch_admin, 'wp_schedule_single_event') !== false, 'scheduled group switch purges the page cache');

echo "Group tariff publication contract: OK\n";

require __DIR__ . '/group-quote-future-season-runtime.php';

$isolation_test = __DIR__ . '/group-quote-year-isolation-runtime.php';
$command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($isolation_test);
passthru($command, $isolation_exit);
if ($isolation_exit !== 0) exit($isolation_exit);

$migration_test = __DIR__ . '/quote-activation-migration-runtime.php';
$command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($migration_test);
passthru($command, $migration_exit);
if ($migration_exit !== 0) exit($migration_exit);
