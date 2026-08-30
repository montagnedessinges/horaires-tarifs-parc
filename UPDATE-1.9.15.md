# Mise à jour 1.9.15 — accès au formulaire de devis par date

## Problème constaté

Le comportement de la 1.9.14 ne correspondait pas au besoin réel : le contrôle de date agissait directement à l’intérieur du formulaire Contact Form 7 et pouvait masquer des éléments du formulaire de façon fragile. Le besoin validé est différent : tous les textes et contenus de la page Devis groupe doivent rester visibles normalement, et seul le formulaire Contact Form 7 doit rester fermé tant que le visiteur n’a pas choisi une date.

## Comportement attendu et implémenté

- Les contenus configurés dans Devis groupe restent toujours visibles : titre, introduction, messages importants, liens rapides, blocs d’information et accordéons.
- Un bloc indépendant « Date de visite » est affiché avant le formulaire.
- Le formulaire Contact Form 7 complet est masqué tant qu’aucune date valide et tarifable n’a été sélectionnée.
- Quand la date correspond à une année disposant d’une grille de tarifs groupes publiée, le formulaire complet s’affiche.
- La date choisie dans le bloc d’accès est automatiquement recopiée dans le champ de date Contact Form 7.
- Une date de fermeture publique ne bloque pas le devis : le message configuré peut être affiché et le formulaire s’ouvre.
- Si les tarifs de l’année sont indisponibles, le formulaire reste fermé et le message configuré s’affiche.
- Les scripts restent limités aux formulaires situés dans le module `.parcs-ht-quote`.

## Correction technique

L’ancien mécanisme basé sur la structure interne `.cp` / `.flex-xbetween` du formulaire CF7 a été supprimé du contrôle d’accès. Cette structure était trop dépendante du HTML du formulaire et pouvait masquer des éléments non prévus.

Le nouveau mécanisme pilote uniquement la visibilité de `.parcs-ht-quote-form-wrap` et ajoute un sélecteur de date indépendant avant ce bloc. Les textes du module ne sont donc plus concernés par le masquage.

## Fichiers concernés

- `assets/quote-gate.js`
- `assets/quote-gate.css`
- `horaires-tarifs-parc.php`

## Règle de non-régression

Toute future évolution du contrôle de date doit respecter cette séparation : les contenus éditoriaux du module Devis groupe sont toujours visibles ; seule la partie formulaire CF7 est conditionnée par le choix de la date.
