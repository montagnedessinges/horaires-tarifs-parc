# Mise à jour 1.20.4 — marqueurs des événements WordPress

## Fonctionnement
- Dans chaque événement du type WordPress sélectionné dans Gestion du parc > Périodes / événements, un encadré `Affichage dans le calendrier` est ajouté automatiquement.
- Chaque événement peut être visible ou masqué dans le calendrier sans changer sa publication WordPress.
- Couleur du marqueur (défaut : `#e7c55b`) et type : Automatique / Grande étoile / Petit point.
- Automatique : étoile si `date_debut` et `date_fin` désignent la même journée, point sur chaque journée d'un événement pluri-journalier.
- Trois marqueurs maximum sont dessinés dans une même case ; un indicateur `+N` signale les événements supplémentaires. Le détail du jour continue de lister tous les événements visibles avec chacun sa couleur et son lien.
- Les anciennes saisies manuelles restent inactives. Aucun pop-up événementiel n'est réintroduit.
- Les traductions FR/EN/DE continuent de passer par la projection existante ; une traduction absente n'est pas inventée.
- Masquer un événement du calendrier évite son émission dans les projections publiques et le HTML sémantique de l'extension. La page WordPress elle-même reste publique si son statut est publié.

## Séparation thème / plugin
Audit du thème Montagne des Singes : `AUDIT-2026-10-12-THEME-EVENEMENTS.md`.
Le CPT `evenement` et les champs ACF `date_debut`/`date_fin` sont conservés intacts.
Les nouveaux réglages sont enregistrés en métadonnées privées `_parcs_ht_event_calendar_*`.
Ni `single-evenement.php` ni les blocs du carrousel/listing d'accueil ne sont modifiés.
Le même mécanisme est disponible pour La Forêt des Singes, sur son type de publication configuré localement.

## Sécurité et performances
Nonce et droit `edit_post` exigés pour écrire ces réglages.
Valeurs limitées à `auto`/`star`/`dot` et couleur hexadécimale à six chiffres.
Les événements non publiés, protégés ou invalides restent exclus.
Le rendu public ne charge aucun script supplémentaire et utilise les fichiers `frontend.js` / `frontend.css` existants.
Les changements de méta invalident le cache des exports et demandent la purge LiteSpeed via les hooks existants.

## Avant installation
- Les tests automatisés et Plugin Check doivent être verts.
- Vérifier le rendu sur les pages de calendrier du parc, y compris mobile, deux événements le même jour, événements à cheval sur deux années, et la séparation FR/EN/DE.
- Vérifier qu'un événement masqué dans le calendrier reste dans le widget d'accueil du thème si sa date est encore pertinente.
- Faire une sauvegarde avant toute mise à jour du site.
- Aucun thème WordPress n'a été modifié par cette release.
