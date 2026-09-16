# Addendum — Mise à jour 2 : refonte visuelle de la partie Groupes

Date : 16/09/2026

Dépôt : `montagnedessinges/horaires-tarifs-parc`

Ce document complète la roadmap et les addendums précédents. Il concerne **uniquement la mise à jour 2**, qui doit rester une mise à jour visuelle / UX. Aucune logique métier ne doit être ajoutée ici : les comportements techniques, la publication par année, les shortcodes, les horaires communs et les règles d’affichage doivent être réglés dans la mise à jour 1.

## Périmètre visuel à refaire

La mise à jour 2 doit inclure une refonte visuelle complète de la partie publique Groupes, notamment :

- le bloc / shortcode `Groupes — Horaires et tarifs` ;
- l’onglet `Horaires d’ouverture` ;
- l’onglet `Tarifs groupes` ;
- le sélecteur d’année lorsqu’il existe ;
- le tableau des horaires ;
- le tableau des tarifs groupes ;
- les moyens de paiement ;
- les blocs d’information `Paiement et facturation` et `Devis et réservation` ;
- le bouton `Faire une demande de devis`.

## Objectif graphique

Le rendu actuel est trop long, trop espacé et peu optimisé pour les petits écrans. La refonte doit reprendre la même philosophie que celle définie pour les tarifs visiteurs :

- affichage beaucoup plus compact ;
- lecture rapide ;
- priorité mobile ;
- moins de hauteur verticale ;
- marges et paddings réduits ;
- hiérarchie visuelle plus nette ;
- cohérence graphique avec le tableau général des tarifs ;
- conservation des couleurs configurables existantes lorsque possible.

## Onglets principaux

Les onglets `Horaires d’ouverture` et `Tarifs groupes` doivent être :

- compacts ;
- côte à côte ;
- clairement différenciés entre état actif et inactif ;
- adaptés au mobile ;
- sans gros blocs verticaux inutiles.

Si une seule section est techniquement disponible après la mise à jour 1, la mise à jour 2 doit simplement présenter cette section proprement, sans créer de faux espace visuel.

## Sélecteur d’année

Lorsque plusieurs années sont disponibles :

- afficher les années sous forme de petits boutons / pills compacts ;
- garder les années sur une ligne autant que possible ;
- permettre un défilement horizontal local sur très petit écran plutôt qu’un empilement massif ;
- ne jamais provoquer de scroll horizontal de toute la page.

## Horaires groupes — visuel

Les horaires affichés sont les mêmes données horaires que le reste du site. La mise à jour 2 ne doit donc modifier que leur présentation.

Le tableau actuel `Dates / Jours / Horaires` doit devenir plus compact et plus lisible sur mobile :

- lignes moins hautes ;
- texte plus dense mais lisible ;
- exceptions clairement identifiables sans créer de grosses cartes ;
- si nécessaire, adaptation responsive en lignes condensées plutôt qu’un grand tableau débordant ;
- mise en avant des horaires eux-mêmes ;
- dates et jours en informations secondaires.

## Tarifs groupes — visuel

Refaire le rendu dans le même esprit que le nouveau tableau tarifaire visiteurs :

- lignes tarifaires compactes ;
- prix mis en avant ;
- suppression des grands espaces inutiles ;
- détails / sous-titres plus discrets ;
- présentation homogène entre adulte/accompagnant, enfant, personne en situation de handicap, etc. ;
- utilisation optimale de la largeur sur mobile.

Les tarifs groupes n’ont pas besoin de reprendre la logique visuelle `Sur place / En ligne` si cette notion n’existe pas pour les données groupes. Il faut respecter les données réellement présentes et ne pas inventer de canaux de vente.

## Moyens de paiement

Le bloc `Moyens de paiement` doit être beaucoup plus compact :

- petites pills ou badges ;
- icônes réduites ;
- possibilité de plusieurs éléments sur une même ligne ;
- retour à la ligne propre sur mobile ;
- pas de grands espaces verticaux.

## Blocs d’information

Les blocs `Paiement et facturation` et `Devis et réservation` doivent être visuellement modernisés :

- meilleure lisibilité ;
- titres clairement distingués ;
- corps de texte plus compact ;
- éviter deux grandes colonnes difficiles à lire sur petit écran ;
- sur mobile, privilégier un empilement propre et dense ;
- possibilité d’un accordéon uniquement si cela améliore réellement la lecture et reste accessible, sans cacher une information importante de façon inutile.

## Bouton de devis

Le bouton `Faire une demande de devis` doit rester bien visible mais ne pas consommer une hauteur excessive.

Il doit conserver le lien existant et uniquement recevoir une amélioration visuelle.

## Cohérence avec le tableau général des tarifs

La partie Groupes doit visuellement appartenir au même système :

- mêmes rayons de bordure ;
- même logique typographique ;
- même densité ;
- mêmes principes responsive ;
- états actifs cohérents ;
- couleurs issues des variables/configurations existantes autant que possible.

Il ne faut pas pour autant forcer exactement le même composant si les données sont différentes.

## Contrainte essentielle

**Cette mise à jour 2 est visuelle uniquement.**

Ne pas y déplacer :

- les règles de publication des années ;
- la logique `group_tariffs_visible` ;
- la logique `groups_schedule_visible` ;
- les nouveaux shortcodes ;
- la séparation public / groupes ;
- l’import/export CSV ;
- les corrections de données.

Ces éléments appartiennent à la mise à jour 1.

## Critères d’acceptation

- [ ] La partie Groupes a été réellement redessinée, pas seulement recolorée.
- [ ] Les onglets Horaires / Tarifs groupes sont compacts.
- [ ] Le sélecteur d’année est compact et mobile-friendly.
- [ ] Les horaires sont nettement plus courts et lisibles sur mobile.
- [ ] Les tarifs groupes sont nettement plus compacts.
- [ ] Les moyens de paiement prennent moins de place.
- [ ] Les blocs d’information sont plus lisibles et moins longs visuellement.
- [ ] Le bouton de devis reste évident.
- [ ] Aucun scroll horizontal global n’est introduit.
- [ ] Le rendu reste utilisable à environ 320 px de largeur.
- [ ] La mise à jour ne modifie aucune règle métier déjà corrigée dans la mise à jour 1.
