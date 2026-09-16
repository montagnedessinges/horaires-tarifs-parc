=== Gestion du parc ===
Contributors: equipe-parcs
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.16.5

Gestion centralisée et multilingue des horaires, calendriers, tarifs, événements, devis groupes et outils du parc.

== Description ==

Cette extension gère plusieurs saisons de parc, les horaires habituels et exceptionnels, le calendrier public, les périodes et événements, les tarifs individuels et groupes, les devis, les guides pédagogiques et le Calendrier de l’Avent.

Les données de chaque saison restent séparées. Les mises à jour sont conçues pour conserver les réglages déjà enregistrés.

== Mise à jour manuelle ==

Téléversez le ZIP depuis Extensions > Ajouter une extension > Téléverser une extension. WordPress peut proposer de remplacer la version installée ; les réglages sont conservés dans la base de données.

Une sauvegarde du site et de la base de données reste recommandée avant toute mise à jour.

== Changelog ==

= 1.16.5 =
* Corrige l’affichage des tarifs groupes des années futures comme 2027 lorsque l’année est activée dans le shortcode Groupes.
* Une grille groupes est désormais considérée exploitable dès qu’une ligne active contient un tarif, sans dépendre de l’ancien état visible/masqué des colonnes commerciales.
* Le rendu public des groupes projette les anciennes structures tarifaires dans une seule colonne canonique « Tarif » / « Price » / « Preis » sans modifier les données enregistrées.
* Les anciennes lignes groupes dépourvues du champ technique `enabled` restent affichables, conformément au comportement historique des tarifs groupes.
* Conserve la bascule annuelle automatique 1.16.4 et la synchronisation commune des années entre tarifs groupes et horaires groupes.

= 1.16.4 =
* Remplace l’ancienne logique de fenêtre par deux dates maîtresses : avant la date d’apparition les interrupteurs restent manuels, à partir de la date d’apparition tous les modules de l’année sont actifs, et à partir de la date de disparition tous les modules sont inactifs.
* Applique cette bascule au calendrier public, aux tarifs visiteurs, aux horaires groupes, aux devis groupes et aux tarifs groupes sans modifier les données préparées pour les années futures.
* Affiche ensemble les deux dates automatiques dans le bloc « Activation de l’année » et retire le réglage de forçage devenu inutile.
* Corrige le shortcode Groupes : les années autorisées aux groupes sont indépendantes du calendrier visiteurs et un sélecteur annuel commun synchronise les tarifs groupes et le calendrier existant.
* Permet donc d’afficher manuellement les horaires et tarifs groupes 2027 avant la date de bascule, puis de laisser le passage 2026 → 2027 s’effectuer automatiquement à la date choisie.

= 1.16.3 =
* Rétablit le sélecteur d’années dans le shortcode des tarifs groupes : les années publiques disponibles comme 2026, 2027 et 2028 apparaissent de nouveau sous forme d’onglets, avec l’année courante prioritaire.
* Force l’affichage des tarifs réduits en « Sur place » uniquement ; une ancienne valeur rangée dans la cellule En ligne est réutilisée uniquement pour l’affichage Sur place sans modifier les données enregistrées.
* Remplace les libellés de canal des tarifs groupes par le libellé générique « Tarif » / « Price » / « Preis ».
* Conserve l’architecture 1.16.2 : les shortcodes composés continuent d’assembler les shortcodes autonomes existants et le moteur de calendrier n’est pas modifié.

= 1.16.2 =
* Simplifie les shortcodes composés : Horaires & Tarifs assemble désormais directement les shortcodes autonomes de l’état du jour, du calendrier et des tarifs.
* Le shortcode Groupes assemble directement le shortcode des tarifs groupes et le shortcode calendrier existant, sans recréer ni réinjecter un second moteur d’horaires.
* Supprime du chemin public composé les synchronisations JavaScript fragiles et les injections de données parallèles ; chaque bloc garde son propre moteur, ses données et son comportement éprouvé.
* Conserve les shortcodes individuels utilisables séparément tout en gardant les shortcodes communs pour simplifier l’intégration dans les pages WordPress.

= 1.16.1 =
* Restaure le shortcode complet Horaires & Tarifs avec l’état du jour, le calendrier existant et le nouveau tableau de tarifs dans cet ordre.
* Corrige les cellules En ligne vides, supprime les colonnes sans valeur et étend automatiquement une cellule tarifaire unique sur toute la zone disponible.
* Place l’information des tarifs réduits avant les lignes, agrandit le rendu sur ordinateur et mobile et conserve « Tarifs » comme simple texte visuel interne.
* Restaure dans l’espace Groupes le vrai calendrier public, avec Tarifs groupes en premier et une sélection d’année synchronisée entre tarifs et horaires.
* Affiche l’année courante en premier et applique les dates de début / fin d’affichage aux modules publics concernés.

= 1.16.0 =
* Refonte complète du rendu public des tarifs, sans modifier le moteur des horaires ni le calendrier existant.
* Nouveau composant compact et responsive pour les tarifs individuels, réduits et groupes, avec fond transparent conçu pour être intégré dans une section de page blanche.
* Les onglets, années et moyens de paiement restent sur une ligne et utilisent un défilement horizontal local sur les petits écrans, sans provoquer de débordement global de la page.
* Les cellules tarifaires vides ne sont plus rendues ; « Sur place » reste neutre et « En ligne » est visuellement distinct et cliquable lorsqu’un lien d’achat est disponible.
* Le shortcode Groupes ouvre les tarifs en premier puis les horaires, tout en conservant des moyens de paiement et des réglages visuels distincts entre visiteurs et groupes.
* Les couleurs de la charte du parc servent de valeurs par défaut, les titres sont noirs sur les blocs transparents et les réglages de couleurs existants restent personnalisables.

= 1.15.18 =
* Refonte complète du moteur des devis groupes : une date utilise exclusivement l’année correspondante, sans aucun repli vers une année précédente ou future.
* Chaque année possède désormais un état d’activation devis indépendant et versionné ; activer 2027 ne peut plus modifier, masquer ou remplacer 2026.
* Les prix du devis proviennent uniquement de la grille groupes canonique de l’année sélectionnée, même lorsque les identifiants de lignes et de colonnes diffèrent d’une année à l’autre.
* Les anciennes données de devis servent uniquement à une migration initiale des saisons historiques réellement utilisées ; les années futures ne sont jamais activées par déduction.
* Une normalisation unique aligne les anciens boutons d’administration avec le nouvel état afin d’éviter un moteur actif avec un bouton affiché sur NON.
* Les calculs Contact Form 7 restent recalculés côté serveur et les effectifs sont traités comme des nombres entiers.
* Ajoute des tests de non-régression couvrant 2026/2027 simultanément, l’activation et la désactivation indépendante de chaque année ainsi que des grilles et identifiants différents.

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
