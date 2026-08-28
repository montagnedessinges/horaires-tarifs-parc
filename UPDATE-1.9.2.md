# Mise à jour 1.9.2 — simplification de l’affichage d’accueil

## Objectif
Retirer les rustines ajoutées pour tenter de corriger un texte statique « Ouvert » provenant du thème/Elementor et revenir à une architecture simple : l’extension ne pilote que ses propres composants.

## Cause désormais identifiée
Sur La Forêt des Singes, le bloc de page d’accueil combine un texte/statut externe au composant dynamique de l’extension avec l’horaire calculé par l’extension. Cela peut produire une combinaison incohérente comme « Ouvert — À partir de 9h » après la fermeture.

La Montagne des Singes, qui s’appuie sur le composant dynamique correctement intégré, ne présente pas ce problème de la même manière.

## Changement 1.9.2
`assets/status-sync.js` ne parcourt plus les parents Elementor, titres, paragraphes ou spans à la recherche de « Ouvert / Open / Geöffnet ».

Il ne met à jour que :
- `home-opening` ;
- `header-status` ;
- `header-hour` pour ses classes d’état ;
- `today`.

Le calcul reste fourni par le moteur commun `assets/display-state.js`.

## Action à faire sur La Forêt des Singes
Corriger le bloc de la page d’accueil à la source dans le thème/constructeur de page : ne plus utiliser un texte statique « Ouvert » séparé du moteur.

Privilégier le shortcode complet :

`[parc_horaire_accueil]`

ou sa variante de langue explicite si nécessaire :

- `[parc_horaire_accueil_fr]`
- `[parc_horaire_accueil_en]`
- `[parc_horaire_accueil_de]`

Le shortcode complet est à privilégier car il porte ensemble le statut, les horaires et la dernière entrée et évite de désynchroniser plusieurs morceaux de texte.

## Test de non-régression
Le test `tests/status-sync-engine.js` vérifie désormais qu’aucune logique Elementor ou de remplacement de texte externe n’est réintroduite dans `status-sync.js`.

## Hors périmètre
Cette version ne modifie pas les tarifs, offres commerciales, saisons, pop-up ou données WordPress.
