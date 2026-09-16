# Clarification — séparation stricte des mises à jour 1 et 2

Date : 16/09/2026

Cette note complète les documents `ROADMAP-2026-09-16-MAJ-1-2.md` et `ADDENDUM-2026-09-16-MAJ-1-2-GROUPES-CSV.md`.

## Principe général

Les deux mises à jour doivent rester strictement séparées :

- **Mise à jour 1 = technique / fonctionnelle**
- **Mise à jour 2 = visuelle / UX uniquement**

Ne pas déplacer un correctif de logique métier, de stockage, de visibilité, de shortcode ou de CSV dans la mise à jour 2.

---

# Mise à jour 1 — TECHNIQUE / FONCTIONNELLE

Cette mise à jour regroupe tous les changements qui modifient le comportement, la logique, les réglages, les données, les shortcodes ou les imports/exports.

## Règle fondamentale — pas de logique « publié / brouillon » pour l’affichage

Pour les fonctions concernées par cette mise à jour, **ne pas utiliser une logique “publié / brouillon” pour décider de l’affichage public**.

L’objectif est volontairement simple : chaque contenu / année dispose d’un interrupteur de visibilité **Afficher : OUI / NON**.

- `OUI` = on affiche dans l’emplacement concerné ;
- `NON` = on n’affiche pas ;
- **la valeur par défaut doit être NON** lorsqu’un nouveau réglage / une nouvelle saison est créé(e) ;
- il ne doit pas être nécessaire de “publier” une saison ou de la sortir d’un “brouillon” pour que ces interrupteurs fonctionnent ;
- les interrupteurs de visibilité, complétés éventuellement par la planification automatique définie ci-dessous, sont la source de vérité pour l’affichage des modules concernés.

Si une ancienne notion `published` / brouillon doit être conservée temporairement pour compatibilité interne ou migration, elle ne doit plus piloter ces affichages publics lorsque les nouveaux interrupteurs existent.

## 1. Recherche admin

Recherche/filtre instantané dans :

- Périodes repères
- Événements
- Exceptions
- Accès temporairement limité

## 2. CSV

Faire évoluer le système CSV pour couvrir proprement les catégories concernées, y compris **Accès temporairement limité**, avec import/export cohérent des données réelles.

## 3. Activation et affichage par année

### Centraliser toute l’activation dans « Parc & apparence »

La gestion de l’activation des années ne doit plus être dispersée dans plusieurs onglets.

Créer dans l’onglet **Parc & apparence** une zone claire du type **Gestion / activation des années**.

Pour chaque année (ex. 2026, 2027), cette zone centralisée doit contenir les commandes principales :

- **Année active : OUI / NON** ;
- afficher le calendrier public ;
- afficher les tarifs visiteurs publics ;
- afficher les horaires sur la page Groupes ;
- afficher les tarifs groupes sur le site ;
- activer les tarifs groupes pour les devis ;
- les dates automatiques associées lorsqu’elles s’appliquent.

Les autres onglets (Horaires, Tarifs, Groupes, etc.) servent uniquement à **éditer le contenu de l’année sélectionnée**. Ils ne doivent pas répéter les mêmes interrupteurs d’activation / visibilité.

### Rôle de « Année active »

`Année active = NON` est un verrou global de sécurité : l’année peut être préparée dans l’administration, mais ses contenus ne doivent pas être exposés publiquement ni utilisés pour les devis.

`Année active = OUI` autorise ensuite les sous-réglages indépendants (calendrier, tarifs visiteurs, horaires Groupes, tarifs groupes, devis) à fonctionner selon leur propre état.

La valeur par défaut d’une nouvelle année doit être **NON**.

Ce réglage est une **activation technique de l’année**, pas un statut « publié / brouillon ».

### Sous-réglages indépendants

Une fois l’année active, les commandes suivantes restent indépendantes :

- afficher le calendrier public ;
- afficher les tarifs visiteurs publics ;
- afficher les horaires sur la page Groupes ;
- afficher les tarifs groupes sur le site ;
- activer les tarifs groupes pour les devis.

Ces commandes doivent elles aussi être **NON par défaut** pour une nouvelle année / saison tant que l’utilisateur ne les active pas explicitement ou qu’une planification automatique ne les fait pas changer d’état.

Il ne faut pas ajouter une étape séparée “Publier la saison”.

## 3 bis. Planification automatique de la visibilité — dates qui pilotent OUI / NON

Ajouter pour les affichages concernés deux dates facultatives :

- **Afficher automatiquement à partir du** ;
- **Ne plus afficher à partir du**.

Ces dates doivent permettre de préparer une année à l’avance et de faire évoluer automatiquement l’état d’affichage.

La date de fin doit pouvoir arrêter un affichage même si celui-ci est actuellement sur **OUI**.

Le comportement attendu est :

- avant la date de début : état prévu / effectif `NON` si l’affichage n’a pas été activé manuellement ;
- à la date de début : l’affichage concerné passe automatiquement à `OUI` ;
- entre la date de début et la date de fin : il reste sur `OUI` ;
- à la date de fin : il passe automatiquement à `NON`, **même s’il était encore sur OUI juste avant** ;
- sans date de fin : après activation, il reste sur `OUI` jusqu’à une action manuelle ou une autre règle explicitement définie.

Exemple pour 2027 :

- date d’affichage automatique : `01/12/2026` ;
- date de fin d’affichage : `01/12/2027`.

Résultat attendu :

- jusqu’au 30/11/2026 : `NON` ;
- le 01/12/2026 : passage automatique à `OUI` ;
- du 01/12/2026 au 30/11/2027 : `OUI` ;
- le 01/12/2027 : passage automatique à `NON`.

### Interaction avec une action manuelle

L’utilisateur doit toujours pouvoir modifier un interrupteur manuellement entre les échéances.

Cependant, une échéance future programmée garde son rôle :

- si une date de début est encore à venir, elle pourra remettre l’affichage sur `OUI` à cette date ;
- si une date de fin est encore à venir, elle pourra remettre l’affichage sur `NON` à cette date, même si l’utilisateur l’avait laissé sur `OUI`.

L’objectif est que les dates jouent le rôle de **changements d’état programmés**, et non de simples indications visuelles.

### Validation et implémentation

- les deux dates sont facultatives ;
- si les deux sont renseignées, la date de fin ne doit pas précéder la date de début ;
- les calculs doivent utiliser le fuseau horaire configuré par l’extension ;
- l’administration doit montrer clairement l’état actuel et les prochaines transitions programmées ;
- le développeur peut choisir une implémentation robuste par calcul d’état effectif, synchronisation paresseuse ou tâche planifiée WordPress, mais **le résultat visible et administratif doit être équivalent : à la date de début OUI, à la date de fin NON** ;
- aucun système “publié / brouillon” supplémentaire ne doit être réintroduit.

Cette planification doit être gérée depuis **Parc & apparence**, avec l’activation de l’année et les autres commandes de visibilité, afin d’avoir un seul endroit de référence.

## 4. Horaires Groupes : une seule source de données

**Ne jamais créer une seconde grille d’horaires spécifique aux groupes.**

Les horaires sont saisis une seule fois par saison et constituent la source unique de vérité :

- périodes d’ouverture normales ;
- éventuel second créneau ;
- exceptions ;
- fermetures exceptionnelles.

La différence entre public général et Groupes porte uniquement sur la **visibilité** de ces mêmes horaires.

Exemple :

- `Afficher le calendrier public 2027 = NON`
- `Afficher les horaires sur la page Groupes 2027 = OUI`

=> les horaires 2027 n’apparaissent pas sur le calendrier public général mais les **mêmes données horaires 2027** apparaissent dans la partie Groupes.

## 5. Shortcodes Groupes

Prévoir une séparation fonctionnelle claire :

- shortcode horaires Groupes : réutilise les horaires communs de la saison ;
- shortcode tarifs Groupes : affiche uniquement les tarifs groupes dont l’affichage est activé ;
- shortcode combiné Groupes : peut assembler les deux modules sans dupliquer leurs sources de données.

Le shortcode horaires Groupes ne doit jamais dépendre d’une grille d’horaires spécifique aux groupes, puisqu’elle ne doit pas exister.

Le shortcode tarifs Groupes ne doit pas dépendre de l’affichage des horaires Groupes.

## 6. Affichage des tarifs groupes

Quand `Afficher les tarifs groupes sur le site [année] = OUI`, et qu’une grille valide existe, cette année doit être visible :

- dans l’onglet Groupes du tableau public général des tarifs ;
- dans le shortcode / module public dédié aux tarifs groupes.

Quand ce réglage est sur `NON`, cette année ne doit pas apparaître dans ces affichages, sauf si une date d’activation automatique vient de la faire passer sur `OUI`.

Une date de fin programmée doit ensuite pouvoir faire repasser cet affichage sur `NON` automatiquement.

Ce réglage reste indépendant de `Activer les tarifs groupes pour les devis`.

## 7. Cas sans horaires mais avec tarifs

Si les horaires Groupes sont masqués mais les tarifs groupes sont affichés :

- le module tarifs doit continuer à fonctionner ;
- un shortcode combiné ne doit pas bloquer l’affichage des tarifs à cause de l’absence d’horaires.

Tous ces points relèvent de la **mise à jour 1**, car ils concernent la logique et le comportement de l’extension.

---

# Mise à jour 2 — VISUELLE / UX UNIQUEMENT

Cette mise à jour intervient **après** la stabilisation technique de la mise à jour 1.

Elle ne doit pas redéfinir la logique de visibilité ni les sources de données.

Elle concerne uniquement le rendu et l’expérience utilisateur :

- tableau des tarifs plus compact ;
- meilleure adaptation mobile ;
- onglets Individuels / Tarifs réduits / Groupes plus compacts ;
- couleurs distinctes Sur place / En ligne ;
- mise en avant graphique du canal En ligne ;
- cellule En ligne cliquable visuellement propre ;
- réduction des hauteurs, paddings et espaces inutiles ;
- refonte visuelle de la partie Tarifs groupes ;
- refonte visuelle de la partie Horaires / Tarifs groupes ;
- moyens de paiement plus compacts ;
- blocs d’informations Groupes plus lisibles ;
- sélecteurs d’année plus compacts ;
- responsive mobile propre.

Les options fonctionnelles nécessaires au rendu (par exemple masquer une cellule En ligne, texte contextuel modifiable, lien spécifique par tarif) doivent être **créées côté technique dans la mise à jour 1 si leur stockage ou leur logique nécessite une évolution**, puis simplement mises en forme dans la mise à jour 2.

## Règle de développement

Avant de commencer la mise à jour 2, la mise à jour 1 doit avoir stabilisé :

- les données ;
- les réglages ;
- les shortcodes ;
- les règles de visibilité ;
- la planification automatique de visibilité ;
- les années affichables ;
- les imports/exports CSV.

La mise à jour 2 ne doit ensuite modifier que la présentation de ces fonctions stabilisées.
