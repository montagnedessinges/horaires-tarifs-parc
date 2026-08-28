# Roadmap – prochaines évolutions à discuter

Ce fichier consigne les idées validées ou à débattre avant développement. Ne pas implémenter automatiquement ces points sans nouvelle validation utilisateur.

## Tarifs : plusieurs colonnes de prix
- Prévoir la possibilité d’ajouter une ou plusieurs colonnes tarifaires supplémentaires par catégorie.
- Cas d’usage prioritaire : différencier un tarif « Sur place » et un tarif « En ligne » pour une même ligne (ex. adulte).
- L’interface doit rester simple : une seule colonne si aucun tarif différencié n’est nécessaire, colonnes supplémentaires facultatives.
- Les colonnes doivent être compatibles FR / EN / DE selon les langues publiques actives.
- À débattre : structure des colonnes, affichage mobile, ordre des colonnes, ancien prix éventuel et compatibilité PDF.

## Offres tarifaires temporaires
- Prévoir des offres ou billets spéciaux valables sur une période définie, par exemple une offre uniquement pendant le mois de juin.
- Une offre doit pouvoir avoir : titre, dates de validité, prix, texte explicatif, langue(s), visibilité, canal de vente et éventuellement lien d’achat.
- L’offre ne doit pas remplacer les tarifs standards : elle s’affiche comme une offre complémentaire uniquement pendant sa période prévue.
- À débattre : emplacement dans le tableau, badge visuel, priorité entre plusieurs offres, affichage hors période et intégration PDF.

## Préparation de l’année suivante
- Permettre de préparer une saison en brouillon sans effet sur le site public.
- Prévoir la duplication de l’année précédente pour conserver la structure des horaires, périodes, tarifs et autres réglages, puis modifier les dates et valeurs nécessaires.
- Les dates mobiles ou événements ponctuels doivent être signalés « à vérifier » plutôt que décalés aveuglément.
- Une saison brouillon ne doit jamais être utilisée par le moteur public tant qu’elle n’est pas publiée.
