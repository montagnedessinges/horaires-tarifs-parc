<?php

$root = dirname(__DIR__);
$file = $root . '/includes/class-parcs-ht-guide-appearance.php';
$source = file_get_contents($file);

$checks = array(
    'guide appearance settings remain available' => strpos($source, 'Apparence des guides') !== false,
    'mobile preview removed from Groups guides panel' => strpos($source, 'htp-guide-live-preview') === false && strpos($source, 'Aperçu mobile') === false,
    'full shortcode preview removed from Groups guides panel' => strpos($source, 'htp-guide-full-preview') === false && strpos($source, 'Aperçu complet du shortcode') === false,
    'settings point users to central preview tab' => strpos($source, 'utilisez l’onglet Aperçu') !== false,
);

$failed = array();
foreach ($checks as $label => $ok) {
    if (!$ok) {
        $failed[] = $label;
    }
}

if ($failed) {
    fwrite(STDERR, "Groups embedded preview regression failed:\n- " . implode("\n- ", $failed) . "\n");
    exit(1);
}

echo "Groups embedded preview regression: OK\n";
