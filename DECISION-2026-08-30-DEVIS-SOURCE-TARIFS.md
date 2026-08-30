# Décision – source des tarifs pour les devis groupes

Date : 30/08/2026

## Constat
Le module Tarifs de l’extension contient des onglets/catégories dont le libellé public peut être modifié. Il ne faut donc pas baser le moteur de devis sur le simple nom visible d’un onglet comme « Groupes » : ce titre peut changer et n’est pas une clé métier fiable.

## Orientation retenue à ce stade
Pour les devis groupes, conserver des tarifs techniques dédiés dans la rubrique Devis groupes plutôt que de lire automatiquement une catégorie publique uniquement d’après son titre.

Les tarifs nécessaires au devis restent au minimum :
- enfant ;
- adulte ;
- personne en situation de handicap ;
- accompagnateur ;
- règle de gratuité adulte / nombre d’enfants.

Ces valeurs peuvent être différentes des tarifs affichés publiquement si le besoin métier l’exige.

## Évolution à étudier pour éviter la double saisie
Prévoir dans une prochaine version une possibilité explicite de liaison entre une catégorie tarifaire publique et le moteur de devis, sans jamais dépendre du nom de l’onglet.

Deux modes possibles dans l’administration des devis :
1. « Tarifs devis indépendants » : les prix sont saisis directement dans le module Devis groupes ;
2. « Utiliser une catégorie tarifaire existante » : l’administrateur choisit explicitement une catégorie par son identifiant interne stable, puis associe les lignes nécessaires au devis (enfant, adulte, handicap, accompagnateur).

Si le mode lié est utilisé, le nom public de la catégorie peut être modifié sans casser le devis.

## Recommandation UX
Dans « Devis groupes », afficher clairement la source utilisée pour chaque année :
- Tarifs devis indépendants ; ou
- Tarifs liés à une catégorie existante.

Si une liaison n’est pas complète ou qu’une ligne obligatoire manque, ne pas générer de devis chiffré et afficher une erreur d’administration claire.

## Version
Cette évolution nécessite une nouvelle version fonctionnelle de l’extension. Ne pas l’intégrer silencieusement à une release déjà publiée.
