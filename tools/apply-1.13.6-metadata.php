<?php

$root = dirname(__DIR__);

function replace_once_in_file($path, $search, $replace) {
    $content = file_get_contents($path);
    if ($content === false || strpos($content, $search) === false) {
        fwrite(STDERR, "Missing expected text in {$path}: {$search}\n");
        exit(1);
    }
    $updated = preg_replace('/' . preg_quote($search, '/') . '/', str_replace('$', '\\$', $replace), $content, 1, $count);
    if ($count !== 1 || $updated === null) {
        fwrite(STDERR, "Could not replace expected text in {$path}\n");
        exit(1);
    }
    file_put_contents($path, $updated);
}

$plugin = $root . '/horaires-tarifs-parc.php';
replace_once_in_file($plugin, ' * Version: 1.13.5', ' * Version: 1.13.6');
replace_once_in_file($plugin, "define('PARCS_HT_VERSION', '1.13.5');", "define('PARCS_HT_VERSION', '1.13.6');");

$readme = $root . '/readme.txt';
replace_once_in_file($readme, 'Stable tag: 1.13.5', 'Stable tag: 1.13.6');

$changelog = $root . '/CHANGELOG.md';
$content = file_get_contents($changelog);
if ($content === false) {
    fwrite(STDERR, "Could not read CHANGELOG.md\n");
    exit(1);
}
if (strpos($content, '## 1.13.6') === false) {
    $entry = "## 1.13.6\n" .
        "- Amélioration du shortcode `[parc_tarifs_groupes]` pour la Montagne des Singes 2026 : le bloc reprend désormais la même hiérarchie visuelle que les tarifs classiques, avec les moyens de paiement placés sous le titre et avant les prix.\n" .
        "- Ajout des moyens de paiement groupes confirmés pour la Montagne des Singes : carte bancaire, espèces, chèque, bon de commande / voucher et Chorus Pro. Les ANCV individuels ne sont pas réutilisés automatiquement pour les groupes.\n" .
        "- Ajout après le tableau d'un rappel compact « Paiement et facturation » : règlement sur place, conditions du règlement différé, informations Chorus Pro, facturation selon le nombre réel de participants présents et absence de paiement avant la visite.\n" .
        "- Ajout d'un rappel « Devis et réservation » : réservation obligatoire, devis généré automatiquement et envoyé par e-mail, retour signé avec la mention « Bon pour accord » et présentation du devis imprimé le jour de la visite.\n" .
        "- Les informations spécifiques à la Montagne des Singes sont conditionnées au `site_type=mds` et ne sont pas appliquées automatiquement à la Forêt des Singes.\n" .
        "- Les tarifs restent lus exclusivement depuis la grille Groupes canonique de la saison publique ; aucun tarif ni moyen de paiement individuel n'est copié dans une seconde grille.\n" .
        "- Extension du test d'exécution du shortcode pour vérifier l'ordre moyens de paiement → tarifs → informations → bouton, ainsi que l'absence de fuite des règles MDS vers FDS.\n";
    $content = preg_replace('/^(# Historique des versions\R\R?)/u', '$1' . $entry, $content, 1, $count);
    if ($count !== 1 || $content === null) {
        fwrite(STDERR, "Could not prepend 1.13.6 changelog entry\n");
        exit(1);
    }
    file_put_contents($changelog, $content);
}

echo "1.13.6 metadata prepared\n";
