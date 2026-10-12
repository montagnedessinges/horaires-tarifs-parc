# Gestion du parc 1.20.3 — événements WordPress et indépendance des pop-ups

## Périmètre

L’utilisateur demande une intégration minimale, commune à La Montagne des Singes et La Forêt des Singes, et la suppression réelle de l’ancien chemin de pop-ups événementiels. Le thème confirmé est `single-evenement.php` ; les champs fournis sont `date_debut` et `date_fin`.

## Fonctionnement

- Dans Périodes / événements, « Événements WordPress » permet de sélectionner le type de publication et d’activer/désactiver la lecture. Par défaut : `evenement`, activé ; aucun résultat si ce type n’est pas enregistré et publiquement consultable.
- L’adaptateur lit les publications publiques non protégées et seulement les dates ACF brutes, le titre et le permalien. Aucun contenu, extrait, image ou bloc flexible n’est transféré au calendrier.
- Formats de dates acceptés : Ymd (stockage ACF Date Picker) et Y-m-d. Fin vide : événement d’un jour. Date invalide ou intervalle inversé : ignoré. Les formats ambigus ne sont pas devinés.
- Une projection en mémoire complète les événements manuels dans les saisons déjà autorisées. Aucun événement n’est enregistré dans `parcs_ht_settings`. Le calendrier interactif, le HTML sémantique et le PDF lisent cette même adaptation.
- Le clic sur une date garde le détail existant ; chaque événement possède un bouton vers son article. La pastille jaune actuelle reste inchangée.
- Les événements à cheval sur deux années sont disponibles dans chacune, sans rendre une année non publiée visible. Les horaires à un/deux créneaux ne sont pas modifiés.
- Les titres et liens sont adaptés avec qTranslate-XT, Polylang ou WPML lorsque disponibles. Une traduction distincte absente n’est pas remplacée automatiquement par un autre article. La validation dans les thèmes réels reste à faire avant de conclure à une validation de production.
- La modification/dépublication/suppression d’un événement ou de ses dates invalide les exports et demande une purge LiteSpeed. Les autres caches de pages/CDN doivent suivre leurs mécanismes habituels de purge WordPress.

## Suppression du chemin historique

Les boucles événement/période/exception ont été supprimées de `Parcs_HT_Alerts`, avec leurs méthodes de génération de dates/messages. Les formulaires PHP n’émettent plus les contrôles correspondants : aucun masquage CSS/JS n’est nécessaire. La migration événement/exception du module 1.20.0 est retirée. Les données anciennes de saisons sont conservées pour éviter une migration destructive, mais ne constituent plus une source exécutable de pop-up.

Les pop-ups visuels autonomes déjà enregistrés dans `parcs_ht_popups_1200` sont conservés. Le calendrier de l’Avent continue d’utiliser ses propres alertes ; son chargement du moteur ne peut plus réactiver un événement ou une exception.

## Compatibilité et validation

Le code reste compatible avec WordPress 6.0 et PHP 7.4. Les tests dédiés couvrent les dates, le changement d’année, le titre/lien sans contenu, les brouillons, les publications protégées, la dépublication, la modification des dates, l’absence de copie dans les saisons et la suppression du chemin historique. Ils sont exécutés sur les sources et le paquet nettoyé dans la matrice CI PHP 7.4/8.1/8.2/8.3.

La validation automatisée ne prouve pas l’installation ou la configuration des deux WordPress. Aucun site de production n’a été ouvert ni modifié dans cette intervention.

## Étape thème

Aucun changement de `single-evenement.php` n’est requis pour alimenter le calendrier : la lecture a lieu directement dans WordPress. La présentation des dates dans ce fichier peut être revue ensuite, après sauvegarde de sa version exacte actuelle. Ne pas supprimer ses blocs ACF `singleevenements` ni son contenu d’article.

Point de restauration : v1.20.2. Désactiver la lecture WordPress dans l’écran Périodes revient aux seuls événements manuels, sans perte de données.
