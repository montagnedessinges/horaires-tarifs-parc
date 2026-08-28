# Roadmap – prochaines évolutions à discuter

Ce fichier consigne les idées validées ou à débattre avant développement. Ne pas implémenter automatiquement ces points sans nouvelle validation utilisateur.

## Règle d’administration : repère interne obligatoire, contenu public facultatif
- Une période, un événement, une exception ou une offre doit toujours avoir un repère interne permettant de l’identifier facilement dans l’administration.
- Le titre public n’est jamais obligatoire.
- Si aucun titre public n’est souhaité, l’élément peut tout de même être publié et affiché.
- Les traductions FR / EN / DE d’un titre public ne doivent être contrôlées que si ce titre public a réellement été saisi.
- Pour les périodes / événements, le champ de référence est le libellé interne.
- Pour les exceptions, le contexte / motif sert de repère obligatoire.

## Tarifs : plusieurs colonnes de prix
- Prévoir la possibilité d’ajouter une ou plusieurs colonnes tarifaires supplémentaires par catégorie.
- Cas d’usage prioritaire : différencier un tarif « Sur place » et un tarif « En ligne » pour une même ligne (ex. adulte).
- L’interface doit rester simple : une seule colonne si aucun tarif différencié n’est nécessaire, colonnes supplémentaires facultatives.
- Une colonne doit pouvoir être ajoutée, supprimée et donc affichée ou masquée selon les besoins de chaque saison.
- Les colonnes doivent être compatibles FR / EN / DE selon les langues publiques actives.
- À débattre : affichage mobile, ordre des colonnes, ancien prix éventuel et compatibilité PDF.

## Offres tarifaires temporaires
- Prévoir des offres ou billets spéciaux valables sur une période définie, par exemple une offre uniquement pendant le mois de juin.
- Une offre doit pouvoir regrouper plusieurs billets / lignes tarifaires, par exemple Adulte + Enfant, sous une même offre.
- Une offre doit distinguer clairement deux périodes indépendantes : période de vente et période de validité des billets.
- Exemple : billets utilisables du 1er au 30 juin mais vente arrêtée avant le 30 juin afin d’éviter une vente trop tardive.
- Une offre doit pouvoir avoir : repère interne, titre public facultatif, dates de vente, dates de validité, plusieurs lignes de prix, texte explicatif, langue(s), visibilité, canal de vente et éventuellement lien d’achat.
- L’offre ne doit pas remplacer les tarifs standards : elle s’affiche comme une offre complémentaire dans l’onglet tarifaire existant, sans créer un nouvel onglet public obligatoire.
- Une offre doit pouvoir être liée facultativement au moteur de pop-up existant afin d’éviter de saisir deux fois les mêmes dates et informations.
- À débattre : emplacement précis dans le tableau, badge visuel, priorité entre plusieurs offres et intégration PDF.

## Aperçu complet
- L’onglet Aperçu doit conserver la simulation date + heure existante.
- Il doit présenter dans un même endroit ce que verra le visiteur : affichage de la page d’accueil, bloc Aujourd’hui / horaires, pop-up correspondant et tarifs.
- L’aperçu doit pouvoir afficher les tarifs et offres d’une saison brouillon sans les rendre visibles sur le site public.
- L’aperçu doit utiliser autant que possible le même moteur de rendu que le site public et ne pas constituer un second moteur indépendant.

## Préparation de l’année suivante
- Permettre de préparer une saison en brouillon sans effet sur le site public.
- La duplication d’une saison doit créer automatiquement la nouvelle saison en statut brouillon.
- La duplication doit conserver la structure des horaires, périodes, tarifs et autres réglages nécessaires, puis permettre de modifier les dates et valeurs de la nouvelle année.
- Toutes les modifications du brouillon doivent pouvoir être enregistrées autant de fois que nécessaire sans modifier la saison actuellement visible par le public.
- Une saison brouillon doit pouvoir être entièrement visualisée dans l’onglet Aperçu.
- Les dates mobiles ou événements ponctuels doivent être signalés « à vérifier » plutôt que décalés aveuglément.
- Une saison brouillon ne doit jamais être utilisée par le moteur public tant qu’elle n’est pas publiée.
- L’action « Publier » est l’étape explicite qui autorise cette saison à devenir visible au public selon ses dates.
