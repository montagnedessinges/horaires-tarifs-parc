<?php
$root = dirname(__DIR__);
$visibility = file_get_contents($root . '/includes/class-parcs-ht-public-visibility.php');
$fixes = file_get_contents($root . '/includes/class-parcs-ht-tariff-public-fixes.php');
$composer = file_get_contents($root . '/includes/class-parcs-ht-shortcode-composer.php');

$checks = array(
    'La disponibilité groupes ne dépend plus de la visibilité des anciennes colonnes' => strpos($visibility, 'group_tariff_grid_ready') !== false && strpos($visibility, 'Parcs_HT_Group_Tariff_Settings::has_grid($year)') === false,
    'Une ligne groupe avec une cellule tarifaire suffit à rendre l’année disponible' => strpos($visibility, "trim((string)(\$cell['value'] ?? '')) !== ''") !== false,
    'Les anciennes lignes groupes sans enabled restent actives' => strpos($fixes, "if (!array_key_exists('enabled', \$row)) \$row['enabled'] = '1';") !== false,
    'Le rendu groupes utilise une colonne canonique Tarif' => strpos($fixes, "'id'=>'price'") !== false && strpos($fixes, "'fr'=>'Tarif'") !== false,
    'Le rendu groupes est préparé directement pour chaque année' => strpos($fixes, 'render_group_year') !== false && strpos($fixes, 'normalize_group_tariffs') !== false,
    'Le portail groupes continue de synchroniser années tarifs et horaires' => strpos($composer, 'group_portal_years') !== false && strpos($composer, 'data-group-tariff-years') !== false,
);

$failed = array();
foreach ($checks as $label => $ok) if (!$ok) $failed[] = $label;
if ($failed) {
    fwrite(STDERR, "Échecs tarifs groupes 1.16.5 :\n- " . implode("\n- ", $failed) . "\n");
    exit(1);
}
echo "Tarifs groupes années futures 1.16.5 : OK\n";
