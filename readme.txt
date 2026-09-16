=== Gestion du parc ===
Contributors: equipe-parcs
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.15.15

Gestion centralisée et multilingue des horaires, calendriers, tarifs, événements, devis groupes et outils du parc.

== Description ==

Cette extension gère plusieurs saisons de parc, les horaires habituels et exceptionnels, le calendrier public, les périodes et événements, les tarifs individuels et groupes, les devis, les guides pédagogiques et le Calendrier de l’Avent.

Les données de chaque saison restent séparées. Les mises à jour sont conçues pour conserver les réglages déjà enregistrés.

== Mise à jour manuelle ==

Téléversez le ZIP depuis Extensions > Ajouter une extension > Téléverser une extension. WordPress peut proposer de remplacer la version installée ; les réglages sont conservés dans la base de données.

Une sauvegarde du site et de la base de données reste recommandée avant toute mise à jour.

== Changelog ==

= 1.15.15 =
* Corrige en urgence la coexistence des tarifs groupes 2026 et 2027 : publier une année future ne remplace plus automatiquement l’année en cours dans l’affichage standard.
* Lorsque plusieurs années de tarifs groupes sont disponibles, l’année courante est sélectionnée par défaut et chaque année reste accessible via son sélecteur.
* Une sélection explicite d’année continue de fonctionner, notamment dans le portail Groupes, sans modifier le moteur de devis par date de visite.
* Ajoute un contrat de non-régression afin qu’une année future publiée ne puisse plus masquer silencieusement l’année courante.

= 1.15.14 =
* Ajoute une recherche instantanée et purement visuelle dans Périodes repères, Événements, Exceptions et Accès temporairement limité, avec compteur, effacement rapide et message lorsqu’aucune ligne ne correspond.
* La recherche tient compte des libellés, titres, dates, types et contenus déjà présents dans les champs sans modifier, supprimer ni réordonner les données du formulaire.
* Étend l’outil CSV avec un export de la saison et un type canonique `limited_access` pour les règles d’accès temporairement limité, traductions comprises.
* L’import valide le fichier complet avant écriture, crée toujours une révision de sécurité et continue de préserver les catégories absentes du CSV.
* Les quatre sections concernées indiquent clairement qu’elles sont couvertes par l’outil CSV commun.
* Ajoute une documentation du format CSV et des contrats de non-régression dédiés.

= 1.15.13 =
* Stabilise la navigation de l’administration : les onglets Groupes et Horaires n’effacent plus les onglets canoniques après chargement et s’appuient sur un seul moteur d’affichage des panneaux.
* Conserve le contexte Groupes → Tarifs après une sauvegarde et isole correctement l’écran Guides pédagogiques des commandes du formulaire principal.
* Remplace l’ancien pilotage global Brouillon / Publié par cinq activations annuelles indépendantes : calendrier public, tarifs visiteurs, horaires groupes, devis groupes et tarifs groupes publics.
* Une année dupliquée démarre avec toutes ses activations publiques désactivées afin de pouvoir être préparée sans publication involontaire.
* Corrige le contrôle de date du devis groupes : une année ou une date sans horaire exploitable est considérée fermée par défaut, tout en laissant le devis possible lorsque les tarifs sont disponibles.
* Rejette les dates calendaires impossibles dans le contrôle préalable du devis.
* Ajoute des contrats de non-régression dédiés à la navigation d’administration, aux activations annuelles et au comportement fail-closed des dates de devis.

= 1.15.12 =
* Stabilisation du sélecteur annuel des tarifs visiteurs : les années affichées sont contrôlées explicitement et ne remplacent plus silencieusement l’année courante.
* Les boutons d’année des tarifs fonctionnent comme des onglets sans rechargement complet de la page, tout en conservant un lien de secours accessible.
* Le titre public reste « Tarifs » sans année ; les moyens de paiement restent communs au-dessus des années.
* Correction du devis groupes pour les saisons futures publiées, notamment 2027, avec réutilisation sûre des liaisons tarifaires stables lorsqu’elles correspondent.
* Le calendrier conserve son propre sélecteur d’année indépendant.
* Le contrôle complet de l’extension n’est plus lancé automatiquement au chargement de l’administration ; il reste disponible manuellement.
* Réduction des observateurs JavaScript globaux afin d’éviter des traitements inutiles sur les pages publiques et dans l’administration.

= 1.15.10 =
* Gestion multi-années des horaires et tarifs avec dates de visibilité et forçage manuel.
* Tarifs individuels et réduits simplifiés en deux canaux : En ligne et Sur place.
* Horaires futurs affichables aux groupes indépendamment du grand public.
* Nouveau shortcode groupes réunissant horaires d’ouverture et tarifs groupes.
* Archives indépendantes pour les éditions du Calendrier de l’Avent.
* Import CSV des horaires et du calendrier par saison : périodes habituelles, exceptions, fermetures, jours fériés, périodes repères, vacances scolaires et événements.
* Modèle CSV téléchargeable et révision de sécurité automatique avant import.
