# Mise à jour 1.13.8 — couleurs indépendantes par shortcode

Cette version applique le résultat de l’audit du 7 septembre 2026 : `[parc_tableau_tarifs]` et `[parc_tarifs_groupes]` partagent les mêmes données de tarifs groupes, mais ne partagent plus leurs réglages visuels.

## Principe

- Source de données unique : `tariffs.groups`.
- Apparence du tableau général : réglages existants de « Tarifs → Apparence des tarifs ».
- Apparence du shortcode groupes : réglages séparés dans « Groupes → Tarifs → Apparence du shortcode Tarifs groupes ».
- Les couleurs de lignes sont elles aussi séparées par identifiant permanent.

Exemple attendu et testé : le tableau général peut être violet tandis que le shortcode groupes est rose, sans modifier les prix ni créer une deuxième grille tarifaire.

## Migration

À la première migration vers 1.13.8, les couleurs qui étaient jusque-là héritées du tableau général sont copiées dans les réglages du shortcode groupes afin de conserver le visuel existant. Cette copie n’est effectuée qu’une fois. Ensuite, chaque shortcode évolue indépendamment.

Les tests ciblés de migration, de rendu du shortcode groupes, de publication des tarifs groupes et de gratuité groupes ont été validés avant le déclenchement de la release. La régression de compatibilité des tests hors environnement WordPress a également été corrigée et validée.
