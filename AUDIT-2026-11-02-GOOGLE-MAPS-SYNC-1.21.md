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

### Règles métier précisées le 02/11/2026

- L’objectif est de **programmer à l’avance toute la saison en cours**, avec toutes les plages horaires et exceptions déjà connues dans le calendrier publié, pas de vérifier la fiche Google tous les jours.
- La fermeture de midi ne devient pas une journée fermée : elle est représentée par deux plages d’ouverture le même jour, avec l’intervalle de fermeture entre les deux.
- Les exceptions datées de courte durée (jusqu’à 6 jours de fermeture consécutifs) sont envoyées comme horaires spéciaux. Toute période de fermeture continue de 7 jours ou plus — fermeture saisonnière ou autre fermeture planifiée — doit suivre la consigne Google « temporairement fermé » ; elle ne doit pas être encodée comme une suite d’exceptions quotidiennes.
- L’API expose le statut courant `openInfo.status` (ouvert / fermé temporairement), sans champ de date d’effet future. Une seule validation doit envoyer le calendrier et enregistrer les transitions futures pour toutes les périodes planifiées d’au moins 7 jours consécutifs : fermeture temporaire au début de chaque période et réouverture à la prochaine date d’ouverture. L’administrateur n’a pas à revenir cliquer au moment de ces deux transitions. Si l’une des dates est déjà arrivée au moment de la validation, l’action correspondante doit être exécutée immédiatement au lieu d’être planifiée dans le passé. Ce sont des événements ponctuels, pas une vérification quotidienne. Les fermetures d’urgence resteront gérées manuellement dans Google Maps et ne feront pas partie d’une synchronisation automatique.
- Les horaires peuvent changer plusieurs fois dans l’année : au moins deux régimes sont prévus à la Montagne des Singes et probablement davantage à la Forêt des Singes. Pour chaque régime hebdomadaire, l’extension doit programmer une mise à jour de `regularHours` à la date de début ; réserver `specialHours` aux exceptions par date. La synchronisation annuelle validée doit programmer ces transitions sans vérification quotidienne.
- Le schéma d’API expose une liste de périodes spéciales et ne donne pas, dans la référence consultée, de maximum global pour le nombre d’entrées ; cela ne prouve pas qu’il n’existe aucune limite opérationnelle. Les périodes spéciales doivent rester non chevauchantes et les fermetures de longue durée doivent utiliser le statut temporaire selon les consignes Google.
- Google permet de programmer des horaires spéciaux sur des dates futures et indique qu’ils apparaissent aux clients aux dates désignées. Une piste serait de préprogrammer les six premiers jours d’une fermeture longue pour les rendre visibles dans l’aperçu des prochains jours, puis de faire passer la fiche en « temporairement fermé ». Cependant, l’utilisateur constate que l’interface Google désactive les horaires spéciaux quand la fiche est temporairement fermée ; Google indique aussi que les horaires spéciaux nécessitent des horaires principaux ouverts. Cette combinaison n’est donc pas considérée comme acquise : il faut la tester sur une fiche réelle avant de l’intégrer.
- Limite d’affichage à expliquer : sans exception d’annonce anticipée validée, le statut temporaire ne changera qu’au moment où l’action planifiée s’exécute. Avant ce moment, la fiche peut continuer à montrer les horaires hebdomadaires normaux pour les jours futurs ; l’API documentée n’offre pas de date d’effet future pour `openInfo.status`.

### Emplacement dans l’administration

La synchronisation doit apparaître comme un onglet **« Google Maps »** dans l’écran existant **Gestion du parc → Horaires & calendrier**. Ce placement la rattache au calendrier source et évite de la confondre avec l’écran **IA & Google**, qui gère les données structurées / SEO et ne synchronise pas la fiche Business Profile. Le titre de l’onglet reste court ; son en-tête peut être **« Synchronisation des horaires Google Maps »**.

## Vérification des calendriers publics (02/11/2026)

### Montagne des Singes

La page publique [Tarifs groupes — calendrier 2027](https://www.montagnedessinges.com/groupes-et-cse/tarifs-groupes/?htp_group_year=2027) affiche :

- Saison du 20/03/2027 au 14/11/2027.
- Du 20/03 au 31/08 : tous les jours, 09:30–17:30.
- Du 01/09 au 14/11 : tous les jours, 10:00–17:00.
- Fermeture exceptionnelle du 08/11 au 10/11/2027 (3 jours consécutifs).

Ce calendrier fournit déjà un cas simple à convertir : deux régimes réguliers à programmer à leurs dates de début, puis une fermeture spéciale de trois jours. La page 2027 consultée ne montre pas de pause méridienne dans ces horaires.

### Forêt des Singes

La page publique [Tarifs groupes](https://www.la-foret-des-singes.com/tarifs-groupes/) accessible dans les résultats consultés affiche le calendrier 2026, pas le calendrier 2027. L’ouverture directe de la même page avec le paramètre d’année 2027 a renvoyé une page WordPress « erreur critique » dans le navigateur de consultation. Je ne peux donc pas confirmer les horaires 2027 de la Forêt depuis sa page publique.

Le calendrier public 2026 montre toutefois pourquoi le convertisseur devra accepter plusieurs régimes hebdomadaires dans une saison : horaires à deux plages, horaires semaine / week-end différents à l’automne et horaires d’après-midi uniquement en semaine sur certaines périodes. Cela donne un exemple de complexité, mais ne constitue pas une preuve des horaires 2027.

**Conséquence pour le développement :** le moteur doit lire les dates et horaires propres à la saison sélectionnée pour chaque parc ; il ne doit pas supposer un nombre fixe de régimes ni réutiliser les périodes 2026 pour 2027.

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
- Pour les parcs dont les horaires varient selon les saisons, programmer une mise à jour du motif hebdomadaire `regularHours` à chaque changement de régime au calendrier. Ne pas simuler des mois de nouvel horaire hebdomadaire en ajoutant une exception spéciale par date.
- La prévisualisation doit afficher chaque régime hebdomadaire, ses dates de début / fin et les appels futurs programmés pour l’appliquer.

### Exceptions datées

- Convertir les horaires exceptionnels publiés en périodes `specialHours` sur leurs dates exactes pour toute la saison planifiée.
- Exemple obligatoire : si les lundis réguliers sont ouverts mais qu’un lundi précis du calendrier est fermé, conserver le lundi ouvert dans `regularHours` et ajouter une exception fermée `specialHours` à cette date. Cette exception Google doit remplacer l’horaire régulier uniquement pour le lundi concerné.
- Convertir une fermeture isolée ou de courte durée (6 jours consécutifs maximum) en exceptions fermées datées.
- Pour les jours d’ouverture avec deux créneaux, transmettre les deux périodes de la journée ; vérifier qu’elles ne se chevauchent pas.
- Pour toute période planifiée de 7 jours consécutifs ou plus, utiliser l’état temporairement fermé, selon les consignes Google. Programmer l’appel de fermeture au début de la période et l’appel de réouverture au premier jour d’ouverture. L’API expose le statut courant mais pas une date d’effet future ; le plugin doit donc porter la planification.
- Les fermetures d’urgence ne déclenchent pas de synchronisation depuis l’extension : elles sont saisies directement dans Google Maps.
- Générer tous les états journaliers depuis `resolve_day()`, sans créer un moteur de calcul parallèle.
- Une fois l’aperçu validé, inclure toute la saison courante et toutes les dates déjà renseignées ; ne pas limiter l’envoi aux prochains jours.
- L’API utilise une liste de périodes spéciales. Aucune limite maximale de nombre d’entrées n’a été trouvée dans la référence API officielle consultée, mais la prévisualisation doit quand même signaler les erreurs de validation ou les chevauchements retournés par Google.

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
7. **Envoi / programmation annuelle en une validation** : bouton distinct « Synchroniser la saison », confirmation explicite, envoi des horaires connus et programmation simultanée de la fermeture saisonnière et de la réouverture. Une seule validation arme la saison ; les transitions planifiées s’exécutent ensuite sans second clic.
8. **Tâches planifiées** : programmer uniquement les appels futurs de fermeture saisonnière et de réouverture confirmés dans le calendrier, sans synchronisation quotidienne ni écrasement des fermetures d’urgence saisies directement dans Google Maps. Vérifier si l’hébergement exécute WP-Cron assez régulièrement ; WordPress déclenche les tâches dues lors d’un chargement de page, donc un cron système peut être nécessaire pour un horaire fiable.
9. **Retour de Google** : relire l’état de la fiche après l’envoi et comparer aux horaires prévus.

## Points bloquants à confirmer avant développement

- Le projet Google Cloud de l’entreprise est-il déjà approuvé pour les Business Profile APIs ? Son quota Business Information API est-il supérieur à zéro ?
- Après une fermeture temporaire, l’API permet-elle de remettre le prochain régime `regularHours`, de rouvrir la fiche et de conserver / réactiver les exceptions datées ?
- Une fermeture longue planifiée apparaît-elle comme fermée dans la fenêtre Maps des 7 jours avant son premier jour, sans désactiver ou invalider les horaires spéciaux ?
- Les deux fiches Google sont-elles administrées depuis le compte Google qui autorisera l’intégration ?
- Google accepte-t-il, pour les catégories exactes des deux lieux, les horaires et jours spéciaux générés par le calendrier ?

Le statut d’approbation, les deux comptes/fiches, les catégories, les quotas et le rendu final des fiches **ne peuvent pas être confirmés depuis le seul dépôt**. Les règles de saison sont cadrées ; il reste à vérifier le volume d’exceptions et la disponibilité d’une exécution planifiée fiable sur l’hébergement des deux sites.

## Périmètre conseillé pour 1.21.0

### Inclus dans le premier lot

- Connexion OAuth à Business Profile et stockage indépendant par site.
- Sélection explicite de la fiche autorisée.
- Lecture de la fiche et affichage de ses horaires actuels.
- Conversion en lecture seule du calendrier publié vers un aperçu des horaires réguliers et des exceptions.
- Diff et validations avant envoi.
- Une seule synchronisation annuelle manuelle après aperçu et validation, avec envoi du calendrier et enregistrement des changements d’état futurs de fermeture saisonnière et de réouverture.
- Exécution ponctuelle des transitions programmées sans nouveau clic à leur date ; aucun polling ou appel de vérification quotidien. Les fermetures d’urgence restent manuelles sur Google.
- Tests de contrat pour les semaines types, deux plages quotidiennes, jours fermés, exceptions prioritaires, bornes de saison, saison brouillon et champs hors masque.
- Protection des actions par capacité WordPress, nonce, validation stricte, échappement des sorties et gestion des erreurs OAuth/API.
- Vérification séparée sur la configuration de la Montagne des Singes et celle de la Forêt des Singes.

### À différer

- Synchronisation automatique à chaque sauvegarde avant que les deux fiches aient été validées avec la synchronisation manuelle.
- Mise à jour d’autres champs de la fiche Google.
- Toute publication de saisons brouillon ou non visibles publiquement.
- Synchronisation depuis un compte ou un projet Google partagé avec des clients tiers.

## Plan de vérification avant release

- **Conversion / affichage** : plages hebdomadaires, changements planifiés de `regularHours` entre régimes, journées avec deux créneaux, journée datée fermée alors que son jour habituel est ouvert, exceptions ouvertes, fermetures de 1 à 6 jours, fermeture continue ≥7 jours et réouverture planifiée ; tester si la fermeture longue apparaît dans l’aperçu Maps une semaine à l’avance.
- **Réouverture** : après une période « temporairement fermé », réappliquer le prochain régime de `regularHours`, rétablir l’état ouvert, vérifier que les horaires sont visibles et vérifier quand les horaires spéciaux sont de nouveau modifiables.
- **Priorité** : vérifier que l’exception gagnante dans `resolve_day()` est celle envoyée à Google ; aucune règle de priorité parallèle.
- **Données** : confirmer que 2026/2027 et les exceptions propres à chaque parc sont converties indépendamment ; aucune donnée n’est supprimée à l’installation ou à la mise à jour.
- **API** : scope, fiche sans permission, jeton révoqué/expiré, `validateOnly`, erreurs 400/401/403/429/5xx, répétition d’une synchronisation identique et réponse partielle.
- **Masque** : vérifier que les changements ne portent que sur les horaires ; relire les champs de profil qui doivent rester inchangés.
- **Interface** : différencier aperçu, validation et écriture réelle ; garder l’état d’envoi et la dernière erreur après rechargement.
- **Exploitation** : pas d’appels de vérification quotidiens. Synchronisation annuelle uniquement après aperçu et validation manuelle ; appels ponctuels planifiés pour l’état temporairement fermé / réouvert pour les périodes d’au moins 7 jours. Les fermetures d’urgence restent manuelles dans Google Maps. Tester le retard possible de WP-Cron sur chaque hébergement.
- **Publication** : mise à jour du changelog, montée à 1.21.0 lorsque le développement sera validé, exécution de la CI et génération du ZIP de release. Ce document seul n’est pas une release.

## Sources

### Dépôt

- [Moteur des horaires](https://github.com/montagnedessinges/horaires-tarifs-parc/blob/main/includes/class-parcs-ht-schedule.php)
- [Calendrier 2027 public — Montagne des Singes](https://www.montagnedessinges.com/groupes-et-cse/tarifs-groupes/?htp_group_year=2027)
- [Calendrier public — Forêt des Singes](https://www.la-foret-des-singes.com/tarifs-groupes/)
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
- [Aide Google : horaires spéciaux, limite de fermeture de six jours et statut temporairement fermé](https://support.google.com/business/answer/6303076?hl=fr)
- [Google : consignes pour les activités saisonnières](https://support.google.com/business/answer/3038177?hl=fr)
- [Google API : statut courant `openInfo.status`](https://developers.google.com/my-business/reference/businessinformation/rest/v1/locations)
- [WordPress : fonctionnement et précision de WP-Cron](https://developer.wordpress.org/plugins/cron/)
