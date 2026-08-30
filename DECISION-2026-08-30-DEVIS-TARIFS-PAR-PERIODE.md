# Décision — devis groupes piloté par la date et tarifs par période — 30/08/2026

## Principe confirmé
- Le visiteur ne choisit jamais manuellement « tarifs 2026 », « tarifs 2027 » ou une année tarifaire.
- La date de visite saisie dans Contact Form 7 est la seule donnée de référence pour sélectionner automatiquement la grille tarifaire applicable.
- Si une grille couvre la date choisie et est publiée/disponible pour les devis, le formulaire se déverrouille et les calculs utilisent cette grille.
- Si aucune grille ne couvre la date choisie, le formulaire ne doit pas générer de devis chiffré. Il doit afficher un message du type : « Les tarifs groupes correspondant à cette période ne sont pas encore disponibles. Merci de revenir ultérieurement ou de nous contacter. »
- Il ne faut jamais réutiliser silencieusement les tarifs d’une année ou période précédente comme secours.

## Tarifs spéciaux par période
Prévoir une architecture suffisamment souple pour gérer plusieurs grilles sur une même année si nécessaire, même si aucun cas précis n’est encore connu.

Exemples possibles :
- grille standard 2027 ;
- grille spéciale du 1er au 30 juin 2027 ;
- grille spéciale vacances / événement / opération commerciale ;
- autre période limitée dans le temps.

Chaque grille de devis groupes devrait pouvoir comporter au minimum :
- repère interne ;
- date de début de validité ;
- date de fin de validité ;
- statut disponible/publié pour les devis ;
- tarif enfant ;
- tarif adulte ;
- tarif personne en situation de handicap ;
- tarif accompagnateur ;
- règle d’adulte gratuit ;
- éventuellement un libellé public/interne de la grille.

La sélection se fait par date de visite, pas simplement par année civile.

## Traçabilité obligatoire sur le PDF
Le PDF doit permettre à l’équipe de savoir immédiatement quelle grille a servi au calcul.

Le comportement actuel validé du titre dynamique doit être conservé :
- date 2026 avec grille 2026 → `DEVIS 2026` ;
- date 2027 avec grille 2027 → `DEVIS 2027`.

En complément, prévoir un libellé explicite de la grille utilisée, par exemple :
- `Tarifs appliqués : 2027` ;
- ou, si une grille spéciale existe, `Tarifs appliqués : Tarif spécial juin 2027`.

Le libellé ne doit jamais être déduit uniquement du texte du PDF : il doit être alimenté par la grille réellement sélectionnée par le moteur de devis.

## Objectif de contrôle
Cette traçabilité permet :
- de vérifier rapidement la grille appliquée au devis ;
- d’expliquer au client sur quelle base son devis a été calculé ;
- d’identifier une éventuelle mauvaise date ou mauvaise grille ;
- de conserver un historique compréhensible si les prix changent plus tard.

## Contact Form 7
CF7 reste le formulaire public. L’extension Horaires & Tarifs Parc doit devenir la source de vérité pour déterminer la grille et les prix selon la date de visite.

Le JavaScript peut gérer l’expérience instantanée :
- verrouillage tant qu’aucune date n’est choisie ;
- disponibilité de la grille ;
- déverrouillage des quantités ;
- calcul en direct ;
- message d’indisponibilité.

Une validation serveur doit confirmer à l’envoi que la date possède toujours une grille valide et recalculer les données de référence avant génération du PDF, afin d’éviter la manipulation des prix côté navigateur.

## Versioning
Cette évolution modifie le fonctionnement du plugin et nécessitera une nouvelle version de l’extension. Si la 1.9.9 est déjà publiée, utiliser 1.9.10 ou une version supérieure.

## Conservation du PDF
La mise en page du PDF historique qui fonctionne est la base stable. Ne pas la refondre. Les évolutions liées aux périodes tarifaires doivent se limiter aux champs dynamiques et aux libellés nécessaires à la traçabilité, sans changer la composition visuelle sauf demande explicite.