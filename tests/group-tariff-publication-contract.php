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
group_publication_check(strpos($settings, 'const STORE_VERSION = 4') !== false, 'group display settings store remains versioned');
group_publication_check(strpos($settings, "'display_from'=>''") !== false && strpos($settings, 'save_display_from') !== false, 'each group year can own a commercial display date');
group_publication_check(strpos($settings, 'public static function is_published') !== false, 'group publication exposes an explicit per-year status');
group_publication_check(strpos($seasons, 'hide_unpublished_groups') !== false && strpos($seasons, "\$tariffs['groups'] = array();") !== false, 'general public tariff rendering cannot expose unpublished group rates');

// Nouveau moteur devis 1.15.18 : état et grille strictement locaux à l'année.
group_publication_check(strpos($quotes, "const STATE_OPTION = 'parcs_ht_group_quote_years_v2'") !== false, 'quote activation owns a dedicated annual state store');
group_publication_check(strpos($quotes, 'const STATE_VERSION = 1') !== false, 'quote annual state is explicitly versioned');
group_publication_check(strpos($quotes, 'public static function quote_enabled_for_year') !== false, 'quote engine exposes a canonical annual activation accessor');
group_publication_check(strpos($quotes, 'public static function binding_for_year') !== false, 'quote engine exposes an annual binding accessor');
group_publication_check(strpos($quotes, 'derive_binding_for_year') !== false, 'missing bindings are rebuilt from the requested year grid');
group_publication_check(strpos($quotes, 'krsort($previous') === false && strpos($quotes, 'ksort($future') === false, 'quote engine contains no previous/future year binding fallback');
group_publication_check(strpos($quotes, 'Parcs_HT_Group_Tariff_Settings::quote_enabled') === false, 'quote availability is independent from commercial group publication');
group_publication_check(strpos($quotes, "(string)(\$season['published']") === false, 'quote availability no longer reads the legacy published status');
group_publication_check(strpos($quotes, 'sync_admin_year_activation') !== false, 'admin saves synchronize only the selected year activation');
group_publication_check(strpos($quotes, "if (\$action !== 'parcs_ht_save') return") !== false, 'unrelated option updates cannot alter quote activation');
group_publication_check(strpos($quotes, "\$year = isset(\$_POST['season_year'])") !== false, 'quote activation synchronization is scoped to the posted season year');
group_publication_check(strpos($quotes, 'legacy_year_evidence') !== false && strpos($quotes, 'migrate_state') !== false, 'legacy quote evidence is consumed only by the one-time state migration');
group_publication_check(strpos($quotes, 'public static function season_for_year') !== false, 'quote engine exposes one canonical per-year pricing accessor');
group_publication_check(strpos($quotes, 'integer_value') !== false, 'participant quantities are normalized as integers server-side');

group_publication_check(strpos($shortcode, 'Parcs_HT_Group_Tariff_Settings::public_year()') !== false, 'dedicated group shortcode selects the commercial group year');
group_publication_check(strpos($shortcode, "\$settings['tariffs'] = isset(\$season['tariffs'])") !== false, 'dedicated group shortcode reads the selected year canonical tariff payload directly');
group_publication_check(strpos($shortcode, 'Parcs_HT_Tariff_Seasons::select_season_tariffs') === false, 'dedicated group shortcode is independent from the general public tariff year');
group_publication_check(strpos($shortcode, "\$rows = isset(\$tariffs['groups'])") !== false, 'group prices still come from canonical tariffs.groups');
group_publication_check(strpos($shortcode, 'Parcs_HT_Group_Tariff_Settings::settings') !== false, 'presentation comes from the dedicated display settings');
group_publication_check(strpos($shortcode, 'is_mds(') === false && strpos($shortcode, 'site_type') === false, 'public renderer is site-agnostic');

group_publication_check(strpos($settings, "'payment_methods'=>array()") !== false && strpos($settings, "'info_blocks'=>array()") !== false, 'generic installations start with editable empty presentation lists');
group_publication_check(strpos($admin, 'Ajouter un moyen de paiement') !== false, 'admin can add payment methods');
group_publication_check(strpos($admin, 'Ajouter un bloc d’information') !== false, 'admin can add information blocks');
group_publication_check(strpos($switch_js, 'Bascule commerciale des tarifs groupes') !== false && strpos($switch_js, 'data-gts-date') !== false, 'group admin exposes a dedicated commercial switch date');
group_publication_check(strpos($switch_admin, "do_action('litespeed_purge_all')") !== false, 'commercial group switch can still purge the page cache');

echo "Group tariff publication contract: OK\n";

require __DIR__ . '/group-quote-future-season-runtime.php';

$isolation_test = __DIR__ . '/group-quote-year-isolation-runtime.php';
$command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($isolation_test);
passthru($command, $isolation_exit);
if ($isolation_exit !== 0) exit($isolation_exit);
