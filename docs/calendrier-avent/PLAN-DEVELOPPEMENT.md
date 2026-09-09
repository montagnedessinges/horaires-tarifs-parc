# Plan de développement — Aperçu puis Calendrier de l’Avent

Ce document fixe l’ordre recommandé avant développement.

## Étape 0 — état actuel

Version publiée : **1.14.0**.

Constat confirmé : la 1.14.0 ne contient pas encore le module Calendrier de l’Avent.

L’onglet Aperçu actuel génère côté serveur les aperçus de tous les shortcodes et de toutes les langues avant que le JavaScript ne les organise. Cette architecture fonctionne, mais charge inutilement des aperçus que l’administrateur ne consulte pas.

## Étape 1 — mise à jour dédiée de l’onglet Aperçu

À réaliser avant le module Avent.

Objectifs :

1. conserver le contrôle global `Date à tester` ;
2. ajouter/fiabiliser `Heure à tester` ;
3. remplacer la longue liste verticale d’aperçus par une navigation compacte par shortcode ;
4. charger/rendre un seul shortcode à la fois ;
5. proposer FR / EN / DE dans l’aperçu sélectionné ;
6. ne calculer/charger que la langue demandée lorsqu’on la sélectionne ;
7. permettre `Mettre à jour l’aperçu` sans recalculer inutilement tous les autres shortcodes ;
8. conserver le même moteur de rendu que le frontend public ;
9. garder les tests actuels des shortcodes et ajouter des tests de non-régression du chargement à la demande.

### Interface recommandée

- sélecteur Date à tester ;
- sélecteur Heure à tester ;
- navigation verticale/compacte des shortcodes ;
- sélecteur FR / EN / DE ;
- zone d’aperçu unique ;
- bouton Mettre à jour l’aperçu ;
- contrôle du fond d’aperçu si toujours utile.

### Performance

Ne pas pré-rendre les 12+ shortcodes × 3 langues à chaque affichage de l’onglet.

Préférer un chargement à la demande via une action admin/AJAX sécurisée qui reçoit :

- base du shortcode ;
- langue ;
- date simulée ;
- heure simulée ;
- saison/brouillon concerné si nécessaire.

Le serveur doit vérifier capacité et nonce avant de rendre l’aperçu.

## Étape 2 — développement du module Calendrier de l’Avent

Le module Avent se branche ensuite sur le moteur d’aperçu amélioré.

Éléments principaux :

- stockage des campagnes séparé du calendrier horaires/tarifs ;
- campagne par parc et année ;
- teasings ;
- 24 jours ;
- partenaires ;
- résultats ;
- traductions ;
- grand jeu ;
- import/réimport intelligent ;
- rendu public par shortcode ;
- modale/panneau jour ;
- sécurité serveur des réponses/indices/mot ;
- génération de textes sociaux avant publication ;
- génération après tirage des commentaires résultat Facebook/Instagram et du texte Story ;
- boutons de copie dans l’administration ;
- publication sociale entièrement manuelle, sans connexion de publication automatique à Meta ;
- `explication_reponse_fr` facultatif inséré dans le commentaire résultat lorsqu’il est renseigné ;
- relance de fin de commentaire calculée selon l’ouverture de la prochaine question ou la fin de campagne ;
- admin compacte en grille ;
- mode archive.

### Planning à privilégier

L’interface standard doit utiliser une date/heure principale par contenu. Cette date/heure pilote par défaut l’ouverture sur le site et sert de repère pour la publication sociale manuelle.

Les overrides d’heure d’ouverture ou de planning spécifique Facebook/Instagram restent des options avancées facultatives et ne doivent pas alourdir l’usage courant.

## Étape 3 — import Excel / Google Sheets

Le développement du parseur doit suivre le contrat de `REFERENTIEL-IMPORT.md`.

Fonctions attendues :

- téléchargement d’un modèle officiel ;
- import `.xlsx` principal ;
- CSV facultatif si fiable ;
- lecture `schema_version` ;
- contrôle `parc_code` ;
- diff avant écriture ;
- mise à jour intelligente par IDs ;
- option explicite de remplacement complet ;
- conservation des médias manuels ;
- validation complète avant persistance.

L’import ne doit pas être conçu comme une seconde structure de données : les champs importés doivent alimenter exactement les mêmes champs WordPress que l’édition manuelle.

Avant de coder cette étape, resynchroniser les feuilles Google Sheets `IMPORT plugin - référentiel` avec la version GitHub courante de `REFERENTIEL-IMPORT.md`.

## Étape 4 — contrôles avant release Avent

Avant publication :

- PHP 7.4 / 8.1 / 8.2 / 8.3 ;
- WordPress Plugin Check ;
- tests de sécurité des payloads publics ;
- tests date/heure ;
- tests jours verrouillés/passés ;
- tests MDS/FDS sans fuite croisée ;
- tests import/réimport ;
- tests conservation des visuels ;
- tests réponse QCM / vrai-faux / choix multiples ;
- tests validation serveur du mot ;
- tests archive/révélation ;
- tests aperçu admin avec date/heure simulées ;
- tests mobile/accessibilité de la modale ou du panneau ;
- tests du générateur de post avant publication ;
- tests du commentaire résultat avec et sans `explication_reponse_fr` ;
- tests gagnants Facebook/Instagram différents ;
- tests du texte Story ;
- tests de relance vers une question déjà ouverte, une question future et la clôture du dernier jour ;
- test garantissant qu’aucune action ne publie automatiquement sur Facebook ou Instagram.

## Règle de publication

Une documentation ou un commit de préparation n’est pas une mise à jour du plugin.

Lorsqu’une vraie mise à jour fonctionnelle est lancée, elle n’est considérée terminée que lorsque :

1. les tests sont verts ;
2. la nouvelle version est publiée dans GitHub Releases ;
3. le ZIP de production est présent et vérifié.

Ne pas annoncer la mise à jour comme installable avant ces trois points.
