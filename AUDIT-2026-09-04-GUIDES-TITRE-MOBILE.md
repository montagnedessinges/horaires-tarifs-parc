# Audit — titre du shortcode Guides pédagogiques sur mobile

Date : 4 septembre 2026

## Problème observé

Sur mobile, le titre « Dossiers pédagogiques » rendu par le shortcode des guides remonte dans la zone d’en-tête du site et se superpose au bandeau/logo au lieu de rester dans le contenu de la page.

Shortcodes concernés :

- `[parc_guides_pedagogiques]`
- `[parc_guides_pedagogiques_fr]`
- `[parc_guides_pedagogiques_en]`
- `[parc_guides_pedagogiques_de]`

## Cause identifiée

Le shortcode rend son titre dans un élément HTML `<header class="parcs-ht-guides-head">`.

Le commit `9939cde5d3d93153a559e70e3af7d1396526f4b1` (« Improve pedagogical guide responsive layout ») a rendu ce bloc visible en remplaçant le comportement précédent `display:none` par `display:block`.

Le site utilise par ailleurs des règles de thème destinées à son propre élément `header`. Une fois le `header` interne du shortcode rendu visible, ces règles globales peuvent lui appliquer un positionnement d’en-tête de site sur mobile. Le symptôme observé correspond à cette collision CSS : le titre est sorti visuellement de son flux normal et apparaît dans le bandeau supérieur.

Le problème ne vient pas des données des guides ni des filtres de cycles/langues. Les améliorations récentes de grille, de taille d’images et de filtres mobiles peuvent être conservées.

## Correction appliquée

La correction est volontairement ciblée sur le bloc de titre du shortcode :

- conservation du titre visible ;
- isolation de `.parcs-ht-guides-head` par rapport aux styles génériques du thème ;
- rétablissement forcé du flux normal avec `position: static` ;
- neutralisation des propriétés susceptibles de provenir du header du thème (`inset`, `top/right/bottom/left`, `transform`, `z-index`, dimensions, fond, ombre, flottement) ;
- neutralisation équivalente du `h2` interne ;
- maintien d’une marge normale sous le titre, adaptée sur mobile ;
- aucune modification des cartes, filtres, données ou contenus des guides.

Cette solution est limitée au composant `.parcs-ht-guides` afin de ne pas modifier le header réel du site.

## Version

Une nouvelle version est nécessaire : **1.12.12**.

Motifs :

1. le correctif modifie le CSS livré par l’extension ;
2. `PARCS_HT_VERSION` sert au cache-busting des assets WordPress ;
3. le processus GitHub de publication construit les releases à partir du numéro de version du plugin.

## Contrôles de non-régression

La correction conserve les contrats déjà attendus par `tests/pedagogical-guides-contract.php`, notamment :

- titre public toujours visible ;
- filtres cycles/langues inchangés ;
- grille responsive inchangée ;
- images 3:4 inchangées ;
- absence de l’ancien espace mobile artificiel de 48 px.

À valider après publication sur le site réel :

- smartphone ≤ 600 px : titre dans le contenu, sous le bandeau du site ;
- tablette : titre dans le flux normal ;
- bureau : aucun déplacement du bloc pleine largeur ;
- FR / EN / DE : même comportement pour les trois shortcodes dédiés.
