# Audit — performance et alertes Santé du site (2 novembre 2026)

## Objet et périmètre

Audit statique demandé après les alertes affichées dans l’administration de La Forêt des Singes. Sources examinées : dépôt `main` au commit `e91f65642717261a303fedbab0d0bf8155b3c531`, release `v1.20.30` (1er novembre 2026), code de sauvegarde et de chargement des réglages, et capture Santé du site fournie le 2 novembre 2026.

Cet audit ne constitue pas un diagnostic serveur : aucun journal PHP, profil mémoire, accès à l’hébergement ou rapport de requête SQL n’a été fourni.

## Lecture de la capture

La capture présente trois problèmes Santé du site :

1. Une session PHP a été démarrée par `session_start()`; WordPress indique qu’elle peut bloquer l’API REST et les requêtes de boucle tant qu’elle reste ouverte.
2. L’API REST échoue sur `/wp-json/wp/v2/types/post?context=edit` avec cURL 28 après 10 secondes et zéro octet reçu.
3. La détection ne trouve pas de cache de page. Le temps de réponse médian affiché est de 1 064 ms, au-dessus du seuil de 600 ms indiqué par WordPress.

Ces messages ne prouvent ni un dépassement mémoire PHP, ni une erreur fatale causée par l’extension. Ils décrivent une session ouverte, une requête REST expirée et l’absence de cache de page détecté.

## Constats vérifiés dans l’extension

### Session PHP et appels réseau

Une recherche statique sur les 76 fichiers PHP de l’extension ne trouve aucun appel à `session_start()`, `session_write_close()`, `session_status()`, `session_id()`, `curl_*` ou de modification de `memory_limit`.

Les occurrences d’API HTTP WordPress trouvées se situent dans les modules FAQ et mise à jour. Le code du moteur horaires/calendrier ne récupère pas ses données horaires depuis un service distant à chaque affichage. Les données publiques proviennent principalement des options WordPress locales.

Conclusion : la session PHP de la capture n’est pas attribuable à l’extension à partir du code actuel. Il faut identifier l’extension, le thème ou le code personnalisé qui appelle `session_start()`. Le délai REST peut être aggravé par la lenteur générale ou la session, mais la capture ne permet pas d’en établir la cause.

### Travail effectué sur une page publique

Le fichier principal charge 62 fichiers PHP avant de savoir si la requête publique utilise leurs fonctions. Leur taille source totale est d’environ 1,25 Mo, y compris plusieurs composants d’administration, de sauvegarde et d’import/export. Cette valeur est une taille de code source, pas une mesure de mémoire PHP ; OPcache et l’environnement du serveur influencent le coût réel.

Lorsque le calendrier ou un shortcode demande les réglages, `Parcs_HT_Defaults::all_settings()` reconstruit une représentation fusionnée des réglages et de toutes les saisons. Un cache statique PHP évite de la reconstruire à chaque appel dans une même requête, mais le cache ne survit pas à la requête suivante. La politique publique normalise aussi les tarifs de toutes les saisons lorsqu’elle filtre l’option principale.

Cela représente du travail local PHP et des copies de données à chaque requête non servie par un cache. Ce n’est pas une recherche réseau permanente ; en revanche, la capture confirme qu’aucun cache de page n’a été détecté sur le site au moment de ce contrôle.

### Travail effectué pendant une sauvegarde

La version 1.20.19 a déjà réduit les formulaires ciblés : elle limite les champs nettoyés, évite l’historique complet à chaque clic et ne reconstruit pas l’audit approfondi pour ces sauvegardes.

Une limite structurelle reste présente : les réglages de toutes les saisons sont regroupés dans l’option WordPress `parcs_ht_settings`. Même pour une sauvegarde ciblée, le code charge l’ensemble fusionné puis appelle `update_option()` avec l’option complète. WordPress doit donc encore sérialiser et persister tout cet arbre de données. Le formulaire ciblé réduit le POST et certains traitements, mais ne rend pas l’écriture physique limitée à une seule saison.

La sauvegarde dédiée au calendrier suit également ce modèle : elle charge `all_settings()`, modifie une saison, puis réécrit l’option principale. C’est une piste concrète pour réduire le pic mémoire des enregistrements, mais son importance réelle doit être mesurée sur l’hébergement.

## Diagnostic

- **Dépassement mémoire : non confirmé.** La capture ne contient aucun message `Allowed memory size exhausted` ni trace de fatal PHP.
- **Session PHP / REST : problème réel, origine non attribuée.** Aucun démarrage de session n’est trouvé dans les sources PHP actuelles de l’extension.
- **Manque de cache de page : confirmé par la capture.** Cela oblige le serveur à exécuter WordPress et les extensions pour davantage de visites anonymes.
- **Charge évitable de l’extension : piste confirmée statiquement.** Le bootstrap public charge de nombreux modules et le stockage monolithique impose une réécriture complète des réglages lors d’une sauvegarde.

Le fait que le site reçoive moins de visites ne réduit pas la puissance attribuée par l’hébergeur. Cela rend toutefois la mise en cache utile : elle peut servir la même page sans recalcul PHP à chaque visite.

## Solution recommandée

### Étape 1 — établir la cause de l’incident

Récupérer, pour l’heure exacte d’une erreur :

- le journal PHP/WordPress avec le message fatal complet et la trace de fichiers ;
- la version de l’extension réellement installée sur La Forêt des Singes ;
- si le problème arrive sur une page publique ou lors d’une action précise d’enregistrement ;
- le pic mémoire de la requête concernée, si l’hébergeur le fournit ;
- l’identité du code qui ouvre la session PHP, en examinant les extensions, le thème et les personnalisations chargées.

Sans cela, relever `memory_limit` ou modifier au hasard le moteur d’horaires risquerait de masquer le problème ou d’introduire une régression.

### Étape 2 — réduire le travail par requête

Préparer une évolution performance avec mesure avant/après sur un environnement de préproduction :

- charger les composants d’administration uniquement dans l’administration, et différer les composants publics jusqu’à la détection du shortcode ou de la page qui en a besoin ;
- éviter de reconstruire les réglages d’années invisibles lors du rendu d’une seule saison, en conservant les calendriers pluriannuels qui sont réellement publiés ;
- mettre en cache le HTML public stable ou s’appuyer sur le cache de page de l’hébergeur ; purger ce cache lors d’une modification des réglages et exclure les écrans d’administration, formulaires, aperçus et réponses personnalisées ;
- mesurer le temps, la mémoire maximale et la taille des données pour l’accueil, Horaires & Tarifs, une page sans shortcode et chaque parcours d’enregistrement.

### Étape 3 — réduire le pic mémoire des enregistrements

Pour qu’une sauvegarde d’une saison ne sérialise plus toutes les saisons, séparer progressivement le stockage : paramètres communs dans une option, puis une option par saison. Prévoir une migration idempotente, une lecture compatible avec l’ancien format, une sauvegarde de retour arrière et la conservation de toutes les données des deux parcs. N’effectuer cette migration qu’après comparaison du pic mémoire et de la taille réelle des options sur les deux sites.

## Limite et décision

Aucun changement de code fonctionnel n’est proposé dans cet audit : le journal serveur nécessaire pour attribuer l’erreur fatale n’est pas disponible, et la séparation du stockage exige une migration avec compatibilité historique. La prochaine correction doit être fondée sur ces mesures, puis vérifiée sur un site de préproduction avant installation sur les deux parcs.


## Précisions après le retour utilisateur

L’utilisateur indique que le site refonctionne lorsque l’extension est désactivée. Cette reproduction renforce le lien entre l’extension active et le problème observé. Elle ne désigne pas encore le chemin fautif : un conflit peut être déclenché par l’interaction de l’extension avec le thème ou une autre extension, même sans appel direct à `session_start()` dans ce dépôt.

L’option principale n’est pas autoloadée : l’activation utilise `add_option(..., false)` et les sauvegardes utilisent `update_option(..., false)`. Le risque identifié n’est donc pas un chargement automatique de cette grosse option sur toutes les pages WordPress. Il apparaît lorsque le code de l’extension la demande explicitement et fusionne toutes les saisons, ainsi qu’au moment où une sauvegarde transmet de nouveau l’option complète à WordPress.

## Références officielles consultées

- Le Plugin Handbook WordPress recommande de séparer le code d’administration du code public et de charger les fichiers admin avec `is_admin()` : https://developer.wordpress.org/plugins/plugin-basics/best-practices/
- La documentation Performance WordPress explique que les options autoloadées sont chargées à chaque page et recommande de ne pas autoloader celles qui ne servent que sur quelques URL : https://developer.wordpress.org/advanced-administration/performance/optimization/#autoloaded-options
- Le test Santé du site WordPress décrit l’alerte de session active et les risques pour REST/loopback : https://developer.wordpress.org/reference/classes/wp_site_health/get_test_php_sessions/
- WordPress indique qu’un conflit d’extension ou de thème est une cause fréquente de boucle REST en échec et recommande l’isolation par désactivation/réactivation : https://developer.wordpress.org/advanced-administration/wordpress/loopback/
- Le manuel PHP explique que les sessions fichier sont verrouillées jusqu’à la fermeture ou fin du script et que `session_write_close()` peut réduire les blocages concurrents : https://www.php.net/manual/en/function.session-write-close.php

## Priorité de livraison

La prochaine évolution à traiter est le chantier performance/mémoire de l’extension, avant la synchronisation Google Maps. L’audit de synchronisation Google Maps reste une étape séparée après stabilisation. Aucun déploiement sur les deux sites n’est inclus dans cette décision.


## Comparaison entre les deux parcs — retour utilisateur

L’utilisateur précise que l’extension active fonctionne sur La Montagne des Singes, tandis que le problème concerne La Forêt des Singes. Cette comparaison renforce l’hypothèse d’un problème dépendant de l’installation (hébergement, taille des réglages, thème ou combinaison d’extensions) plutôt que d’une incompatibilité générale de l’extension.

Elle ne confirme pas à elle seule le dépassement mémoire. Pour comparer les deux installations sans exposer de secrets, relever sur chacune :
- version exacte de l’extension et de PHP / valeur de `memory_limit` ;
- taille sérialisée de `parcs_ht_settings` et de l’historique de sauvegardes ;
- action et écran qui échouent à la Forêt ;
- thème actif et extensions actives autour de l’incident ;
- pic mémoire et fatal PHP correspondant, s’ils sont disponibles.

La comparaison doit guider la correction commune ; aucun réglage spécifique à la Montagne ne doit être copié vers la Forêt.
