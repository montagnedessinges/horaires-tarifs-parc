<?php
$root = dirname(__DIR__);
$main = file_get_contents($root . '/horaires-tarifs-parc.php');
$layer = file_get_contents($root . '/includes/class-parcs-ht-tariff-seasons.php');
$validation = file_get_contents($root . '/assets/admin-validation.js');
$admin = file_get_contents($root . '/assets/tariff-seasons-admin.js');
$front = file_get_contents($root . '/assets/tariff-seasons-frontend.js');

$checks = array(
    'version supports tariff season layer' => strpos($main, 'Version: 1.9.') !== false,
    'season tariff layer loaded' => strpos($main, 'class-parcs-ht-tariff-seasons.php') !== false,
    'draft stores tariffs inside season' => strpos($layer, "['seasons'][\$year]['tariffs']") !== false,
    'draft does not replace published fallback' => strpos($layer, '!$published') !== false && strpos($layer, "\$old_value['tariffs']") !== false,
    'public selects published season only' => strpos($layer, "['published'] ?? '0'") !== false,
    'column visibility supported' => strpos($layer, "['visible']") !== false && strpos($admin, 'Afficher cette colonne') !== false,
    'offer grouping supported' => strpos($layer, 'offer_group') !== false && strpos($front, 'parcs-ht-offer-group-title') !== false,
    'offer popup supported' => strpos($layer, 'offer_popup') !== false && strpos($layer, 'data-htp-offer-popup') !== false,
    'sales display window supported' => strpos($layer, 'display_from') !== false && strpos($layer, 'display_to') !== false,
    'internal marker french only' => strpos($validation, 'repère interne FR obligatoire') !== false && strpos($validation, 'titre public obligatoire') === false,
    'draft tariff preview present' => strpos($admin, 'Aperçu des tarifs') !== false && strpos($admin, 'reste invisible au public') !== false,
);

$failed = array();
foreach ($checks as $label => $ok) {
    echo ($ok ? '[OK] ' : '[FAIL] ') . $label . PHP_EOL;
    if (!$ok) $failed[] = $label;
}
if ($failed) exit(1);
echo "Tariff seasons contract: OK\n";
