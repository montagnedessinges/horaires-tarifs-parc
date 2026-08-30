# Roadmap – devis groupes : contrôle préalable par date et jours fermés

Décision / besoin exprimé le 30/08/2026. Ce document complète `ROADMAP.md` et doit être repris avant toute prochaine évolution du module de devis groupes.

## Principe général
- Le plugin sert de filtre avant l'accès au formulaire de devis CF7.
- À l'arrivée sur la page devis, le visiteur renseigne d'abord une date de visite.
- Le plugin contrôle cette date avant d'afficher ou d'autoriser l'accès au formulaire de devis complet.
- Le formulaire CF7 reste le formulaire de devis principal ; le moteur du plugin fournit ensuite automatiquement les tarifs correspondant à l'année de la date choisie.
- Dans le formulaire CF7, la date de visite doit être placée avant les effectifs afin que les calculs utilisent immédiatement la bonne grille tarifaire.

## Cas d'une date où le parc est fermé
- Le contrôle préalable doit pouvoir détecter qu'une date choisie correspond à une période où le parc est fermé au public.
- Dans ce cas, le plugin ne doit pas envoyer le visiteur directement dans le formulaire de devis standard.
- Il doit afficher un message configurable dans l'administration, par exemple : « Le parc est fermé à cette période. Vous pouvez toutefois nous adresser une demande de visite par mail afin que nous vérifiions si nous pouvons vous accueillir. »
- Le message doit pouvoir contenir une adresse e-mail ou un lien de contact configurable ; ne pas coder une adresse en dur dans le moteur.
- Le comportement doit rester paramétrable : une date fermée au public peut être traitée comme une demande exceptionnelle plutôt que comme une interdiction absolue de visite de groupe.

## Administration souhaitée
- Prévoir des réglages permettant de choisir le comportement pour les dates fermées : message public, adresse/lien de contact, et éventuellement autorisation ou non de poursuivre vers le devis.
- Le texte public doit être modifiable sans modifier le code.
- Réutiliser autant que possible les horaires/saisons déjà connus par l'extension pour déterminer si le parc est ouvert ou fermé à la date choisie ; éviter un second calendrier indépendant.

## Tarifs
- Une fois une date admissible choisie, l'année de cette date détermine automatiquement la grille de tarifs groupes publiée.
- Aucun tarif d'une autre année ne doit être utilisé en secours.
- Si les tarifs de l'année ne sont pas disponibles, le devis chiffré ne doit pas être généré ; afficher à la place un message configurable.

## Version
Cette évolution modifie le comportement fonctionnel public du module devis et nécessitera une nouvelle version de l'extension si elle est développée après publication de la 1.9.10. Ne pas modifier une release déjà publiée ; utiliser la version suivante disponible.