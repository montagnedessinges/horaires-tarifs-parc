# Audit administration — Calendrier de l’Avent 1.15.0

Date : 9 septembre 2026

## Constat

Le moteur Avent et sa page d’administration étaient bien présents dans la 1.15.0, mais leur point d’entrée n’était pas cohérent avec l’interface réellement utilisée dans « Horaires du parc ».

La page Avent était enregistrée uniquement comme sous-menu WordPress, alors que la navigation principale de l’extension ne contenait aucune entrée visible vers cet espace. En parallèle, les deux shortcodes Avent étaient ajoutés à la table « Shortcodes » par une surcouche JavaScript après rendu de la page.

Cette architecture pouvait donc donner l’impression que le module n’existait pas, même si son code était chargé.

## Cause

Les tests 1.15.0 validaient principalement la présence des classes, fonctions, chaînes de sécurité et shortcodes. Ils ne protégeaient pas suffisamment l’architecture réelle de navigation de l’administration.

## Correction structurelle 1.15.1

- le Calendrier de l’Avent devient un menu WordPress de premier niveau ;
- « Horaires du parc » contient un lien PHP natif vers cet espace ;
- la page Shortcodes lit directement le registre central ;
- `assets/advent-shortcodes-admin.js` est supprimé ;
- le contrôleur admin porte le nom canonique `class-parcs-ht-advent-admin.php` ;
- les tests bloquent le retour à l’ancien sous-menu seul ou à une injection DOM.

Aucune donnée de campagne MDS/FDS n’est créée en dur et aucune campagne existante n’est réinitialisée par cette correction.