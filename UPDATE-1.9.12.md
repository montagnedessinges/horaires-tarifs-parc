# Mise à jour 1.9.12

## Objectif

Réorganiser l’administration sans refaire les moteurs existants et rendre enfin accessibles les tarifs du devis groupe pour la saison sélectionnée.

## Administration

L’onglet principal **Horaires & calendrier** regroupe désormais en sous-onglets :

- Horaires & calendrier
- Périodes & événements
- Exceptions
- Accès limité

Les données et moteurs existants ne sont pas déplacés ni réécrits : la 1.9.12 change principalement leur présentation dans l’administration.

L’onglet principal **Tarifs** reste indépendant.

## Devis groupe

Dans **Devis groupe**, un sous-onglet **Tarifs du devis [année]** affiche uniquement la grille correspondant à la saison actuellement sélectionnée dans l’administration.

Champs :

- disponibilité de la grille pour les devis automatiques ;
- tarif enfant ;
- tarif adulte ;
- tarif personne en situation de handicap ;
- tarif accompagnateur ;
- règle « 1 adulte gratuit pour X enfants ».

Ces valeurs utilisent le moteur `Parcs_HT_Group_Quotes` déjà existant. Il n’y a pas de deuxième moteur de calcul.

Le tableau public **Tarifs** et les tarifs techniques du devis groupe restent deux sources distinctes. Modifier les tarifs du devis ne modifie pas le tableau public.

Le changement de saison dans le gestionnaire existant (2026, 2027...) change automatiquement la grille de devis affichée.

## Compatibilité et sécurité

- aucun changement du calcul public des horaires ;
- aucun changement du moteur de calcul serveur des devis ;
- aucun ancien tarif utilisé en secours lorsqu’une année n’est pas publiée ;
- enregistrement des tarifs devis protégé par capacité `manage_options` et nonce WordPress ;
- la saison envoyée doit réellement exister dans les saisons du plugin ;
- les prix sont validés côté serveur.

## Menu technique historique

Le sous-menu technique séparé `Tarifs devis groupes` est retiré de la navigation afin d’éviter deux interfaces pour la même donnée. Les données existantes sont conservées et restent stockées dans `parcs_ht_group_quotes`.

## Portée volontairement limitée

Cette version n’est pas une refonte des données du plugin. Elle regroupe l’interface et raccorde la grille devis à la saison sélectionnée. Les autres regroupements éventuels (Pop-up, Aperçu, Mises à jour, Shortcodes) ne sont pas modifiés sans décision explicite.
