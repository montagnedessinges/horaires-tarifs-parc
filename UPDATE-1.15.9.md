# Mise à jour 1.15.9 — saisons publiques et archives Avent

## Affichage multi-années

- Une seule saison publique disponible : les shortcodes conservent leur affichage actuel, sans onglet supplémentaire.
- Plusieurs saisons publiques encore visibles : les tarifs et la page Horaires & Tarifs affichent un sélecteur d’année (par exemple 2026 / 2027).
- Le calendrier horaire autonome conserve son sélecteur d’année natif déjà présent dans le moteur.
- Le shortcode `parc_tarifs_groupes` applique la même logique de sélection d’année que les tarifs généraux.

## Date de fin d’affichage d’une saison

- Ajout dans l’administration d’un champ **« Afficher cette année jusqu’au »** pour chaque saison.
- La date est enregistrée dans la saison sous `public_display_until`.
- La saison reste visible pendant toute la date choisie et disparaît automatiquement le lendemain.
- Une valeur vide signifie qu’aucune date automatique de retrait n’est définie.
- Ce retrait public ne supprime ni ne modifie les horaires, tarifs, événements ou données enregistrées de la saison.

## Calendrier de l’Avent : éditions archivables

- Le Calendrier de l’Avent reste totalement séparé du cycle des saisons horaires/tarifs.
- Les campagnes conservent les statuts existants `brouillon`, `active` et `archivee`.
- `[parc_calendrier_avent]` affiche en priorité la campagne active ; s’il n’y en a plus, il peut afficher la campagne archivée la plus récente.
- Une édition précise peut être appelée avec `[parc_calendrier_avent id="avent-2026"]`.
- L’attribut historique `campagne="..."` reste compatible.
- Le même principe s’applique à `[parc_reglement_avent]`.
- Une campagne en brouillon ne devient jamais accessible publiquement par ce mécanisme.
- Les requêtes AJAX d’une campagne archivée restent rattachées à cette campagne afin que les anciennes cases puissent être consultées.

## Compatibilité

- Aucune donnée existante n’est réinitialisée.
- Les saisons et campagnes existantes restent conservées.
- Les shortcodes existants restent valides.
- Les variantes FR / EN / DE continuent de fonctionner.
- Version de l’extension : **1.15.9**.
