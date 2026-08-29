<?php
$root = dirname(__DIR__);
$main = file_get_contents($root . '/horaires-tarifs-parc.php');
$status = file_get_contents($root . '/includes/class-parcs-ht-season-status.php');
$statusJs = file_get_contents($root . '/assets/season-status-admin.js');
$validation = file_get_contents($root . '/assets/admin-validation.js');
$preview = file_get_contents($root . '/assets/admin-preview-enhanced.js');

preg_match('/Version:\s*([0-9.]+)/', $main, $versionMatch);
$version = isset($versionMatch[1]) ? $versionMatch[1] : '0.0.0';

$checks = array(
    'version supports season status layer' => version_compare($version, '1.9.4', '>='),
    'season status layer loaded' => strpos($main, 'class-parcs-ht-season-status.php') !== false && strpos($main, 'Parcs_HT_Season_Status::init()') !== false,
    'plain save preserves status' => strpos($status, '$new_status = $old_status') !== false,
    'explicit publish exists' => strpos($status, '$action === \'publish\'') !== false && strpos($statusJs, 'Publier la saison') !== false,
    'explicit draft exists' => strpos($status, '$action === \'draft\'') !== false && strpos($statusJs, 'Remettre en brouillon') !== false,
    'draft save label exists' => strpos($statusJs, 'Enregistrer le brouillon') !== false,
    'published save label exists' => strpos($statusJs, 'Enregistrer les modifications') !== false,
    'legacy publication checkbox hidden' => strpos($statusJs, 'settings[general][published]') !== false,
    'second slot truly optional' => strpos($validation, 'var hasO2=hasValue(o2),hasC2=hasValue(c2)') !== false && strpos($validation, 'if(hasO2!==hasC2)') !== false,
    'second slot values are not compared for equality' => strpos($validation, '(o2&&o2.value)!==(c2&&c2.value)') === false,
    'preview uses one shared display card' => strpos($preview, "card('Affichage du jour',state)") !== false && strpos($preview, "card('Page d’accueil',state)") === false,
    'preview explains closing time' => strpos($preview, 'fermeture du créneau à') !== false && strpos($preview, 'fermeture du dernier créneau à') !== false,
);

$failed = array();
foreach ($checks as $label => $ok) {
    echo ($ok ? '[OK] ' : '[FAIL] ') . $label . PHP_EOL;
    if (!$ok) $failed[] = $label;
}
if ($failed) exit(1);
echo "Admin season/status contract: OK\n";
