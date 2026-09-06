# Mise à jour 1.13.5 — shortcode Tarifs groupes

Version cible : 1.13.5.

## Objectif

Refaire `[parc_tarifs_groupes]` comme un visuel autonome simple, sans créer ni maintenir une deuxième grille de prix.

## Source unique des tarifs

Le shortcode lit directement les mêmes données que le tableau principal :

- `Parcs_HT_Defaults::settings()` ;
- sélection de la saison publique par `Parcs_HT_Tariff_Seasons::select_season_tariffs(..., true)` ;
- données `tariffs.groups` et `tariffs.columns.groups` de cette saison.

Aucun montant n'est stocké dans le moteur du shortcode et aucun tarif n'est copié dans une option parallèle.

## Rendu

Le shortcode construit uniquement son propre bloc visuel « Tarifs groupes » à partir de cette source : lignes actives, colonnes visibles, libellés FR/EN/DE, sous-titres, notes, offres spéciales, styles de lignes, note de réservation groupe et bouton de devis.

Il ne rend pas le tableau complet pour ensuite masquer les sections Individuels/Réduits.

## Publication et devis

Les règles métier existantes de publication annuelle et du moteur de devis restent conservées. Le shortcode ne possède cependant plus de dépendance directe à `Parcs_HT_Group_Tariff_Settings` pour déterminer ou stocker ses prix : il reçoit la même grille publique déjà filtrée que le tableau principal.

## Non-régression

Le test `tests/group-tariff-shortcode-runtime.php` vérifie notamment que :

- les quatre variantes du shortcode sont enregistrées ;
- le sélecteur de saison publique est réellement utilisé ;
- seule la grille Groupes est affichée ;
- une modification du prix dans la source canonique est immédiatement reflétée dans le shortcode ;
- aucune ancienne valeur dupliquée ne subsiste ;
- le moteur du shortcode ne référence plus `Parcs_HT_Group_Tariff_Settings`.

Aucun tarif, horaire, formulaire, réglage ou donnée de parc n'est remplacé par la mise à jour.