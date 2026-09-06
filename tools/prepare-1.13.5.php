<?php

$root = dirname(__DIR__);
$version = '1.13.5';

function htp_replace_once($path, $search, $replace) {
    $content = file_get_contents($path);
    if ($content === false) {
        fwrite(STDERR, "Cannot read {$path}\n");
        exit(1);
    }
    $count = 0;
    $updated = str_replace($search, $replace, $content, $count);
    if ($count !== 1) {
        fwrite(STDERR, "Expected exactly one replacement in {$path}; got {$count}\n");
        exit(1);
    }
    file_put_contents($path, $updated);
}

$main = $root . '/horaires-tarifs-parc.php';
htp_replace_once($main, ' * Version: 1.13.4', ' * Version: ' . $version);
htp_replace_once($main, "define('PARCS_HT_VERSION', '1.13.4');", "define('PARCS_HT_VERSION', '" . $version . "');");

$readme = $root . '/readme.txt';
htp_replace_once($readme, 'Stable tag: 1.13.4', 'Stable tag: ' . $version);

$changelog = $root . '/CHANGELOG.md';
$content = file_get_contents($changelog);
if ($content === false) {
    fwrite(STDERR, "Cannot read CHANGELOG.md\n");
    exit(1);
}
if (strpos($content, "## {$version}\n") === false) {
    $entry = "## 1.13.5\n"
        . "- Refonte du shortcode `[parc_tarifs_groupes]` comme bloc visuel autonome alimenté directement par la même source que le tableau principal.\n"
        . "- Le shortcode lit la saison publique via `Parcs_HT_Tariff_Seasons::select_season_tariffs(..., true)`, puis uniquement `tariffs.groups` et `tariffs.columns.groups`.\n"
        . "- Aucun tarif n’est dupliqué, copié ou stocké dans une option propre au shortcode ; une modification de la grille Groupes canonique est immédiatement reflétée dans le shortcode.\n"
        . "- Le rendu n’utilise plus le système précédent de publication/titre/notice propre au shortcode pour retrouver ses prix et ne dépend plus directement de `Parcs_HT_Group_Tariff_Settings`.\n"
        . "- Le statut de publication annuel et les protections du moteur de devis restent inchangés : le shortcode reçoit la même grille publique déjà filtrée que le tableau général.\n"
        . "- Le bloc autonome reprend les lignes actives, colonnes visibles, traductions FR/EN/DE, sous-titres, notes, offres spéciales, styles de lignes, note groupe et bouton de devis.\n"
        . "- Ajout d’un test d’exécution vérifiant qu’un changement de prix dans la source canonique apparaît immédiatement dans le shortcode et qu’aucune valeur dupliquée ne subsiste.\n"
        . "- Aucun tarif, horaire, formulaire ou réglage de parc n’est réécrit par cette mise à jour.\n";
    $needle = "# Historique des versions\n\n";
    if (strpos($content, $needle) !== 0) {
        fwrite(STDERR, "Unexpected CHANGELOG header\n");
        exit(1);
    }
    $content = $needle . $entry . substr($content, strlen($needle));
    file_put_contents($changelog, $content);
}

$main_after = file_get_contents($main);
$readme_after = file_get_contents($readme);
$changelog_after = file_get_contents($changelog);

$checks = array(
    strpos($main_after, 'Version: 1.13.5') !== false,
    strpos($main_after, "define('PARCS_HT_VERSION', '1.13.5');") !== false,
    strpos($readme_after, 'Stable tag: 1.13.5') !== false,
    strpos($changelog_after, "## 1.13.5\n") !== false,
);
foreach ($checks as $ok) {
    if (!$ok) {
        fwrite(STDERR, "1.13.5 metadata verification failed\n");
        exit(1);
    }
}

echo "1.13.5 metadata prepared\n";
