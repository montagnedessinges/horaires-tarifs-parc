# Audit du thème Montagne des Singes — événements (12/10/2026)

## Source
Archive `themes.zip` fournie par l'utilisateur, thème `montagnedessinges/`, 175 entrées ZIP au total.
Archive inspectée hors site, sans installation ni intervention sur WordPress.
Ne pas publier l'archive propriétaire du thème dans ce dépôt.

## Faits vérifiés dans les fichiers
- `functions.php` lignes 93–104 : le thème déclare le CPT public `evenement`, avec titre, contenu, image principale et révisions.
- `page.php` charge `templates/centre.php` pour les pages du thème.
- `templates/centre.php` lignes ~230–342 : bloc flexible `sc-3col-txt-sld` (carrousel de six événements) qui interroge directement `WP_Query(post_type=evenement)` et les métas ACF `date_debut`/`date_fin`.
- `templates/centre.php` lignes ~515–645 : bloc flexible `block_evenements_liste`, qui fait sa propre requête événementielle et utilise les mêmes champs ACF, les images et les permaliens.
- `single-evenement.php` du ZIP est le template historique fourni par l'utilisateur : titre, image principale, `the_content()`, date ACF et blocs flexibles `singleevenements`; le nouveau template proposé dans le chat **n'est pas encore installé**.
- La logique du thème ne lit pas les métadonnées `_parcs_ht_event_calendar_*` et ne dépend pas de la classe `Parcs_HT_WordPress_Events`.

## Compatibilité avec 1.20.4
- Ne pas changer `post_type=evenement` ni les champs ACF `date_debut`/`date_fin`, ni les titres, images et permaliens.
- Les préférences de marqueur doivent être enregistrées dans de nouvelles métadonnées privées du même article, sans affecter les requêtes du thème.
- Masquer un événement du **calendrier de l'extension** ne doit pas le dépublier de WordPress, ni le retirer du carrousel/listing du thème.
- Les deux sites utilisent le même code d'extension mais des paramètres WordPress propres à leur installation.

## Risques préexistants (non corrigés par cette version)
- Le carrousel applique `strtotime($datedebut . "+1 days")` et `strtotime($datefin . "+1 days")` pour **l'affichage seulement**. Cela semble provoquer un décalage d'un jour ; vérifier sur un événement concret avant de modifier.
- Les deux requêtes du thème utilisent `date('Ymd')` et des comparaisons numériques de métas ACF ; elles supposent que les dates sont stockées sous la forme brute ACF `Ymd`. Le format réel de la base WordPress ne figure pas dans le ZIP.
- Des champs `date_fin` réellement absents (au lieu de vides) peuvent ne pas être reconnus par les requêtes historiques.
- Les textes et liens de certains boutons EN/DE du carrousel sont codés en dur, certains vers `#`.
- `block_evenements_liste` effectue une requête supplémentaire pour compter les événements ; le calcul de compteur porte sur tous les articles plutôt que seulement les éléments à venir.

## Limites
L'archive ne contient ni base de données WordPress, ni configuration stockée d'ACF, ni paramètres réels des deux sites.
L'audit statique ne vaut pas test dans un vrai navigateur WordPress.
Aucun changement de thème n'est requis par la fonction de marqueurs du calendrier.
