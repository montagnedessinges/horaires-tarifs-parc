<?php

$root = getenv('PLUGIN_ROOT') ?: dirname(__DIR__);

function htp_1196_assert($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$main = file_get_contents($root . '/horaires-tarifs-parc.php');
$admin = file_get_contents($root . '/includes/class-parcs-ht-admin.php');
$retail = file_get_contents($root . '/includes/class-parcs-ht-admin-retail-tariffs.php');
$groups = file_get_contents($root . '/includes/class-parcs-ht-admin-group-tariffs.php');
$settings = file_get_contents($root . '/includes/class-parcs-ht-group-tariff-settings.php');
$display = file_get_contents($root . '/includes/class-parcs-ht-tariff-display.php');
$fixes = file_get_contents($root . '/includes/class-parcs-ht-tariff-public-fixes.php');
$shared = file_get_contents($root . '/includes/class-parcs-ht-tariff-shared-1168.php');
$css = file_get_contents($root . '/assets/tariffs-ui.css');

preg_match('/Version:\s*([0-9.]+)/', (string)$main, $version_match);
$version = $version_match[1] ?? '0.0.0';
htp_1196_assert(version_compare($version, '1.19.6', '>='), 'version 1.19.6 absente');

htp_1196_assert(strpos($admin, "'info_blocks'=>array('individual'=>array(),'reduced'=>array())") !== false, 'stockage canonique des blocs visiteurs absent');
htp_1196_assert(strpos($admin, "'color'=>self::color(\$row, 'color', '#006757')") !== false, 'sanitization de la couleur visiteurs absente');

htp_1196_assert(strpos($retail, 'Blocs d’information sous les tarifs') !== false, 'éditeur des blocs visiteurs absent');
htp_1196_assert(strpos($retail, "settings[tariffs][info_blocks]") !== false, 'champs blocs visiteurs absents');
htp_1196_assert(strpos($retail, 'Couleur du repère') !== false && strpos($retail, 'data-htp-quote-sortable') !== false, 'couleur ou ordre des blocs visiteurs absent');

htp_1196_assert(strpos($groups, "group_display[info_blocks]") !== false && strpos($groups, "['color']") !== false, 'couleur des blocs Groupes absente');
htp_1196_assert(strpos($settings, "'color'=>\$color") !== false, 'persistance de la couleur des blocs Groupes absente');

htp_1196_assert(strpos($display, 'private static function info_callouts') !== false, 'renderer partagé des blocs tarifs absent');
htp_1196_assert(strpos($display, 'parcs-ht-tariff-ui__callout') !== false, 'markup des blocs tarifs absent');
htp_1196_assert(strpos($display, "self::info_callouts(\$display['info_blocks']") !== false, 'Groupes ne réutilise pas le renderer de blocs');

htp_1196_assert(strpos($fixes, "info_blocks']['individual") !== false && strpos($fixes, "info_blocks']['reduced") !== false, 'fallback public visiteurs incomplet');
htp_1196_assert(strpos($shared, "info_blocks']['individual") !== false && strpos($shared, "info_blocks']['reduced") !== false, 'renderer public partagé visiteurs incomplet');

htp_1196_assert(strpos($css, '.parcs-ht-tariff-ui__callouts') !== false, 'styles conteneur des blocs absents');
htp_1196_assert(strpos($css, 'color-mix(in srgb,var(--htp-tariff-callout-accent') !== false, 'fond teinté comme le devis absent');
htp_1196_assert(strpos($css, 'border-left:4px solid var(--htp-tariff-callout-accent') !== false, 'repère coloré comme le devis absent');

fwrite(STDOUT, "OK tariff-info-callouts-1196-contract\n");
