# 1.21.0 — Synchronisation Google Maps à partir du calendrier existant

## Périmètre obligatoire

Cette évolution part de la version **1.20.31**. Elle ajoute uniquement le module Google Business Profile et ses points de raccordement nécessaires. Le contenu du calendrier, les tarifs, les groupes, les devis, les sauvegardes ciblées et les comportements existants doivent rester identiques à 1.20.31. La branche `main` reste inchangée tant que la PR n'est pas explicitement validée et fusionnée.

Toute modification hors de ce périmètre doit être expliquée et discutée avant d'être ajoutée. Si une adaptation exige de changer un comportement existant, on la garde à part pour en débattre avant de poursuivre.

## Objectif

Le moteur `Parcs_HT_Schedule::resolve_day()` est l'unique source des horaires de la Montagne des Singes et de la Forêt des Singes. **Aucun nouveau calendrier n'est créé** et aucune donnée d'ouverture n'est écrasée. Chaque installation WordPress associe une fiche Google distincte.

Cette release est préparée sur une branche de développement. **Aucune connexion réelle ni publication sur une fiche Google n'a été testée à ce stade.** Ne pas l'activer en production sans tests de validation.

## Fonctionnalités

- Écran **Gestion du parc → Google Maps**.
- Identifiants OAuth 2.0 Google Cloud pour application Web, avec URL de redirection affichée.
- Connexion avec consentement Google (`business.manage`, accès hors ligne), liste des fiches accessibles et choix de la fiche exacte.
- Activation explicite par l'administrateur, **désactivée par défaut**, et possibilité de déconnecter.
- Projection par `Parcs_HT_GBP_Plan` :
  - horaires des 7 prochains jours convertis en plages hebdomadaires Google (`regularHours`);
  - écarts sur les 35 prochains jours convertis en horaires exceptionnels (`specialHours`);
  - hors saison ou sans ouverture prévue dans la semaine, statut `CLOSED_TEMPORARILY`;
  - reprise des horaires et du statut `OPEN` à la réouverture, si le calendrier WordPress le prévoit.
- Une seule vérification planifiée par jour, vers **1 h du matin** dans le fuseau horaire WordPress ; chaque installation reçoit un décalage déterministe de 0 à 59 minutes. Le calendrier est comparé localement à l’envoi précédent : si le contenu utile n’a pas changé, aucun appel à Google n’est lancé. Une modification du calendrier est prise en compte à la prochaine vérification nocturne. Le bouton **Synchroniser maintenant** permet une intervention exceptionnelle. Le module n'est chargé que dans l’administration ou lors d’une tâche cron, jamais sur les requêtes publiques ordinaires.
- Contrôle après envoi par relecture de l'API et affichage des erreurs / des modifications Google en attente.
- Les autres champs Google (nom, adresse, téléphone, catégorie, etc.) ne sont jamais modifiés.

## Préalables Google

1. Obtenir l'accord de Google pour utiliser les API Business Profile et activer les API *Business Information* et *Account Management* dans le projet Cloud.
2. Configurer l'écran de consentement OAuth et un client OAuth 2.0 de type **Application Web**.
3. Ajouter **exactement** l'URL de redirection affichée dans l'écran WordPress.
4. Donner à l'utilisateur Google l'accès à la fiche du parc à synchroniser.
5. Enregistrer les identifiants dans WordPress, autoriser Google, choisir la bonne fiche puis activer la synchronisation.

Une **simple clé API Google Maps ne suffit pas**. Le projet OAuth peut être soumis à une vérification ; en mode test, la durée de vie des jetons hors ligne peut être réduite.

## Limites et précautions

- Les horaires exceptionnels ne sont pas disponibles pendant un statut Google `CLOSED_TEMPORARILY`. La synchronisation envoie donc seulement le statut hors saison, et les horaires lors de la réouverture.
- Les exceptions saisies directement sur Google au-delà de la fenêtre de 35 jours sont conservées pour éviter de les effacer ; les exceptions de la fenêtre synchronisée sont remplacées par celles de WordPress.
- La représentation hebdomadaire Google ne possède pas les périodes de dates de notre calendrier. L'extension calcule des horaires récurrents à court terme et des exceptions futures sur **35 jours**, puis les actualise dans le temps.
- Les plages horaires chevauchant minuit ou inversées ne sont pas envoyées ; elles produisent une erreur d'administration.
- Google peut examiner des modifications avant qu'elles soient affichées publiquement. Une réponse HTTP valide **ne garantit pas** la publication immédiate dans Google Maps.
- WP-Cron peut s'exécuter en retard sur un site peu visité. Configurer une **vraie tâche cron système** sur le serveur pour les bascules saisonnières à heure régulière. L'envoi quotidien ou déclenché après une modification du calendrier peut être retardé par les validations Google.
- La bascule saisonnière se fait en fonction de la date locale WordPress, pas d'une heure spécifique de fermeture. Tester ce comportement : pour une dernière journée d'ouverture, l'état `CLOSED_TEMPORARILY` est programmé à partir du **jour suivant**.
- Cette synchronisation est unidirectionnelle : **WordPress → Google**. Les modifications manuelles dans Google peuvent être remplacées lors de la prochaine synchronisation.
- Le secret OAuth et le jeton de renouvellement sont enregistrés dans une option WordPress dédiée, non affichée dans le back-office. Protéger la base de données et les sauvegardes ; ne jamais inclure ces identifiants dans un ticket GitHub public. Il faudra auditer les exports/sauvegardes du site avant mise en service.

## Tests et recette

Tests automatiques sans Google : `php tests/gbp-plan-1210-runtime.php` (projection 7/8/11 novembre 2026, fermeture le 12, réouverture 2027, horaires inversés refusés, source inchangée).

Avant mise en production :

1. Vérifier les données de calendrier des deux sites, y compris les cas sans période active, exceptions de priorité et périodes de fermeture.
2. Tester la connexion OAuth et la sélection de **la bonne fiche** avec un compte Google autorisé.
3. Tester l'envoi Google en mode validation (`validateOnly`) et contrôler qu'il ne touche pas aux autres champs.
4. Contrôler les horaires affichés publiquement dans Google Maps sur une semaine classique, un jour exceptionnel et une fin de saison.
5. Vérifier les erreurs/reconnexion, la fréquence du cron, le fuseau horaire et la récupération après une panne.
6. Tester indépendamment les deux installations WordPress, puis n'activer l'envoi automatique qu'après accord de l'administrateur.

## Sources techniques Google

- https://developers.google.com/my-business/content/overview
- https://developers.google.com/my-business/content/implement-oauth
- https://developers.google.com/my-business/reference/businessinformation/rest/v1/accounts.locations/list
- https://developers.google.com/my-business/reference/businessinformation/rest/v1/locations/patch
- https://developers.google.com/my-business/reference/businessinformation/rest/v1/accounts.locations
