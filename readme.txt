=== Gestion du parc ===
Contributors: equipe-parcs
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.15.18

Gestion centralisée et multilingue des horaires, calendriers, tarifs, événements, devis groupes et outils du parc.

== Description ==

Cette extension gère plusieurs saisons de parc, les horaires habituels et exceptionnels, le calendrier public, les périodes et événements, les tarifs individuels et groupes, les devis, les guides pédagogiques et le Calendrier de l’Avent.

Les données de chaque saison restent séparées. Les mises à jour sont conçues pour conserver les réglages déjà enregistrés.

== Mise à jour manuelle ==

Téléversez le ZIP depuis Extensions > Ajouter une extension > Téléverser une extension. WordPress peut proposer de remplacer la version installée ; les réglages sont conservés dans la base de données.

Une sauvegarde du site et de la base de données reste recommandée avant toute mise à jour.

== Changelog ==

= 1.15.18 =
* Ajoute une migration unique pour restaurer l’activation du devis groupes 2026 lorsqu’une ancienne mise à jour l’a enregistrée à tort sur NON malgré une grille et un historique de devis valides.
* La migration ne touche jamais une année future comme 2027 et ne modifie aucun tarif.
* Après cette migration, les interrupteurs 2026 et 2027 restent totalement indépendants et un OFF manuel reste définitif.
* Ajoute un test de non-régression reproduisant la restauration 2026 tout en vérifiant que 2027 reste inchangé.

= 1.15.17 =
* Sépare définitivement l’activation du devis groupes de la publication commerciale des tarifs groupes : publier ou préparer 2027 ne peut plus désactiver un devis 2026 historique.
* Lorsqu’une saison possède le nouvel interrupteur `group_quotes_enabled`, celui-ci reste la seule autorité pour cette année.
* Pour les saisons historiques sans cet interrupteur, le moteur restaure l’état depuis les données réellement enregistrées du devis (liaison exacte ou ancienne grille de devis), et non depuis le statut public des tarifs groupes.
* Renforce le test 2026/2027 afin de reproduire explicitement un statut commercial faux tout en exigeant que les devis 2026 et 2027 restent disponibles indépendamment.

= 1.15.16 =
* Corrige le devis groupes lorsqu’une année future comme 2027 possède une liaison tarifaire différente : cette liaison ne peut plus rendre les tarifs 2026 indisponibles.
* Une liaison provenant d’une autre année n’est réutilisée que si tous ses identifiants et prix existent réellement dans la grille de l’année demandée.
* Si une liaison stable manque pour une année historique, le moteur peut reconstruire la liaison de cette année à partir du mapping métier historique au lieu d’utiliser aveuglément une autre année.
* Ajoute un test de non-régression reproduisant le cas 2026 disponible + 2027 configuré avec des identifiants différents.
* Retire la modification d’affichage ajoutée en 1.15.15 : l’affichage public revient au comportement 1.15.14, qui fonctionnait déjà correctement. Le correctif est désormais limité au moteur de devis.

= 1.15.15 =
* Correctif d’affichage 2026/2027 ajouté en urgence puis retiré en 1.15.16 après vérification : le problème observé provenait du moteur de devis et non de l’affichage public.

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
