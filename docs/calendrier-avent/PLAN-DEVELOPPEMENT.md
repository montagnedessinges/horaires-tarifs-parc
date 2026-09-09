# Plan de développement — Aperçu puis Calendrier de l’Avent

Ce document fixe l’ordre recommandé pour démarrer le développement après le cadrage fonctionnel du 9 septembre 2026.

## Étape 0 — état actuel

Version publiée au moment du cadrage : **1.14.0**.

La 1.14.0 ne contient pas encore le module Calendrier de l’Avent.

La documentation `docs/calendrier-avent/` décrit le comportement à développer. Elle n’est pas une preuve d’implémentation.

Référentiel d’import courant : cadrage **0.7**, `schema_version = 3`.

## Étape 1 — préparer/fiabiliser le moteur d’aperçu commun

À réaliser avant ou au tout début du module Avent.

Objectifs :

1. conserver `Date à tester` ;
2. ajouter/fiabiliser `Heure à tester` ;
3. charger/rendre un seul shortcode à la fois ;
4. conserver FR / EN / DE ;
5. ne calculer que la langue demandée ;
6. permettre `Mettre à jour l’aperçu` sans recalcul global ;
7. utiliser exactement le même moteur de rendu que le frontend public ;
8. garder les tests existants et ajouter les scénarios Avent.

L’aperçu Avent est indispensable car il doit permettre de simuler décembre alors que le développement et les tests sont réalisés plusieurs mois avant.

## Étape 2 — socle du module Calendrier de l’Avent

Créer le stockage et l’administration pour :

- campagnes séparées par parc/année ;
- 24 journées ;
- teasings sociaux ;
- partenaires ;
- résultats ;
- grand jeu ;
- règlement dynamique ;
- traductions ;
- textes publics configurables ;
- import/réimport.

Aucune donnée annuelle ou propre à MDS/FDS ne doit être codée en dur.

## Étape 3 — shortcodes publics

### `[parc_calendrier_avent]`

Le shortcode ne crée qu’un bloc à l’endroit où il est inséré.

À implémenter :

- titre interne configurable ;
- courte introduction configurable ;
- bouton `Comment participer ?` ;
- panneau d’explication courte ;
- bouton vers l’URL configurable du règlement complet ;
- grille 24 cases ;
- un seul visuel teasing public avant ouverture ;
- jours futurs verrouillés ;
- jour courant mis en avant mais jamais ouvert automatiquement ;
- clic volontaire sur un jour ouvert ;
- détail du jour dans le même bloc ;
- visuel 4:5 central et agrandissable ;
- partenaire, lot, question, réponses ;
- rendu des résultats à J+1 ;
- gagnants et indices visibles dans l’archive après publication du résultat ;
- jour 24 avec jeu quotidien + finale distincte ;
- mode archive après Noël.

### `[parc_reglement_avent]`

Le shortcode affiche uniquement le règlement complet de la campagne active.

Il ne crée pas de page, ne fixe aucune URL et n’impose pas de H1/global CSS.

## Étape 4 — logique des résultats J+1

À implémenter précisément :

- le jeu du jour ne montre jamais sa réponse le jour J ;
- la révélation est par défaut calée sur l’ouverture du jour suivant ;
- les données saisies à l’avance restent serveur ;
- si l’heure de révélation est atteinte sans résultat publié, afficher le texte configurable `tirage non effectué` ;
- la saisie des gagnants ne suffit pas à publier ;
- action explicite `Publier le résultat` ;
- une fois publié : bonne réponse + explication éventuelle + gagnant Facebook + gagnant Instagram + indice éventuel ;
- ces éléments restent visibles en archive.

Prévoir une règle spécifique/configurable pour le résultat du jour 24.

## Étape 5 — mot mystère et finale sécurisée

À implémenter :

- case à cocher `Ce jour contient un indice du mot mystère` ;
- champs lettre + position uniquement visibles dans l’admin lorsque cochée ;
- loupe visible côté public si applicable ;
- lettre + position secrètes le jour J ;
- révélation de la lettre + position avec le résultat à J+1 ;
- champ de saisie du mot final le 24 à la date/heure configurée ;
- validation serveur ;
- limitation raisonnable des tentatives ;
- mot correct → autorisation temporaire signée ;
- seulement après autorisation : exécuter/rendre `grand_jeu_formulaire_shortcode` ;
- ne jamais précharger le formulaire avec CSS/JS caché ;
- ne jamais exposer mot ou shortcode du formulaire dans un payload public avant autorisation.

## Étape 6 — générateurs sociaux

### Avant publication

Générer Facebook/Instagram depuis les données structurées avec :

- partenaire ;
- lot ;
- introductions ;
- question ;
- réponses ;
- règles ;
- rappel automatique du mot mystère si jour avec indice ;
- URL de la page centrale du calendrier sur toutes les publications quotidiennes ;
- hashtags.

Tous les textes par défaut restent modifiables.

Prévoir aperçu + copie manuelle + override.

Aucune connexion de publication automatique à Meta.

### Après tirage

Générer :

- commentaire résultat Facebook ;
- commentaire résultat Instagram ;
- Story résultat ;
- boutons `Copier`.

Utiliser la bonne réponse, l’explication éventuelle, le gagnant du réseau, le partenaire et la relance adaptée au planning.

## Étape 7 — import Excel / CSV de test

Le parseur suit strictement `REFERENTIEL-IMPORT.md` : cadrage **0.7**, `schema_version = 3`.

Fonctions attendues :

- modèle officiel ;
- `.xlsx` principal ;
- CSV accepté au minimum pour une campagne de démonstration/test si fiable ;
- contrôle `schema_version` ;
- contrôle `parc_code` ;
- diff avant écriture ;
- mise à jour intelligente par IDs ;
- conservation des médias manuels ;
- validation complète avant persistance ;
- remplacement complet uniquement après confirmation.

L’import alimente exactement les mêmes champs que l’édition manuelle WordPress.

Après la première version de l’import, préparer une campagne fictive complète de test afin que l’équipe puisse juger le rendu et corriger l’UX avant les vraies données 2026.

## Étape 8 — scénarios d’aperçu obligatoires

Tester au minimum :

- 30 novembre : grille fermée + teasing public ;
- 1er décembre avant ouverture : aucune case accessible ;
- 1er décembre après ouverture : case 1 accessible, pas ouverte automatiquement ;
- clic jour 1 : visuel 4:5 + partenaire + lot + question ;
- 2 décembre après ouverture : cases 1 et 2 accessibles ;
- jour 1 sans tirage publié : texte `tirage non effectué` ;
- jour 1 avec résultat publié : réponse + gagnants ;
- jour avec indice : lettre/position invisibles le jour J ;
- même jour à J+1 après résultat publié : indice visible ;
- 24 décembre avant finale : formulaire absent ;
- mot faux : formulaire absent ;
- mot correct : formulaire rendu côté serveur ;
- après fermeture : formulaire final fermé ;
- archive : anciens jours, gagnants et indices révélés consultables.

## Étape 9 — tests avant release

Avant publication :

- PHP 7.4 / 8.1 / 8.2 / 8.3 ;
- WordPress Plugin Check ;
- sécurité des payloads publics ;
- date/heure et fuseau ;
- MDS/FDS sans fuite croisée ;
- responsive/mobile ;
- accessibilité clavier et agrandissement du visuel ;
- import/réimport ;
- conservation des visuels ;
- QCM / vrai-faux / choix multiples ;
- J+1 et `Publier le résultat` ;
- gagnants Facebook/Instagram différents ;
- indices ;
- brute-force/rate limit du mot ;
- formulaire final non exposé prématurément ;
- règlement dynamique ;
- générateurs sociaux ;
- absence totale de publication automatique Meta.

## Règle de publication

La mise à jour n’est considérée comme installable que lorsque :

1. les tests sont verts ;
2. la nouvelle version est publiée dans GitHub Releases ;
3. le ZIP de production est présent et vérifié.

## Feu vert

Le cadrage fonctionnel est suffisamment avancé pour commencer le développement.

Le chat de développement doit relire **tout** `docs/calendrier-avent/` avant de coder, en particulier `HANDOFF-DEVELOPPEMENT.md`, et ne doit pas réintroduire les anciennes décisions : plusieurs teasings publics ou ouverture automatique du jour courant sont désormais abandonnées.
