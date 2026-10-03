# Mise à jour 1.19.9 — Boutons et liens multilingues de la FAQ

## Objectif

Remplacer les URL affichées en texte brut dans certaines réponses par de vrais boutons cliquables, tout en gardant le contrôle éditorial depuis le Google Sheet.

## Nouvelles colonnes CSV

Les liens déjà présents dans le Google Sheet sont désormais réellement lus par l’import FAQ :

- T — `Lien de redirection FR`
- U — `Weiterleitungslink DE`
- V — `Redirect link EN`

Trois colonnes facultatives sont ajoutées :

- W — `Texte bouton FR`
- X — `Button-Text DE`
- Y — `Button text EN`

## Règle d’affichage

Pour une réponse directe :

- si l’URL et le texte du bouton sont renseignés, un bouton cliquable est affiché ;
- si l’URL existe mais que le texte du bouton est vide, aucun nouveau bouton n’est ajouté automatiquement ;
- les liens dynamiques déjà gérés par la FAQ conservent leur comportement et utilisent le même rendu de bouton.

L’URL sélectionnée dépend de la langue affichée.

## Compatibilité

Les anciennes fiches qui stockent encore une URL unique restent compatibles. Les liens Google Docs, Google Drive et Gmail internes restent bloqués comme liens publics. Les liens HTTPS publics explicitement placés dans les colonnes de redirection restent autorisés.

## Premiers boutons configurés dans le Google Sheet Montagne

- bornes de recharge à proximité ;
- aires de camping-car à proximité ;
- demande de devis groupe ;
- téléchargement du logo et de l’affiche pour les tombolas ;
- achat du Pass’Alsace.

Les URL ont été retirées du texte visible de ces réponses et placées dans les colonnes de redirection.

## Hors périmètre

Aucun changement sur les saisons, horaires, tarifs, devis, guides ou Contact Form 7.
