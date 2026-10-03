# Mise à jour 1.19.8 — Règles du parc uniquement

## Correction

La version 1.19.7 avait ajouté un import séparé de l’onglet **« Connaissances singes - IA »** vers la catégorie « Règles du parc ».

Ce comportement est retiré.

## Fonctionnement retenu

La catégorie secondaire **« Règles du parc »** contient uniquement les règles de visite que l’on souhaite garder discrètes dans l’interface tout en les laissant accessibles à la recherche et aux outils d’IA qui lisent le HTML.

Ces règles sont créées directement dans l’onglet du parc :

- `Montagne des Singes` ;
- `Forêt des Singes`.

Puis elles sont importées avec le CSV FAQ habituel.

## Connaissances singes

L’onglet **« Connaissances singes - IA »** :

- reste une base interne de référence ;
- n’est pas proposé à l’import dans WordPress ;
- n’alimente pas la catégorie « Règles du parc » ;
- n’est pas nécessaire pour l’export CSV de la FAQ.

Les informations détaillées sur les Magots restent destinées aux contenus éditoriaux, aux réponses internes et aux informations fournies sur place.

## Affichage des règles

La logique introduite en 1.19.7 est conservée pour les vraies règles du parc :

- pas de filtre principal « Règles du parc » ;
- petit accordéon secondaire fermé par défaut ;
- contenu présent dans le HTML initial ;
- questions trouvables par la recherche de la FAQ ;
- ouverture automatique du bloc si une recherche correspond ;
- source publique affichable lorsqu’elle est disponible.

## Sécurité

- aucune suppression automatique ;
- aperçu avant import ;
- validation humaine ;
- révision avant application ;
- aucun changement sur les saisons, tarifs, horaires, devis, guides ou Contact Form 7.
