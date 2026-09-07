# 1.14.0 — Bascule commerciale des tarifs groupes

Cette version sépare l’année commerciale des tarifs groupes de la saison publique générale.

- Une année de tarifs groupes peut être publiée et utilisée par le devis même si la saison publique générale de la même année reste en brouillon.
- Groupes → Tarifs dispose d’une date de bascule d’affichage propre au shortcode `[parc_tarifs_groupes]`.
- Sans date personnalisée, le comportement de compatibilité reste une activation au 1er janvier de l’année concernée.
- Le shortcode groupes lit intégralement la grille canonique de l’année commerciale sélectionnée : prix, année du titre et présentation restent cohérents.
- Le devis continue de choisir les tarifs selon l’année de la date de visite, indépendamment de la date de bascule du shortcode groupes.
- L’administration affiche l’état de la grille, de la publication groupes, de la liaison devis et de la saison publique générale, avec des avertissements si une bascule approche ou est dépassée sans préparation complète.
- Une purge LiteSpeed est planifiée lors d’une bascule future afin d’éviter qu’une page mise en cache conserve l’ancienne année.
- Les tarifs publics individuels et réduits ne sont pas basculés par ce mécanisme.
