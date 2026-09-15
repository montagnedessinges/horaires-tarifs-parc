=== Gestion du parc ===
Contributors: equipe-parcs
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.15.12

Gestion centralisée et multilingue des horaires, calendriers, tarifs, événements, devis groupes et outils du parc.

== Description ==

Cette extension gère plusieurs saisons de parc, les horaires habituels et exceptionnels, le calendrier public, les périodes et événements, les tarifs individuels et groupes, les devis, les guides pédagogiques et le Calendrier de l’Avent.

Les données de chaque saison restent séparées. Les mises à jour sont conçues pour conserver les réglages déjà enregistrés.

== Mise à jour manuelle ==

Téléversez le ZIP depuis Extensions > Ajouter une extension > Téléverser une extension. WordPress peut proposer de remplacer la version installée ; les réglages sont conservés dans la base de données.

Une sauvegarde du site et de la base de données reste recommandée avant toute mise à jour.

== Changelog ==

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
