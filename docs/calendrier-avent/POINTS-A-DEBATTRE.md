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

Structure validée :

- `heure_ouverture_globale` au niveau CAMPAGNE ;
- `heure_ouverture` facultative au niveau CONTENUS.

À finaliser :

- faut-il que `date_publication` + `heure_publication` sociale puissent automatiquement servir d’heure d’ouverture par défaut, ou garder les deux notions totalement séparées ?

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

Principe validé : bloc RÉSULTATS distinct de CONTENUS.

À finaliser :

- gagnants saisis uniquement dans WordPress ou également importables ;
- affichage ou non des gagnants sur le site archive ;
- durée de conservation des données opérationnelles.

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

Validé : génération structurée + aperçu + bouton Copier le texte + override manuel.

À finaliser :

- modèle Facebook et Instagram identiques ou séparés ;
- longueur cible ;
- emplacement des liens ;
- hashtags globaux vs hashtags du jour ;
- ordre partenaire / lot / intro / question / règles ;
- génération automatique d’un texte de résultat séparé.

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
