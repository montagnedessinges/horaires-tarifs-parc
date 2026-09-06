# Audit — Shortcode Tarifs groupes — 6 septembre 2026

## Contexte

Après publication de la version 1.13.3, le shortcode `[parc_tarifs_groupes]` a été testé sur le site et signalé comme ne fonctionnant pas, notamment avec une ligne de tarif groupe de type « Senior ».

## Symptôme confirmé

Le shortcode est bien enregistré dans WordPress (`parc_tarifs_groupes` ainsi que les variantes `_fr`, `_en` et `_de`), mais son moteur de rendu pouvait considérer une grille de tarifs groupes existante comme vide.

Le problème ne venait donc pas du nom du shortcode affiché dans l’onglet Shortcodes, mais de la compatibilité de son moteur de lecture avec les données tarifaires déjà enregistrées.

## Cause identifiée

Depuis la refonte des tarifs groupes et l’introduction des identifiants permanents, le rendu public du shortcode exigeait que chaque colonne de tarifs groupes possède déjà un identifiant au format `tariff_col_XXXXXX`.

Or les données historiques de l’extension ont utilisé plusieurs formes successives :

- colonne identifiée simplement par `price` ;
- cellule enregistrée dans `cells['price']` ;
- prix directement enregistré dans `row['price']` ;
- anciennes lignes pouvant ne pas posséder explicitement le champ `enabled`.

Ces données sont valides et peuvent encore exister sur une installation WordPress même si le moteur récent sait ensuite les migrer. Le rendu public ne doit pas dépendre du fait qu’une migration d’administration ait déjà été exécutée.

Conséquence : une grille réellement renseignée pouvait être rejetée par `Parcs_HT_Group_Tariffs` et produire le message d’indisponibilité au lieu d’afficher les tarifs.

## Correction retenue

Le shortcode Tarifs groupes est rendu compatible avec les données actuelles et historiques sans modifier les données WordPress :

1. les colonnes publiques ne sont plus rejetées parce que leur identifiant n’est pas encore un identifiant permanent ;
2. l’identifiant historique `price` est accepté ;
3. une grille historique sans `columns.groups` peut retrouver une colonne tarifaire unique de compatibilité ;
4. pour la première colonne, le moteur cherche successivement la cellule correspondant à l’identifiant courant, `cells['price']`, une éventuelle cellule historique unique, puis `row['price']` ;
5. une ligne historique sans champ `enabled` reste visible par défaut ; une ligne explicitement désactivée reste masquée ;
6. le format actuel avec identifiants permanents reste prioritaire et inchangé.

Cette compatibilité concerne uniquement l’affichage public. Les identifiants permanents restent nécessaires là où ils constituent une identité métier, notamment pour les liaisons du moteur de devis.

## Protection contre les régressions

Un test d’exécution réel a été ajouté : `tests/group-tariff-shortcode-runtime.php`.

Il vérifie :

- l’enregistrement des quatre shortcodes Tarifs groupes ;
- le rendu d’une ancienne grille utilisant `price` ;
- le rendu d’une ligne « Senior » enregistrée avec un prix au format historique ;
- l’absence de faux message « tarifs groupes indisponibles » dans ce cas ;
- le rendu d’une grille actuelle utilisant `tariff_col_...` et `tariff_row_...` ;
- le même test sur la copie nettoyée qui sert à construire le ZIP de production.

Le workflow GitHub Actions exécute désormais ce test avec les contrôles bloquants.

## Règle durable

L’affichage public d’un tarif déjà enregistré ne doit jamais dépendre d’une migration d’administration préalable lorsque les données historiques peuvent être lues sans ambiguïté.

En particulier, les identifiants permanents nécessaires au moteur de devis ne doivent pas devenir une condition artificielle pour simplement afficher le tableau des tarifs groupes.

Une correction d’affichage ne doit pas réécrire silencieusement les tarifs existants du parc.
