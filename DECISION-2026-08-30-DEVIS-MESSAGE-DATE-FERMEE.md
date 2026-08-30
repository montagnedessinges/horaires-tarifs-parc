# Décision – message paramétrable pour les dates de visite fermées

Date : 30/08/2026

## Besoin validé

Pour le parcours de génération automatique de devis groupes, le formulaire CF7 reste accessible et continue à générer automatiquement le devis/PDF.

La date de visite doit être placée en premier dans le formulaire afin que l’extension puisse déterminer immédiatement l’année tarifaire et recalculer les montants avec la grille correspondante.

Si la date choisie correspond à une période où le parc est fermé au public, l’extension ne doit pas bloquer ni masquer le formulaire de devis.

Elle doit afficher au-dessus du formulaire un message d’information spécifique, par exemple :

> Le parc est fermé au public à cette date. Une visite de groupe peut toutefois être possible. Merci de nous contacter en indiquant la date et l’horaire souhaités afin que nous puissions vérifier si nous pouvons vous accueillir. Vous pouvez malgré tout générer votre devis automatiquement ci-dessous.

La prise de contact concerne uniquement la faisabilité de l’accueil à la date et à l’horaire souhaités. Le devis, lui, reste généré automatiquement par le formulaire ; l’équipe ne traite pas manuellement une « demande de devis ».

## Paramétrage obligatoire dans l’administration

Cette fonction doit être entièrement paramétrable par l’administrateur du site :

- option pour activer ou désactiver le message lié aux dates fermées ;
- texte du message modifiable sans changer le code ;
- possibilité de définir/modifier l’adresse e-mail ou le lien de contact affiché ;
- idéalement contenu FR / EN / DE selon les langues publiques actives ;
- le formulaire CF7 reste affiché même lorsque le message est actif ;
- désactiver l’option doit supprimer ce comportement sans supprimer les réglages enregistrés.

## Logique métier

- Date ouverte + tarifs disponibles : formulaire normal, tarifs de l’année appliqués automatiquement.
- Date fermée + tarifs disponibles : message informatif au-dessus du formulaire, mais devis toujours générable automatiquement.
- Année sans grille de tarifs groupes publiée : ne pas produire de devis chiffré avec une ancienne grille en secours ; afficher le message d’indisponibilité tarifaire prévu par le moteur devis.

## Version

Cette évolution est fonctionnelle et nécessite une nouvelle version de l’extension si elle n’est pas déjà incluse dans la dernière version publiée.
