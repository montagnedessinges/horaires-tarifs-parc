# Mise à jour 1.13.2 — catalogue complet des shortcodes

Date : 6 septembre 2026

## Objectif

L’onglet **Shortcodes** de l’administration doit afficher tous les shortcodes réellement utilisables par l’extension, sans dépendre d’une ancienne liste partielle maintenue à la main.

## Correction

- le registre central reste la source unique des modules de shortcode ;
- chaque module expose désormais quatre variantes dans le catalogue :
  - sans suffixe de langue : détection automatique de la langue ;
  - `_fr` : français forcé ;
  - `_en` : anglais forcé ;
  - `_de` : allemand forcé ;
- l’onglet Shortcodes reconstruit automatiquement son tableau à partir du registre central ;
- les modules récents tels que `parc_tarifs_groupes`, `parc_horaire_accueil` et `parc_guides_pedagogiques` sont donc inclus automatiquement ;
- un test de régression vérifie la présence de la colonne « Automatique » et des quatre variantes.

## Catalogue attendu

Le registre contient 12 modules. L’onglet expose donc 48 shortcodes utilisables : 12 variantes automatiques, 12 FR, 12 EN et 12 DE.

## Compatibilité

Aucune donnée de parc, aucun tarif, aucun horaire, aucun formulaire Contact Form 7 et aucun réglage existant ne sont modifiés par cette évolution.
