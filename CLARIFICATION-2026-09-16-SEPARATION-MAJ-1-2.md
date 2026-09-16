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
- `NON` = on n’affiche pas, sauf si une planification automatique définie ci-dessous rend temporairement le contenu visible ;
- **la valeur par défaut doit être NON** lorsqu’un nouveau réglage / une nouvelle saison est créé(e) ;
- il ne doit pas être nécessaire de “publier” une saison ou de la sortir d’un “brouillon” pour que ces interrupteurs fonctionnent ;
- les interrupteurs de visibilité, complétés éventuellement par leur planification automatique, sont la source de vérité pour l’affichage des modules concernés.

Si une ancienne notion `published` / brouillon doit être conservée temporairement pour compatibilité interne ou migration, elle ne doit plus piloter ces affichages publics lorsque les nouveaux interrupteurs existent.

## 1. Recherche admin

Recherche/filtre instantané dans :

- Périodes repères
- Événements
- Exceptions
- Accès temporairement limité

## 2. CSV

Faire évoluer le système CSV pour couvrir proprement les catégories concernées, y compris **Accès temporairement limité**, avec import/export cohérent des données réelles.

## 3. Affichage par année

Clarifier et fiabiliser les interrupteurs par saison :

- afficher le calendrier public ;
- afficher les tarifs visiteurs publics ;
- afficher les horaires sur la page Groupes ;
- afficher les tarifs groupes sur le site ;
- activer les tarifs groupes pour les devis.

Ces commandes sont indépendantes et doivent être **NON par défaut** pour une nouvelle année / saison tant que l’utilisateur ne les active pas explicitement.

Il ne faut pas ajouter une étape séparée “Publier la saison”. L’utilisateur choisit seulement si chaque bloc doit être affiché ou non.

## 3 bis. Planification automatique de la visibilité

Pour chaque interrupteur d’affichage concerné, ajouter deux dates facultatives :

- **Afficher automatiquement à partir du** ;
- **Ne plus afficher à partir du**.

L’objectif est de pouvoir préparer une année à l’avance sans revenir manuellement dans l’administration le jour du changement.

### Priorité du réglage manuel

Le réglage manuel `OUI` est prioritaire :

- si l’utilisateur met **OUI**, le contenu reste affiché ;
- les dates automatiques ne doivent pas repasser un réglage manuel `OUI` à `NON` ;
- l’utilisateur peut donc forcer l’affichage à tout moment.

Le mode automatique intervient lorsque le réglage manuel est sur **NON**.

### Comportement lorsque le réglage manuel est sur NON

- sans date de début : le contenu reste masqué ;
- avec une date de début future : le contenu reste masqué avant cette date ;
- à partir de la date de début, il devient automatiquement visible ;
- si aucune date de fin n’est définie, il reste ensuite visible automatiquement ;
- si une date de fin est définie, il redevient automatiquement masqué **à partir de cette date**.

Exemple pour les tarifs visiteurs 2027 :

- `Afficher les tarifs visiteurs 2027 = NON`
- `Afficher automatiquement à partir du = 01/12/2026`
- `Ne plus afficher à partir du = 01/12/2027`

Résultat :

- jusqu’au 30/11/2026 : masqué ;
- du 01/12/2026 au 30/11/2027 : affiché automatiquement ;
- à partir du 01/12/2027 : masqué automatiquement.

Si l’utilisateur passe manuellement le réglage à `OUI` pendant cette période, **OUI reste prioritaire** et le contenu continue d’être affiché, même après la date de fin, jusqu’à ce que l’utilisateur remette le réglage sur `NON`.

### Validation des dates

- les deux dates sont facultatives ;
- si les deux sont renseignées, la date de fin ne doit pas précéder la date de début ;
- les calculs doivent utiliser le fuseau horaire configuré par l’extension ;
- l’état effectif doit être calculé au rendu / à la lecture et ne doit pas nécessiter un cron pour modifier physiquement la valeur enregistrée de `OUI/NON` ;
- l’administration doit pouvoir indiquer clairement l’état effectif : `Affiché manuellement`, `Planifié`, `Affiché automatiquement`, `Masqué` ou équivalent, sans transformer cette information en nouvelle logique de publication/brouillon.

Cette planification doit être disponible pour les affichages publics concernés. Elle reste indépendante pour chaque bloc : calendrier, tarifs visiteurs, horaires Groupes et tarifs groupes. Pour le moteur de devis, ne l’appliquer que si le développeur confirme qu’une activation planifiée est souhaitable et sans risque ; sinon conserver `Activer les tarifs groupes pour les devis` comme interrupteur manuel indépendant.

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

Quand ce réglage est sur `NON`, cette année ne doit pas apparaître dans ces affichages, sauf pendant une fenêtre de planification automatique active définie au point 3 bis.

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
