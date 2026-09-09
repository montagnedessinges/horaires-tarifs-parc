# Points à débattre avant développement du Calendrier de l’Avent

Ce fichier sert de zone de débat fonctionnel. Le chat spécialisé Calendrier de l’Avent peut proposer des modifications ici avant que le chat de développement touche au code.

## 1. Langues du site

Décision actuelle :

- réseaux sociaux principalement en français ;
- site techniquement compatible FR / EN / DE ;
- français obligatoire ;
- traductions EN/DE facultatives via `TRADUCTIONS`.

À finaliser : comportement exact si une traduction manque.

Options déjà envisagées :

- afficher le français avec une courte mention indiquant que le concours se déroule en français sur les réseaux ;
- masquer le contenu non traduit ;
- exiger toutes les traductions avant publication.

## 2. Heure d’ouverture des jours

Décision validée : le fonctionnement standard doit rester simple.

- `date_publication` + `heure_publication` constituent la date/heure principale du contenu ;
- par défaut, cette même date/heure ouvre la case ou le teasing sur le site ;
- elle sert aussi de repère pour la publication manuelle sur les réseaux sociaux ;
- le plugin ne publie rien automatiquement sur Facebook ou Instagram ;
- les horaires distincts par réseau ou l’override d’ouverture ne sont que des options avancées et facultatives, à masquer dans l’interface standard tant qu’elles ne sont pas utilisées.

Aucun débat fonctionnel restant sur le principe général. Les détails d’interface avancée pourront être décidés pendant le développement.

## 3. Fermeture du grand jeu

La fermeture doit être configurable et peut être postérieure au 28 décembre.

À finaliser pour chaque campagne : date/heure réelles. Ne jamais coder une date fixe dans l’extension.

## 4. Formulaire final

Principe validé : le mot correct déverrouille le formulaire final côté serveur.

À finaliser :

- champs obligatoires du formulaire final ;
- téléphone facultatif ou obligatoire ;
- adresse nécessaire ou non ;
- newsletter facultative ;
- politique de doublons (une participation par e-mail ou plusieurs) ;
- texte RGPD exact.

## 5. Résultats quotidiens

Principes validés :

- bloc RÉSULTATS distinct de CONTENUS ;
- gagnants Facebook et Instagram séparés ;
- `explication_reponse_fr` facultatif, utilisé principalement pour expliquer pourquoi la réponse est correcte dans le commentaire de résultat ;
- génération automatique du commentaire résultat Facebook ;
- génération automatique du commentaire résultat Instagram ;
- génération automatique d’un texte court pour Story ;
- aperçu + bouton `Copier` pour chaque sortie ;
- publication toujours manuelle par l’équipe du parc ;
- relance automatique adaptée au planning : nouvelle question déjà en ligne, prochain rendez-vous à venir ou clôture si dernier jour.

À finaliser :

- gagnants saisis uniquement dans WordPress ou également importables ;
- affichage ou non des gagnants sur le site archive ;
- durée de conservation des données opérationnelles ;
- formulation exacte des modèles de résultat et de Story, tout en conservant des overrides manuels.

## 6. Teasings

Nombre libre et piloté par les données.

À finaliser :

- certains teasings doivent-ils rester visibles une fois le calendrier ouvert ?
- peut-on avoir des teasings/rappels pendant la période du 1er au 24 ?
- affichage site : un teasing actif à la fois ou plusieurs contenus passés consultables ?

## 7. Visuels

Principes validés :

- URL externe ou média WordPress ;
- remplacement manuel possible ;
- réimport vide = conservation du média manuel ;
- placeholder au bon ratio dans l’admin si image manquante.

À finaliser :

- visuel d’attente public autorisé ou publication bloquée si image absente ;
- politique exacte pour les URL externes ;
- besoin ou non d’une protection plus stricte des médias futurs que la simple non-exposition de leur URL par le module.

## 8. Apparence de la grille publique

Principes : 24 cases, mobile prioritaire, jours futurs verrouillés.

À débattre :

- nombre de colonnes desktop/tablette/mobile ;
- style des cases verrouillées ;
- présence d’une miniature sur la case ou seulement du numéro ;
- animation d’ouverture ;
- modale centrale ou panneau latéral sur desktop ;
- comportement mobile exact.

## 9. Administration

Principe validé : pas de 24 formulaires à la suite.

Base proposée :

- Campagne ;
- Teasings ;
- Calendrier en grille ;
- Grand jeu ;
- Partenaires ;
- Résultats ;
- Import/export.

À débattre :

- panneau latéral vs modale vs écran dédié pour éditer un jour ;
- badges/couleurs de statut ;
- actions rapides depuis chaque case ;
- duplication d’un jour ;
- réorganisation éventuelle des teasings.

## 10. Générateur de publications sociales

Décisions validées :

- l’extension ne publie jamais automatiquement vers Facebook ou Instagram ;
- elle prépare les textes dans l’administration pour copie manuelle ;
- génération structurée du post avant publication + aperçu + bouton `Copier le texte` + override manuel ;
- après tirage, génération séparée du commentaire résultat Facebook, du commentaire résultat Instagram et d’un texte Story ;
- `explication_reponse_fr` vient juste après la bonne réponse lorsqu’il est renseigné ;
- le texte résultat peut ensuite mentionner les gagnants, remercier le partenaire, rappeler les conditions utiles puis relancer vers la suite du calendrier ;
- si la prochaine question est déjà ouverte, le générateur invite à participer immédiatement ; sinon il annonce le prochain rendez-vous ; après le dernier jour, il utilise une clôture adaptée.

À finaliser :

- modèle Facebook et Instagram identiques ou légèrement adaptés ;
- longueur cible exacte ;
- emplacement des liens ;
- hashtags globaux vs hashtags du jour ;
- ordre final partenaire / lot / intro / question / règles pour le post initial ;
- style exact de la Story résultat.

## 11. Import

Orientation : `.xlsx` principal, CSV éventuellement en complément.

À finaliser :

- bibliothèque PHP retenue et impact sur le poids du plugin ;
- prise en charge de plusieurs feuilles physiques ou d’un fichier plat ;
- export inverse de la campagne WordPress vers XLSX ;
- gestion des traductions dans une feuille séparée ;
- niveau de tolérance aux colonnes inconnues.

## 12. Aperçu commun du plugin

Avant l’Avent, une mise à jour dédiée de l’onglet Aperçu est recommandée.

Décision validée :

- garder `Date à tester` ;
- ajouter/tenir compte de `Heure à tester` ;
- afficher le vrai rendu du shortcode à cette date/heure ;
- ne pas ouvrir automatiquement l’éditeur du jour ;
- charger un seul shortcode à la fois et une langue à la fois pour éviter le pré-rendu complet actuel.

Le module Avent devra simplement se brancher sur ce moteur commun.
