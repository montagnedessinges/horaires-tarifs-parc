# Addendum — mises à jour 1 et 2 : CSV, affichage 2027 et portail Groupes

Date : 16/09/2026

Dépôt : `montagnedessinges/horaires-tarifs-parc`

Ce document complète `ROADMAP-2026-09-16-MAJ-1-2.md`. Il doit être lu avec cette roadmap par le prochain chat / développeur avant toute modification du code.

---

# 1. Clarification du bloc « Affichage et devis — année »

Le bloc d’administration comporte actuellement quatre commandes :

- Afficher le calendrier [année]
- Afficher les tarifs visiteurs [année]
- Activer les tarifs groupes pour les devis [année]
- Afficher les tarifs groupes sur le site [année]

Ces commandes doivent rester indépendantes.

## Comportement attendu de « Afficher les tarifs groupes sur le site [année] »

Cette commande concerne la **publication publique des tarifs groupes**, pas le devis.

Quand elle est sur **OUI**, et que la grille de tarifs groupes de l’année existe et contient des lignes actives, les tarifs groupes de cette année doivent être visibles dans les deux endroits publics suivants :

1. le tableau public général des tarifs, dans l’onglet **Groupes** ;
2. la page / le shortcode dédié **Groupes — Horaires et tarifs**, dans l’onglet **Tarifs groupes**.

Quand elle est sur **NON**, les tarifs groupes de cette année ne doivent être publiés dans aucun de ces deux affichages publics.

Le réglage **Activer les tarifs groupes pour les devis [année]** reste indépendant : il sert uniquement à rendre la grille de l’année utilisable par le moteur de devis.

## Point vérifié dans le code actuel

Le tableau public général supprime les tarifs groupes d’une année si cette année n’est pas présente dans `Parcs_HT_Group_Tariff_Settings::public_years()`.

Le portail Groupes construit ses années tarifaires depuis `Parcs_HT_Group_Tariff_Settings::published_years()`.

`Parcs_HT_Group_Tariff_Settings::is_published()` tient déjà compte du champ `group_tariffs_visible` lorsque celui-ci existe.

Le développement doit donc garantir et tester explicitement qu’un passage à **OUI** de `group_tariffs_visible` publie bien la même année dans les deux affichages publics, sans nécessiter d’activer les devis.

### Cas 2027 à tester

Avec :

- grille groupes 2027 existante ;
- au moins une ligne groupe 2027 active ;
- `Afficher les tarifs groupes sur le site 2027 = OUI` ;
- `Activer les tarifs groupes pour les devis 2027` indifférent ;

alors 2027 doit être disponible :

- dans l’onglet **Groupes** du tableau public général ;
- dans **Groupes — Horaires et tarifs > Tarifs groupes**.

Si 2026 et 2027 sont tous deux publiés, le sélecteur d’année doit permettre de consulter les deux sans ambiguïté.

---

# 2. Bug / incohérence actuelle : horaires groupes

Le shortcode `parc_groupes_horaires_tarifs` utilise un réglage différent pour déterminer les années d’horaires groupes :

`groups_schedule_visible`

Ce réglage est utilisé par `Parcs_HT_Display_Policy::group_schedule_years()`.

Problème d’interface identifié : ce réglage n’apparaît pas parmi les quatre commandes visibles du bloc « Affichage et devis — année », alors qu’il est déjà pris en compte dans la logique d’enregistrement interne.

Cela peut produire le rendu public :

> Aucun horaire groupe publié pour le moment.

alors que la page Groupes et les tarifs groupes existent.

## Correction attendue

Ajouter une commande explicite et compréhensible dans l’administration :

**Afficher les horaires groupes sur la page groupes [année]**

Cette commande pilote `groups_schedule_visible`.

Elle doit être indépendante :

- du calendrier public général ;
- des tarifs visiteurs ;
- des tarifs groupes publics ;
- des tarifs groupes utilisés par les devis.

Il est recommandé de renommer le titre du bloc pour être plus clair, par exemple :

**Affichage public et devis — 2027**

ou un intitulé équivalent.

Les libellés doivent faire comprendre immédiatement où chaque option agit.

---

# 3. Comportement de la page « Groupes — Horaires et tarifs »

Le comportement actuel ouvre toujours l’onglet **Horaires d’ouverture** en premier, même lorsqu’aucun horaire groupe n’est publié.

Ce comportement doit être corrigé.

## Règles attendues

### Horaires disponibles + tarifs disponibles

Afficher les deux onglets :

- Horaires d’ouverture
- Tarifs groupes

L’onglet actif par défaut peut rester Horaires d’ouverture.

### Aucun horaire disponible + tarifs disponibles

Ne pas ouvrir une vue vide « Aucun horaire groupe publié » comme écran principal.

Le portail doit ouvrir directement **Tarifs groupes**.

Solution recommandée : masquer l’onglet Horaires d’ouverture lorsqu’aucune année d’horaires groupes n’est publiée.

À défaut, il peut être désactivé, mais l’onglet Tarifs groupes doit être actif par défaut.

### Horaires disponibles + aucun tarif disponible

Afficher directement Horaires d’ouverture et masquer / désactiver l’onglet Tarifs groupes.

### Aucun horaire + aucun tarif

Afficher un seul message global clair, sans deux onglets vides.

---

# 4. Mise à jour 2 — refonte visuelle du portail Groupes

La mise à jour 2 ne concerne plus uniquement le tableau public Individuels / Tarifs réduits / Groupes.

Elle doit également revoir le rendu de la page / du shortcode :

`parc_groupes_horaires_tarifs`

## Objectif

Rendre la partie Groupes :

- plus compacte ;
- plus moderne ;
- plus lisible sur mobile ;
- cohérente visuellement avec le nouveau tableau de tarifs général ;
- moins haute ;
- sans gros espaces inutiles.

## Onglets principaux

Les boutons :

- Horaires d’ouverture
- Tarifs groupes

doivent devenir des onglets compacts, sur une seule ligne autant que possible.

L’état actif doit être immédiatement identifiable.

## Sélecteur d’année

S’il existe plusieurs années publiques, afficher des boutons d’année compacts, par exemple 2026 / 2027.

Éviter de multiplier les grosses rangées de boutons.

## Tarifs groupes

Le tableau groupes doit reprendre la philosophie de la refonte tarifaire de la mise à jour 2 :

- lignes compactes ;
- nom à gauche ;
- prix très lisible à droite ;
- détails secondaires discrets ;
- séparateurs légers ;
- hauteur minimale ;
- aucun gros bloc inutile.

Les moyens de paiement doivent rester présents lorsqu’ils sont activés, mais être beaucoup plus compacts.

Les blocs d’information « Paiement et facturation » / « Devis et réservation » doivent être rendus plus lisibles et moins lourds visuellement. Ils peuvent rester en deux colonnes sur grand écran et passer proprement sur mobile.

Le bouton **Faire une demande de devis** doit rester clairement identifiable.

## Horaires groupes

Le tableau des horaires doit également être responsive et compact :

- dates ;
- jours ;
- horaires ;
- exceptions.

Éviter un tableau qui nécessite un scroll horizontal de toute la page.

Sur petit écran, autoriser une présentation par lignes/cartes très compactes si nécessaire plutôt qu’un tableau illisible.

---

# 5. Mise à jour 1 — le CSV doit finalement être inclus

La première roadmap indiquait de ne pas modifier le CSV dans la mise à jour 1. Cette décision est remplacée par le présent addendum.

## Nouveau périmètre CSV

La mise à jour 1 doit couvrir **recherche + CSV** pour :

- Périodes repères ;
- Événements ;
- Exceptions ;
- Accès temporairement limité.

Le CSV actuel sait déjà importer notamment :

- `exception_hours`
- `exception_closed`
- `period`
- `school_holiday`
- `event`

Il faut préserver cette compatibilité.

## Accès temporairement limité

Ajouter au format CSV un type dédié au module d’accès limité, avec un nom technique stable à définir lors de l’implémentation, par exemple :

`limited_access`

ou `domain_rule`.

Ne pas choisir deux noms concurrents : le développeur doit sélectionner un identifiant définitif et le documenter.

Le CSV doit permettre de restituer les champs réellement utilisés par ce module (dates, état actif, textes/labels utiles, paramètres nécessaires au comportement public), sans perdre les traductions si le module en possède.

## Import + export

La mise à jour ne doit pas se limiter au téléchargement d’un modèle.

Prévoir :

- import CSV ;
- export CSV des données réellement enregistrées pour la saison ;
- modèle CSV documenté ;
- validation avant écrasement des catégories concernées ;
- message clair en cas d’erreur de ligne ;
- conservation des catégories absentes du fichier importé, conformément au comportement actuel.

L’export doit permettre de réimporter le fichier sans perte fonctionnelle des champs pris en charge.

## Interface

Le développeur peut conserver un outil CSV global « Horaires & calendrier » si cela reste clair, mais les quatre sections concernées doivent rendre évident qu’elles sont couvertes par l’import/export.

Une solution possible est d’ajouter dans chaque section un lien/bouton vers l’outil CSV avec indication du type correspondant, sans dupliquer quatre moteurs différents.

La priorité est la simplicité de maintenance et l’absence de duplication de logique.

---

# 6. Critères d’acceptation supplémentaires

## Publication groupes

- [ ] `Afficher les tarifs groupes sur le site 2027 = OUI` rend 2027 visible dans l’onglet Groupes du tableau général si la grille 2027 existe.
- [ ] Le même réglage rend 2027 visible dans le portail Groupes > Tarifs groupes.
- [ ] Le réglage des devis peut être NON sans empêcher l’affichage public des tarifs groupes.
- [ ] Le réglage des devis peut être OUI sans forcer l’affichage public si l’affichage groupes est NON.
- [ ] Plusieurs années publiées sont sélectionnables correctement.

## Horaires groupes

- [ ] Un contrôle admin visible permet de régler `groups_schedule_visible` pour chaque année.
- [ ] Ce contrôle n’affecte pas le calendrier public général.
- [ ] Si aucun horaire groupe n’est publié mais que les tarifs le sont, la page ouvre directement Tarifs groupes.
- [ ] Aucun onglet vide inutile n’est présenté comme vue principale.

## Refonte visuelle Groupes

- [ ] Onglets principaux compacts sur mobile.
- [ ] Années compactes.
- [ ] Tarifs groupes nettement plus courts verticalement.
- [ ] Moyens de paiement compacts.
- [ ] Blocs d’information lisibles et responsive.
- [ ] Horaires groupes lisibles à environ 320 px sans scroll horizontal de toute la page.

## CSV

- [ ] Périodes repères exportables et importables.
- [ ] Événements exportables et importables.
- [ ] Exceptions exportables et importables.
- [ ] Accès temporairement limité exportable et importable.
- [ ] Un export peut être réimporté sans perte des champs pris en charge.
- [ ] Les types CSV existants restent compatibles.
- [ ] Les catégories absentes d’un import ne sont pas écrasées.

---

# 7. Fichiers probables à auditer / modifier

Mise à jour 1 :

- `includes/class-parcs-ht-schedule-csv.php`
- `includes/class-parcs-ht-admin.php`
- `assets/admin.js`
- `assets/admin.css`
- tests CSV / admin

Mise à jour 2 / publication groupes :

- `includes/class-parcs-ht-display-policy.php`
- `includes/class-parcs-ht-group-tariff-settings.php`
- `includes/class-parcs-ht-group-portal.php`
- `includes/class-parcs-ht-group-tariffs.php`
- `includes/class-parcs-ht-shortcodes.php`
- `assets/frontend.css`
- éventuellement `assets/frontend.js`
- tests de visibilité, tarifs groupes et portail groupes

Avant développement, vérifier la version réellement présente sur `main` et ne pas repartir d’une ancienne release.