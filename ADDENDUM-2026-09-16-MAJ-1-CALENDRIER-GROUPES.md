# Addendum — MAJ 1 technique : calendrier et portail Groupes

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

## Ordre du shortcode / portail Groupes

Dans le shortcode combiné Groupes, l’ordre doit être inversé par rapport au fonctionnement actuel.

L’onglet affiché en premier doit être :

1. **Tarifs groupes** ;
2. **Horaires d’ouverture**.

Au chargement de la page, **Tarifs groupes doit être l’onglet actif par défaut** dès qu’au moins une grille de tarifs groupes est disponible.

Si aucun tarif groupe n’est disponible mais que les horaires Groupes sont disponibles, le module peut ouvrir directement **Horaires d’ouverture**.

Si un seul des deux modules est disponible, ne pas afficher inutilement un onglet vide.

## Gestion de plusieurs années dans le portail Groupes

Le portail Groupes doit pouvoir afficher **plusieurs années en parallèle**, car les horaires et surtout les tarifs groupes peuvent être différents d’une année à l’autre.

Exemple :

- tarifs groupes 2026 visibles ;
- tarifs groupes 2027 visibles ;

=> le visiteur doit pouvoir choisir **2026** ou **2027** et consulter la grille correspondant réellement à cette année.

Même principe pour le calendrier Groupes si plusieurs années d’horaires sont rendues visibles.

### Source des années affichables

Par défaut, le shortcode doit utiliser les années dont l’affichage est autorisé dans la gestion centralisée **Parc & apparence** :

- année active ;
- afficher les horaires sur la page Groupes ;
- afficher les tarifs groupes sur le site ;
- dates automatiques d’activation / désactivation.

Une année masquée dans ces réglages ne doit pas réapparaître simplement parce qu’elle existe dans les données.

### Sélecteur d’année

- si une seule année est disponible pour le module affiché, aucun sélecteur d’année n’est obligatoire ;
- si plusieurs années sont disponibles, afficher un sélecteur / des onglets d’année ;
- le changement d’année doit charger les données propres à cette année sans mélanger les grilles ;
- pour les tarifs groupes, les prix, textes, moyens de paiement, informations et liens propres à l’année sélectionnée doivent suivre cette année ;
- pour les horaires, le calendrier commun doit afficher les données de l’année sélectionnée.

### Année sélectionnée par défaut

Si l’année civile en cours fait partie des années visibles, elle doit être sélectionnée par défaut.

Sinon, sélectionner une année visible de manière déterministe, en privilégiant la plus proche / la plus récente pertinente, sans dépendre de l’ordre accidentel des données.

### Paramétrage du shortcode

Prévoir un paramètre facultatif permettant de limiter explicitement les années rendues par le shortcode lorsqu’une page en a besoin, par exemple une logique du type :

`annees="2026,2027"`

Ce paramètre ne doit **jamais contourner les règles de visibilité** définies dans Parc & apparence : il peut restreindre la liste, mais pas forcer l’affichage d’une année désactivée.

Sans paramètre, le shortcode affiche automatiquement toutes les années Groupes actuellement autorisées.

## Shortcodes / composants

Le shortcode ou module Groupes dédié aux horaires doit appeler le composant calendrier commun, ou une fonction de rendu commune extraite du calendrier principal.

Ne pas recopier la logique d’horaires dans `Parcs_HT_Group_Portal` ou un autre module dédié aux groupes.

Le code doit éviter deux implémentations parallèles qui pourraient diverger dans le temps.

Le shortcode combiné Groupes peut conserver ses deux modules, mais :

- **Tarifs groupes est présenté en premier** ;
- **Horaires d’ouverture est présenté en second** ;
- l’onglet Horaires contient le calendrier commun et non l’ancien tableau spécifique ;
- chaque module sait gérer une ou plusieurs années indépendamment ;
- les années visibles pour les tarifs ne sont pas obligatoirement identiques aux années visibles pour les horaires.

## Compatibilité avec la gestion des années

Le calendrier et les tarifs Groupes doivent respecter les réglages techniques définis dans la MAJ 1 et centralisés dans **Parc & apparence** :

- année active OUI/NON ;
- afficher le calendrier public OUI/NON ;
- afficher les horaires sur la page Groupes OUI/NON ;
- afficher les tarifs groupes sur le site OUI/NON ;
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
- [ ] Le shortcode combiné ouvre **Tarifs groupes** en premier lorsque des tarifs sont disponibles.
- [ ] Aucun onglet vide n’est affiché inutilement.
- [ ] Plusieurs années de tarifs groupes peuvent être visibles simultanément sans mélange de données.
- [ ] Plusieurs années d’horaires Groupes peuvent être visibles simultanément si elles sont activées.
- [ ] Un sélecteur d’année apparaît lorsqu’il y a plusieurs années disponibles.
- [ ] L’année civile courante est sélectionnée par défaut lorsqu’elle est disponible.
- [ ] Le paramètre facultatif `annees` peut restreindre les années du shortcode sans contourner les règles de visibilité.

Ce point appartient entièrement à la **mise à jour 1 technique / fonctionnelle**. La mise à jour 2 pourra uniquement modifier la présentation visuelle de ces composants stabilisés.