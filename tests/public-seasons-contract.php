<?php

$root = dirname(__DIR__);
$helper = file_get_contents($root . '/includes/class-parcs-ht-public-seasons.php');
$admin = file_get_contents($root . '/assets/public-seasons-admin.js');
$policy = file_get_contents($root . '/includes/class-parcs-ht-display-policy.php');
$policy_admin = file_get_contents($root . '/assets/display-policy-admin.js');
$retail = file_get_contents($root . '/assets/retail-channels.js');
$portal = file_get_contents($root . '/includes/class-parcs-ht-group-portal.php');
$registry = file_get_contents($root . '/includes/class-parcs-ht-shortcode-registry.php');
$main = file_get_contents($root . '/horaires-tarifs-parc.php');

foreach (array($helper,$admin,$policy,$policy_admin,$retail,$portal,$registry,$main) as $content) {
    if (!is_string($content)) {
        fwrite(STDERR, "Unable to read public display implementation.\n");
        exit(1);
    }
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

foreach (array('public_display_from','public_force_display','groups_schedule_visible','En ligne','Sur place','_legacy_retail_1_15_9','htp_group_year') as $marker) {
    if (strpos($policy, $marker) === false) {
        fwrite(STDERR, "Missing display policy marker: {$marker}\n");
        exit(1);
    }
}
foreach (array('Afficher au grand public à partir du','Forçage public','Afficher cette année aux groupes') as $marker) {
    if (strpos($policy_admin, $marker) === false) {
        fwrite(STDERR, "Missing admin publication control: {$marker}\n");
        exit(1);
    }
}
foreach (array('parcs-ht-price-channel-label','En ligne','Sur place') as $marker) {
    if (strpos($retail, $marker) === false) {
        fwrite(STDERR, "Missing retail channel rendering marker: {$marker}\n");
        exit(1);
    }
}
foreach (array('parc_groupes_horaires_tarifs','Horaires d’ouverture','Tarifs groupes','group_schedule_years','begin_group_tariff_year') as $marker) {
    if (strpos($portal . "\n" . $registry, $marker) === false) {
        fwrite(STDERR, "Missing group portal marker: {$marker}\n");
        exit(1);
    }
}

preg_match('/Version:\s*([0-9.]+)/', $main, $version_match);
$version = isset($version_match[1]) ? $version_match[1] : '0.0.0';
if (!version_compare($version, '1.15.9', '>=')) {
    fwrite(STDERR, "Public seasons require plugin version 1.15.9 or later.\n");
    exit(1);
}
foreach (array('class-parcs-ht-public-seasons.php', 'Parcs_HT_Public_Seasons::init()', 'class-parcs-ht-display-policy.php', 'Parcs_HT_Display_Policy::init()', 'class-parcs-ht-group-portal.php', 'Parcs_HT_Group_Portal::init()') as $marker) {
    if (strpos($main, $marker) === false) {
        fwrite(STDERR, "Missing public seasons bootstrap marker: {$marker}\n");
        exit(1);
    }
}

echo "Public seasons and display policy contract OK for {$version}\n";
