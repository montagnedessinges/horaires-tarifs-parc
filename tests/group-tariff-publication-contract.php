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

// Le statut de publication groupes reste une règle métier commune : il protège
// le tableau public général et le calcul des devis. Il ne constitue pas une copie des prix.
group_publication_check(strpos($settings, "const OPTION = 'parcs_ht_group_tariff_settings'") !== false, 'group publication keeps its own status/settings store');
group_publication_check(strpos($settings, 'public static function is_published') !== false, 'group publication exposes an explicit per-year status');
group_publication_check(strpos($settings, "(string)(\$season['published'] ?? '0') !== '1'") !== false, 'group tariffs can only publish inside a published season');
group_publication_check(strpos($seasons, 'hide_unpublished_groups') !== false && strpos($seasons, "\$tariffs['groups'] = array();") !== false, 'general public tariff rendering cannot expose unpublished group rates');
group_publication_check(strpos($quotes, 'Parcs_HT_Group_Tariff_Settings::is_published($year)') !== false, 'quote calculation requires group rates published for the visit year');

// Le shortcode autonome ne possède plus sa propre source de tarifs : il demande
// les réglages canoniques puis passe par exactement le même sélecteur de saison publique.
group_publication_check(strpos($shortcode, 'Parcs_HT_Defaults::settings()') !== false, 'dedicated group shortcode reads canonical tariff settings');
group_publication_check(strpos($shortcode, 'Parcs_HT_Tariff_Seasons::select_season_tariffs') !== false, 'dedicated group shortcode uses the public tariff season selector');
group_publication_check(strpos($shortcode, 'Parcs_HT_Group_Tariff_Settings') === false, 'dedicated group shortcode has no separate tariff-settings dependency');
group_publication_check(strpos($shortcode, "['tariffs']") !== false && strpos($shortcode, "['groups']") !== false, 'dedicated group shortcode reads the canonical groups tariff data');
group_publication_check(strpos($shortcode, "do_shortcode('[parc_tableau_tarifs") === false && strpos($shortcode, 'parcs-ht-group-tariffs-source') === false, 'dedicated group shortcode renders its own visual instead of hiding the full tariff table');

group_publication_check(strpos($admin, 'Publier les tarifs groupes de cette année') !== false, 'group publication remains editable in Groupes → Tarifs');
group_publication_check(strpos($admin, 'Un devis ne sera jamais calculé avec les tarifs d’une autre année.') !== false, 'admin explicitly documents no cross-year quote fallback');

echo "Group tariff publication contract: OK\n";
