<?php
$root = dirname(__DIR__);
$fix = file_get_contents($root . '/includes/class-parcs-ht-tariff-public-fixes.php');
$composer = file_get_contents($root . '/includes/class-parcs-ht-shortcode-composer.php');
$plugin = file_get_contents($root . '/horaires-tarifs-parc.php');

$checks = array(
    'Le correctif réenregistre le shortcode visiteurs' => strpos($fix, "add_shortcode('parc_tableau_tarifs'") !== false,
    'Le correctif réenregistre le shortcode groupes' => strpos($fix, "add_shortcode('parc_tarifs_groupes'") !== false,
    'Les années groupes viennent de la visibilité annuelle existante' => strpos($fix, 'Parcs_HT_Public_Visibility::group_tariff_years()') !== false,
    'Le sélecteur années groupes utilise le moteur d’onglets tarifaires existant' => strpos($fix, 'data-htp-ui-years') !== false && strpos($fix, 'data-htp-ui-year=') !== false && strpos($fix, 'data-htp-ui-year-panel=') !== false,
    'Les tarifs réduits sont forcés sur place' => strpos($fix, "'reduced'") !== false && strpos($fix, "'id'=>'onsite'") !== false && strpos($fix, "row['cells']['online'] = array('value'=>'','old_value'=>'')") !== false,
    'Le libellé groupes devient Tarif générique' => strpos($fix, "'fr'=>'Tarif','en'=>'Price','de'=>'Preis'") !== false,
    'Le composeur 1.16.2 reste inchangé et réutilise les shortcodes enfants' => strpos($composer, "self::child('parc_tableau_tarifs'") !== false && strpos($composer, "self::child('parc_tarifs_groupes'") !== false,
    'Le correctif est initialisé après le composeur' => strpos($plugin, "Parcs_HT_Shortcode_Composer::init();\n    Parcs_HT_Tariff_Public_Fixes::init();") !== false,
    'La version corrective est 1.16.3' => strpos($plugin, 'Version: 1.16.3') !== false && strpos($plugin, "PARCS_HT_VERSION', '1.16.3") !== false,
);

$failed = array();
foreach ($checks as $label => $ok) {
    if (!$ok) $failed[] = $label;
}
if ($failed) {
    fwrite(STDERR, "Échecs correctif tarifs publics 1.16.3 :\n- " . implode("\n- ", $failed) . "\n");
    exit(1);
}
echo "Correctif tarifs publics 1.16.3 : OK\n";
