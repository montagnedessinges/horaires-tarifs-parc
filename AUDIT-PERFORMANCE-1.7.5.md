# Audit performance – Horaires & Tarifs Parc 1.7.5

Audit statique réalisé à partir de la base 1.7.4 avant publication de la 1.7.5.

## Périmètre contrôlé

- bootstrap PHP et fichiers chargés par requête ;
- Options API et migrations ;
- scripts/styles publics et administration ;
- calculs calendrier et prochaine ouverture ;
- génération PDF ;
- système de mise à jour GitHub ;
- appels HTTP, cron, requêtes SQL directes et accès fichiers ;
- syntaxe PHP et JavaScript ;
- nonces, capabilities, sanitization et escaping sur les principaux endpoints.

## Optimisations appliquées

1. Chargement conditionnel des modules admin / alertes / updater.
2. Bootstrap léger des shortcodes avec chargement différé du gros moteur de rendu.
3. Migration supprimée du chemin critique de chaque page publique.
4. Flag `parcs_ht_has_popup_source` très léger pour éviter la lecture de `parcs_ht_settings` quand aucun pop-up n'est utilisé.
5. Cache PHP par requête des réglages d'une saison.
6. Payload public réduit aux réglages nécessaires au JavaScript.
7. Mémoïsation JavaScript des horaires calculés par date et des prochaines ouvertures.
8. Cache des erreurs GitHub et timeout de vérification ramené à 6 secondes.

## Mesures statiques

- Base 1.7.4 : environ 374 Ko de fichiers PHP étaient inclus par le bootstrap initial.
- Base 1.7.5, requête publique ordinaire sans shortcode ni pop-up : environ 101 Ko de PHP sont inclus au bootstrap.
- Réduction statique du code PHP chargé au bootstrap public : environ 73 %.
- Exemple préconfiguration Forêt : payload public JSON estimé de 11 606 octets à 7 220 octets, soit environ 38 % de moins.
- Réglages généraux transmis au navigateur : 121 clés / ~4,7 Ko auparavant contre 7 clés / ~0,3 Ko après audit sur la préconfiguration testée.

Ces chiffres mesurent le volume de code/données traité par l'extension dans un environnement de test statique. Ils ne constituent pas une mesure du temps de chargement réel d'un site, qui dépend aussi du thème, de PHP/OPcache, du cache, de la base de données, du serveur et des autres extensions.

## Points ne nécessitant pas de correction immédiate

- Aucun `$wpdb`, `WP_Query` ou requête SQL personnalisée dans l'extension.
- Aucun cron propre à l'extension.
- Les appels réseau sont limités au système GitHub de mise à jour.
- Les CSS/JS publics restent chargés uniquement lorsqu'un shortcode nécessite le moteur public ; un fallback existe pour les shortcodes rendus tardivement par un constructeur de pages.
- Les PDF sont générés uniquement à la demande et ne s'exécutent pas pendant une visite normale.

## Données

La 1.7.5 ne change pas `SCHEMA_VERSION` et n'introduit aucune migration des horaires/tarifs. Les données existantes restent stockées dans `parcs_ht_settings` et ne sont pas remplacées.
