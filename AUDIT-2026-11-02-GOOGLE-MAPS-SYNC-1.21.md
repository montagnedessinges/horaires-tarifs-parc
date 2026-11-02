# Audit — synchronisation des horaires vers Google Maps (cadrage 1.21)

Date : 02/11/2026  
Dépôt audité : `montagnedessinges/horaires-tarifs-parc`  
Branche d’audit : `audit/google-maps-hours-sync-1.21`  
Statut : audit documentaire et technique ; aucun code de synchronisation n’est livré dans ce document.

## Résumé décisionnel

La fonctionnalité est réalisable avec la **Google Business Profile API — Business Information API**, qui permet de lire et modifier les horaires d’un établissement. Elle ne fonctionne pas avec une simple clé API : les appels de modification exigent un projet Google Cloud ayant obtenu l’accès à ces API, une connexion OAuth 2.0 et le consentement d’un utilisateur qui a accès à la fiche concernée.

Le calendrier de Gestion du parc peut rester la source canonique. Toutefois, Google ne possède pas un modèle équivalent aux saisons et plages de dates de l’extension : ses horaires réguliers décrivent une semaine type, tandis que ses horaires spéciaux décrivent des dates précises. Une synchronisation « calendrier complet → Google » doit donc calculer une semaine de base et des exceptions datées, avec une stratégie de fenêtre temporelle, de contrôle et de relecture. La fermeture saisonnière ne peut pas être représentée par une unique exception couvrant plusieurs semaines dans l’API documentée.

**Recommandation :** commencer par un écran de connexion, de sélection de fiche et de prévisualisation. N’écrire vers Google qu’après validation du diff par l’administrateur. Garder une option d’envoi automatique désactivée par défaut jusqu’à validation du fonctionnement en production.

### Décision fonctionnelle confirmée le 02/11/2026

La prochaine version doit comporter deux outils visibles et distincts :

1. Un bouton **« Connecter Google »** qui lance OAuth, puis permet de sélectionner la fiche Google autorisée. L’écran affiche ensuite le compte et la fiche connectés, avec les actions de déconnexion / reconnexion.
2. Un **convertisseur calendrier → horaires Google** qui lit le calendrier publié de cette installation, le transforme au format des horaires réguliers et spéciaux Google, et présente le résultat (semaine de base, exceptions datées et diff) avant tout envoi.

La conversion et l’envoi réel doivent rester deux actions distinctes : **« Convertir / prévisualiser »** ne modifie pas la fiche Google ; **« Synchroniser maintenant »** envoie le résultat après confirmation. Une connexion réussie ne déclenche donc pas d’écriture automatique.

### Emplacement dans l’administration

La synchronisation doit apparaître comme un onglet **« Google Maps »** dans l’écran existant **Gestion du parc → Horaires & calendrier**. Ce placement la rattache au calendrier source et évite de la confondre avec l’écran **IA & Google**, qui gère les données structurées / SEO et ne synchronise pas la fiche Business Profile. Le titre de l’onglet reste court ; son en-tête peut être **« Synchronisation des horaires Google Maps »**.

## Éléments vérifiés dans le dépôt

- Le dépôt canonique partagé par les deux parcs est `montagnedessinges/horaires-tarifs-parc`, branche `main`.
- Le fichier principal actuellement présent sur `main` annonce la version **1.20.30**.
- Le moteur `Parcs_HT_Schedule::resolve_day()` résout l’état d’une journée selon la priorité des exceptions, puis des horaires habituels ; il sait gérer une ou deux plages horaires dans la journée.
- Les données saisonnières et les exceptions sont propres à chaque installation WordPress. La fonctionnalité doit donc garder une connexion et une fiche Google configurées indépendamment sur les deux sites.
- La classe `Parcs_HT_AI_Google` ajoute du JSON-LD à partir des données existantes, sans appel réseau. L’audit 1.18.0 précise que la synchronisation Google Business Profile était alors hors périmètre. Le nouveau besoin est distinct : modifier réellement les horaires de la fiche d’établissement.
- Les règles du dépôt demandent de préserver les données de chaque parc, de privilégier une seule source de vérité et de vérifier toute évolution sur les deux sites.

## Fonctionnement Google confirmé dans la documentation officielle

1. La ressource d’établissement expose `regularHours` et `specialHours`. La méthode `locations.patch` permet de modifier une fiche avec un `updateMask` qui limite explicitement les champs touchés.
2. `regularHours` est une liste de périodes hebdomadaires (jour et heures d’ouverture/fermeture). Elle ne comporte pas de dates de début et de fin pour exprimer « cette semaine type ne vaut que du 1er mai au 30 juin ».
3. `specialHours` surcharge les horaires réguliers pour des dates données. Une fermeture spéciale peut fermer une journée. Chaque période spéciale documentée est inférieure à 24 heures ; sa date de fin ne peut pas dépasser le lendemain. Une fermeture de saison de plusieurs semaines ne se résume donc pas à une seule période spéciale.
4. La modification requiert le scope OAuth `https://www.googleapis.com/auth/business.manage`.
5. L’accès aux API Business Profile n’est pas public : Google demande une demande d’accès pour le projet Google Cloud. La documentation des prérequis cite notamment une fiche vérifiée et active depuis plus de 60 jours et un site web associé. Il est impossible de confirmer depuis le dépôt si l’entreprise possède déjà un projet approuvé, un quota actif ou des identifiants OAuth.

Ces éléments impliquent un vrai flux OAuth avec choix de la fiche autorisée, et pas un simple champ « API key ».

## Modèle de conversion recommandé

### Horaires réguliers

- Convertir les jours et plages de la semaine de base en `regularHours`.
- Pour les parcs dont les horaires varient selon les saisons, ne pas supposer qu’une semaine-type unique représente toute l’année.
- La prévisualisation doit nommer clairement la saison / semaine de référence retenue, et montrer les différences de saison que Google recevra comme exceptions datées.

### Exceptions datées

- Convertir les horaires exceptionnels en périodes `specialHours` sur leurs dates exactes.
- Convertir une journée fermée en entrée spéciale « fermé » pour cette date.
- Pour deux créneaux dans une journée, vérifier la conversion en plusieurs périodes spéciales sans chevauchement ; Google rejette des horaires invalides ou qui se chevauchent.
- Générer les exceptions à partir du moteur canonique `resolve_day()`, pas d’un second calcul parallèle du calendrier.
- Ne transmettre que l’horizon sélectionné et prévisualisé. Une politique doit être définie (par exemple, saison publiée en cours et suivante), car le format Google ne porte pas les bornes de validité des saisons du plugin.
- Ne pas exporter par défaut toute la basse saison jour après jour avant d’avoir validé le volume, les validations Google et le rendu réel sur les deux fiches.

### Autorité et protection contre les écrasements

- Limiter le `updateMask` aux seuls champs d’horaires que l’extension gère (`regularHours` et, si activé, `specialHours`). Ne jamais envoyer la ressource complète sans masque.
- Laisser les champs d’identité, adresse, téléphone, catégories, photos, description et autres réglages de la fiche intacts.
- Avant écriture, afficher l’état WordPress calculé, l’état actuel récupéré de Google et le diff proposé.
- Après écriture, relire la fiche et afficher la réponse Google. Prévoir un état « en attente / envoyé / erreur / divergence » : une requête acceptée ne prouve pas à elle seule que le rendu public est déjà actualisé.

## Parcours d’administration proposé

1. **Emplacement** : ouvrir l’onglet « Google Maps » dans Gestion du parc → Horaires & calendrier, avec l’en-tête « Synchronisation des horaires Google Maps ».
2. **Prérequis** : afficher l’état d’accès Business Profile, le compte Google connecté et les autorisations disponibles. Expliquer qu’une clé API seule ne suffit pas.
3. **Connexion** : bouton explicite « Connecter Google » qui lance OAuth avec le scope minimal requis ; stocker les jetons de renouvellement de façon protégée et proposer déconnexion / révocation.
4. **Sélection de la fiche** : après connexion, l’administrateur choisit la fiche Google associée à ce WordPress. Ne pas l’associer automatiquement uniquement par nom.
5. **Conversion** : action « Convertir / prévisualiser » qui transforme le calendrier publié en modèle Google, sans écrire sur Google. Le résultat montre la semaine de base, les exceptions datées et le diff.
6. **Validation / test** : si pris en charge par l’endpoint utilisé, appeler `validateOnly=true` puis présenter les erreurs Google avant l’envoi réel.
7. **Envoi manuel initial** : bouton distinct « Synchroniser maintenant », confirmation explicite et journal minimal du résultat.
8. **Automatisation ultérieure** : option distincte, désactivée par défaut, pour lancer la synchronisation après une sauvegarde publiée ; journaliser les changements, éviter les appels identiques et conserver une commande de resynchronisation manuelle.
9. **Retour de Google** : relire l’état de la fiche après l’envoi et comparer aux horaires prévus.

## Points bloquants à confirmer avant développement

- Le projet Google Cloud de l’entreprise est-il déjà approuvé pour les Business Profile APIs ? Son quota Business Information API est-il supérieur à zéro ?
- Les deux fiches Google sont-elles administrées depuis le compte Google qui autorisera l’intégration ?
- Google accepte-t-il, pour les catégories exactes des deux lieux, les horaires et jours spéciaux générés par le calendrier ?
- Quelle règle d’horizon souhaite-t-on synchroniser : calendrier complet, seule saison publiée actuelle, ou saison actuelle et prochaine ?
- Pour les variations saisonnières, faut-il définir comme horaires réguliers le régime qui occupe la plus grande partie de la saison, puis publier les autres jours en exceptions ?
- L’envoi automatique doit-il être proposé dès la première version ou attendre une période d’observation en envoi manuel ?

Le statut d’approbation, les deux comptes/fiches, les catégories, les quotas et le rendu final des fiches **ne peuvent pas être confirmés depuis le seul dépôt**.

## Périmètre conseillé pour 1.21.0

### Inclus dans le premier lot

- Connexion OAuth à Business Profile et stockage indépendant par site.
- Sélection explicite de la fiche autorisée.
- Lecture de la fiche et affichage de ses horaires actuels.
- Conversion en lecture seule du calendrier publié vers un aperçu des horaires réguliers et des exceptions.
- Diff et validations avant envoi.
- Synchronisation manuelle limitée aux horaires, avec rellecture et journal de résultat.
- Tests de contrat pour les semaines types, deux plages quotidiennes, jours fermés, exceptions prioritaires, bornes de saison, saison brouillon et champs hors masque.
- Protection des actions par capacité WordPress, nonce, validation stricte, échappement des sorties et gestion des erreurs OAuth/API.
- Vérification séparée sur la configuration de la Montagne des Singes et celle de la Forêt des Singes.

### À différer

- Synchronisation automatique à chaque sauvegarde avant que les deux fiches aient été validées avec la synchronisation manuelle.
- Mise à jour d’autres champs de la fiche Google.
- Toute publication de saisons brouillon ou non visibles publiquement.
- Synchronisation depuis un compte ou un projet Google partagé avec des clients tiers.

## Plan de vérification avant release

- **Conversion** : plages hebdomadaires, journées avec deux créneaux, exceptions ouvertes, exceptions fermées, fermetures saisonnières, journée hors saison et transitions entre saisons.
- **Priorité** : vérifier que l’exception gagnante dans `resolve_day()` est celle envoyée à Google ; aucune règle de priorité parallèle.
- **Données** : confirmer que 2026/2027 et les exceptions propres à chaque parc sont converties indépendamment ; aucune donnée n’est supprimée à l’installation ou à la mise à jour.
- **API** : scope, fiche sans permission, jeton révoqué/expiré, `validateOnly`, erreurs 400/401/403/429/5xx, répétition d’une synchronisation identique et réponse partielle.
- **Masque** : vérifier que les changements ne portent que sur les horaires ; relire les champs de profil qui doivent rester inchangés.
- **Interface** : différencier aperçu, validation et écriture réelle ; garder l’état d’envoi et la dernière erreur après rechargement.
- **Exploitation** : ne pas appeler l’API publique au chargement des pages ; ne synchroniser qu’après une action administrateur ou, plus tard, un événement de publication contrôlé.
- **Publication** : mise à jour du changelog, montée à 1.21.0 lorsque le développement sera validé, exécution de la CI et génération du ZIP de release. Ce document seul n’est pas une release.

## Sources

### Dépôt

- [Moteur des horaires](https://github.com/montagnedessinges/horaires-tarifs-parc/blob/main/includes/class-parcs-ht-schedule.php)
- [Socle IA & Google 1.18.0](https://github.com/montagnedessinges/horaires-tarifs-parc/blob/main/AUDIT-2026-09-29-IA-GOOGLE-1.18.0.md)
- [Classe IA & Google](https://github.com/montagnedessinges/horaires-tarifs-parc/blob/main/includes/class-parcs-ht-ai-google.php)
- [Instructions du dépôt](https://github.com/montagnedessinges/horaires-tarifs-parc/blob/main/AGENTS.md)

### Documentation Google

- [Ressource `locations` — Business Information API](https://developers.google.com/my-business/reference/businessinformation/rest/v1/locations)
- [Méthode `locations.patch`](https://developers.google.com/my-business/reference/businessinformation/rest/v1/locations/patch)
- [Prérequis et demande d’accès](https://developers.google.com/my-business/content/prereqs)
- [Mise en œuvre OAuth](https://developers.google.com/my-business/content/implement-oauth)
- [Limites et quotas](https://developers.google.com/my-business/content/limits)
- [Politiques des Business Profile APIs](https://developers.google.com/my-business/content/policies)
- [Structure des horaires spéciaux](https://developers.google.com/my-business/reference/businessinformation/rest/v1/accounts.locations)
