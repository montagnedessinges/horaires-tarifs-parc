<?php
$root = dirname(__DIR__);
$main = file_get_contents($root . '/horaires-tarifs-parc.php');
$status = file_get_contents($root . '/includes/class-parcs-ht-season-status.php');
$statusJs = file_get_contents($root . '/assets/season-status-admin.js');
$validation = file_get_contents($root . '/assets/admin-validation.js');
$preview = file_get_contents($root . '/assets/admin-preview-enhanced.js');
$policy = file_get_contents($root . '/includes/class-parcs-ht-display-policy.php');

preg_match('/Version:\s*([0-9.]+)/', $main, $versionMatch);
$version = isset($versionMatch[1]) ? $versionMatch[1] : '0.0.0';

$checks = array(
    'version supports yearly activation model' => version_compare($version, '1.15.13', '>='),
    'season status compatibility layer loaded' => strpos($main, 'class-parcs-ht-season-status.php') !== false && strpos($main, 'Parcs_HT_Season_Status::init()') !== false,
    'calendar visibility drives legacy published marker' => strpos($status, "['calendar_visible']") !== false && strpos($status, '$calendar_visible') !== false,
    'calendar activation input is sanitized' => strpos($status, "sanitize_text_field(wp_unslash(\$_POST['settings']['general']['calendar_visible']))") !== false,
    'plain partial save preserves legacy status' => strpos($status, '$new_status = $old_status') !== false,
    'duplicated year starts with every activation disabled' => strpos($status, "'calendar_visible','retail_tariffs_visible','groups_schedule_visible','group_quotes_enabled','group_tariffs_visible'") !== false && strpos($status, "\$season[\$flag] = '0'") !== false,
    'global publish draft UI removed' => strpos($statusJs, 'Publier la saison') === false && strpos($statusJs, 'Remettre en brouillon') === false && strpos($statusJs, 'htp-season-status-banner') === false,
    'season status script no longer rewrites sticky save bar' => strpos($statusJs, '.htp-sticky-save') === false && strpos($statusJs, 'innerHTML') === false,
    'legacy publication checkbox hidden' => strpos($statusJs, 'settings[general][published]') !== false,
    'five yearly controls rendered' => strpos($policy, "'calendar_visible'=>'Afficher le calendrier public'") !== false && strpos($policy, "'groups_schedule_visible'=>'Afficher les horaires aux groupes'") !== false && strpos($policy, "'group_quotes_enabled'=>'Activer les devis groupes'") !== false,
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
echo "Admin season/year activation contract: OK\n";
