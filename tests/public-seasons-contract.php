<?php

$root = dirname(__DIR__);
$helper = file_get_contents($root . '/includes/class-parcs-ht-public-seasons.php');
$admin = file_get_contents($root . '/assets/public-seasons-admin.js');
$main = file_get_contents($root . '/horaires-tarifs-parc.php');

if (!is_string($helper) || !is_string($admin) || !is_string($main)) {
    fwrite(STDERR, "Unable to read public seasons implementation.\n");
    exit(1);
}

$required = array(
    "const QUERY_ARG = 'htp_year'",
    'public_display_until',
    'parc_tableau_tarifs',
    'parc_tarifs_groupes',
    'parc_horaires_tarifs',
    'visible_years',
    'count($years) < 2',
    'archivee',
    'parc_calendrier_avent',
    'parc_reglement_avent',
    "['id']",
    'latest_advent_campaign_id',
);
foreach ($required as $marker) {
    if (strpos($helper, $marker) === false) {
        fwrite(STDERR, "Missing public seasons contract marker: {$marker}\n");
        exit(1);
    }
}

if (strpos($helper, "array('parc_horaires_tarifs','parc_calendrier','parc_tableau_tarifs')") !== false) {
    fwrite(STDERR, "Standalone calendar must keep its native multi-year selector.\n");
    exit(1);
}

foreach (array('Afficher cette année jusqu’au', 'settings[general][public_display_until]') as $marker) {
    if (strpos($admin, $marker) === false) {
        fwrite(STDERR, "Missing admin display-until marker: {$marker}\n");
        exit(1);
    }
}

foreach (array("Version: 1.15.9", "define('PARCS_HT_VERSION', '1.15.9')", 'class-parcs-ht-public-seasons.php', 'Parcs_HT_Public_Seasons::init()') as $marker) {
    if (strpos($main, $marker) === false) {
        fwrite(STDERR, "Missing 1.15.9 bootstrap marker: {$marker}\n");
        exit(1);
    }
}

echo "Public seasons contract OK\n";
