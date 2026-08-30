# Mise à jour 1.9.13 — correctif interface Devis groupe

## Objectif
Corriger l'organisation de l'administration des devis sans modifier le moteur de calcul, les tarifs existants, Contact Form 7 ni le fonctionnement public des devis.

## Corrections
- Le sous-onglet séparé `Formulaires FR / EN / DE` est supprimé.
- Dans `Devis groupe > Contenu`, le réglage du formulaire Contact Form 7 est présenté directement avec les sélecteurs `FR / EN / DE`, comme les autres champs multilingues de l'extension.
- Les trois formulaires restent stockés dans l'option technique existante `parcs_ht_quote_language_shortcodes` et continuent d'alimenter `[parc_devis_groupe_fr]`, `[parc_devis_groupe_en]` et `[parc_devis_groupe_de]`.
- Le formulaire général historique reste conservé en secours afin de ne pas casser les installations existantes, mais son champ séparé n'est plus affiché dans cette interface pour éviter le doublon visuel.
- `Accès au devis` n'apparaît plus comme sous-menu WordPress séparé sous `Horaires du parc`.
- Les réglages d'accès au devis sont désormais intégrés comme sous-onglet de `Devis groupe`.
- Les valeurs existantes d'accès au devis sont conservées : activation du choix de date, message de fermeture, contact, message de tarifs indisponibles et contact associé.
- `Tarifs du devis <année>` reste lié à la saison sélectionnée dans l'administration.

## Structure visée
`Devis groupe` contient désormais :
1. `Contenu`
2. `Tarifs du devis <année>`
3. `Accès au devis`

Dans `Contenu`, le shortcode Contact Form 7 se règle directement via `FR / EN / DE` au-dessus du champ de saisie, au même endroit que les autres contenus multilingues.

## Sécurité / compatibilité
- Les sauvegardes spécifiques des formulaires multilingues, des tarifs du devis et de l'accès au devis utilisent des actions AJAX réservées aux administrateurs avec nonce WordPress.
- Aucun tarif, calcul serveur ou contrôle de date public n'est remplacé par cette correction.
- Aucun ancien réglage n'est supprimé.
- La 1.9.12 publiée reste intacte ; ce correctif est livré dans une nouvelle version 1.9.13 conformément à la règle d'immutabilité des releases.
