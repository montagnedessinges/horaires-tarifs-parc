# Addendum — MAJ 1 technique : calendrier Groupes

Date : 16/09/2026

Ce document complète `ROADMAP-2026-09-16-MAJ-1-2.md`, `ADDENDUM-2026-09-16-MAJ-1-2-GROUPES-CSV.md` et `CLARIFICATION-2026-09-16-SEPARATION-MAJ-1-2.md`.

## Principe

La partie **Groupes > Horaires d’ouverture** ne doit plus générer ni maintenir un tableau d’horaires spécifique du type `Dates / Jours / Horaires`.

Elle doit réutiliser **le même composant / moteur de calendrier que la partie publique principale “Horaires & Tarifs”**, avec exactement la même source de données horaires.

Il ne doit exister qu’une seule source de vérité pour les horaires d’une saison :

- périodes normales ;
- créneau 1 ;
- créneau 2 éventuel ;
- exceptions ;
- fermetures exceptionnelles ;
- autres informations déjà utilisées par le calendrier principal.

## Différence entre affichage public et affichage Groupes

La différence ne porte que sur la visibilité.

Exemple :

- `Afficher le calendrier public 2027 = NON`
- `Afficher les horaires sur la page Groupes 2027 = OUI`

Résultat attendu :

- le calendrier 2027 n’apparaît pas dans la partie publique générale ;
- le **même calendrier 2027**, alimenté par les mêmes données, apparaît dans la partie Groupes.

Inversement, si l’affichage Groupes est sur NON, le calendrier peut rester visible dans la partie publique générale si son propre réglage est sur OUI.

## Shortcodes / composants

Le shortcode ou module Groupes dédié aux horaires doit appeler le composant calendrier commun, ou une fonction de rendu commune extraite du calendrier principal.

Ne pas recopier la logique d’horaires dans `Parcs_HT_Group_Portal` ou un autre module dédié aux groupes.

Le code doit éviter deux implémentations parallèles qui pourraient diverger dans le temps.

Le shortcode combiné Groupes `Horaires d’ouverture / Tarifs groupes` peut conserver ses onglets, mais l’onglet Horaires doit contenir le calendrier commun et non l’ancien tableau spécifique.

## Compatibilité avec la gestion des années

Le calendrier Groupes doit respecter les réglages techniques définis dans la MAJ 1 et centralisés dans **Parc & apparence** :

- année active OUI/NON ;
- afficher le calendrier public OUI/NON ;
- afficher les horaires sur la page Groupes OUI/NON ;
- dates automatiques d’activation et de désactivation.

L’affichage Groupes reste indépendant de l’affichage public général.

## Critères d’acceptation

- [ ] Il n’existe plus de rendu public Groupes spécifique sous forme de tableau `Dates / Jours / Horaires`.
- [ ] Le module Groupes utilise le même composant calendrier que la partie principale.
- [ ] Les données horaires ne sont saisies qu’une seule fois.
- [ ] Une modification des horaires est visible partout où le calendrier est activé, sans double saisie.
- [ ] L’affichage public général et l’affichage Groupes peuvent être activés ou désactivés indépendamment.
- [ ] Les exceptions et fermetures du calendrier principal apparaissent également dans le calendrier Groupes.
- [ ] Les deux créneaux horaires éventuels restent pris en charge.
- [ ] La logique d’année active et les dates automatiques sont respectées.

Ce point appartient entièrement à la **mise à jour 1 technique / fonctionnelle**. La mise à jour 2 pourra uniquement modifier la présentation visuelle du calendrier commun.