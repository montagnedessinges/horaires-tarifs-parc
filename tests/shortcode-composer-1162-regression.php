<?php
$root = dirname(__DIR__);
$composer = file_get_contents($root . '/includes/class-parcs-ht-shortcode-composer.php');
$plugin = file_get_contents($root . '/horaires-tarifs-parc.php');

$checks = array(
    'Le shortcode complet assemble le statut existant' => strpos($composer, "self::child('parc_horaires_aujourdhui'") !== false,
    'Le shortcode complet assemble le calendrier existant' => strpos($composer, "self::child('parc_calendrier'") !== false,
    'Le shortcode complet assemble le tableau tarifs existant' => strpos($composer, "self::child('parc_tableau_tarifs'") !== false,
    'Le shortcode groupes assemble les tarifs groupes existants' => strpos($composer, "self::child('parc_tarifs_groupes'") !== false,
    'Le shortcode groupes assemble le calendrier existant' => substr_count($composer, "self::child('parc_calendrier'") >= 2,
    'Le composeur n’injecte pas de données calendrier parallèles' => strpos($composer, 'ParcsHTPData') === false && strpos($composer, 'syncCalendar') === false,
    'Le composeur est initialisé après les anciens composants' => strpos($plugin, 'Parcs_HT_Tariff_Display::init();\n    Parcs_HT_Shortcode_Composer::init();') !== false,
    'La version corrective est 1.16.2' => strpos($plugin, 'Version: 1.16.2') !== false && strpos($plugin, "PARCS_HT_VERSION', '1.16.2") !== false,
);

$failed = array();
foreach ($checks as $label => $ok) {
    if (!$ok) $failed[] = $label;
}
if ($failed) {
    fwrite(STDERR, "Échecs composeur 1.16.2 :\n- " . implode("\n- ", $failed) . "\n");
    exit(1);
}
echo "Composeur shortcodes 1.16.2 : OK\n";
