# Audit — indépendance des couleurs des shortcodes tarifs — 07/09/2026

## Constat avant 1.13.8

- `[parc_tableau_tarifs]` et `[parc_tarifs_groupes]` lisaient correctement la même grille `tariffs.groups`.
- Le shortcode groupes reconstruisait cependant ses variables CSS à partir de `settings.general`, donc des mêmes couleurs générales que le tableau principal.
- Les styles de ligne (`label_color`, `price_color`, fond, bordure, etc.) étaient également lus directement sur les lignes tarifaires partagées.
- Résultat : les données étaient correctement mutualisées, mais le visuel n’était pas réellement indépendant.

## Correction retenue

Conserver une source tarifaire unique et séparer uniquement la présentation. Le shortcode groupes possède désormais sa propre palette et ses propres styles de lignes, migrés une fois depuis l’apparence existante. Aucun nouveau système tarifaire n’est créé.
