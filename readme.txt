=== Gestion du parc ===
Contributors: equipe-parcs
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.17.5

Gestion centralisée et multilingue des horaires, calendriers, tarifs, événements, devis groupes et outils du parc.

== Description ==

Cette extension gère plusieurs saisons de parc, les horaires habituels et exceptionnels, le calendrier public, les périodes et événements, les tarifs individuels et groupes, les devis, les guides pédagogiques et le Calendrier de l’Avent.

Les données de chaque saison restent séparées. Les mises à jour sont conçues pour conserver les réglages déjà enregistrés.

== Mise à jour manuelle ==

Téléversez le ZIP depuis Extensions > Ajouter une extension > Téléverser une extension. WordPress peut proposer de remplacer la version installée ; les réglages sont conservés dans la base de données.

Une sauvegarde du site et de la base de données reste recommandée avant toute mise à jour.

== Changelog ==

= 1.17.5 =
* Remplace l’ancien pont « Tarifs visiteurs » par une page métier dédiée aux catégories Individuels et Tarifs réduits.
* Conserve les colonnes, lignes, identifiants permanents, offres spéciales, périodes de validité et canaux de vente déjà utilisés par le moteur public.
* Conserve les moyens de paiement visiteurs avec les canaux indépendants Sur place / En ligne ; l’achat en ligne reste réservé à Individuels.
* Isole strictement l’enregistrement des tarifs visiteurs : les prix, colonnes et réglages Groupes de la même année sont restaurés avant sauvegarde et ne sont jamais écrasés par cette page.
* Réutilise les enrichissements existants des offres, des colonnes visibles et des moyens de paiement sans créer de second moteur tarifaire.
* Ajoute le contexte d’année commun et un accès vers Contenus & traductions pour les textes éditoriaux FR / EN / DE.
* Replie les réglages avancés d’apparence et d’impression tout en conservant les réglages historiques pour compatibilité.

= 1.17.4 =
* Remplace l’ancien écran-pont Périodes & événements par un espace métier annuel dédié réunissant périodes repères, événements, exceptions et accès temporairement limité.
* Sépare visuellement les périodes de contexte et les événements sans fusionner leurs règles métier ni leurs données.
* Chaque événement peut conserver sa propre couleur et son propre pictogramme parmi les marqueurs déjà pris en charge par le calendrier public.
* Les horaires exceptionnels conservent la priorité sur les horaires habituels, avec un ou deux créneaux et retour automatique au planning normal à la fin de l’exception.
* Les pop-up d’exception réutilisent le contexte, le titre public et le message public comme source unique ; les dates et horaires peuvent être affichés indépendamment.
* Les réglages d’un pop-up restent masqués tant que « Activer le pop-up » n’est pas coché.
* Le module Accès temporairement limité et ses textes FR / EN / DE sont conservés, ainsi que le moteur CSV commun.
* Les anciens liens Horaires → Périodes, Exceptions et Accès limité redirigent vers le nouvel espace sans modifier les shortcodes publics ni le moteur calendrier canonique.

= 1.17.3 =
* Remplace l’ancien onglet Horaires & calendrier par un écran métier dédié et plus léger, sans réécrire le moteur public.
* Conserve les périodes d’ouverture existantes avec un ou deux créneaux, le second restant facultatif.
* Replie les options avancées de chaque période pour alléger l’interface tout en conservant jours concernés, dernière entrée spécifique et couleur fonctionnelle.
* Ajoute le sélecteur d’année commun directement sur l’écran Horaires & calendrier et préserve les anciens liens vers l’onglet historique.
* Conserve les couleurs fonctionnelles des cases du calendrier et les réglages visuels existants sans les remplacer par l’apparence globale.
* Renvoie les textes publics FR / EN / DE vers Contenus & traductions au lieu de les dupliquer dans l’écran métier.
* Réutilise le moteur CSV existant et conserve les shortcodes, données historiques, événements, exceptions et accès limité inchangés.

= 1.17.2 =
* Fait de la Vue d’ensemble le point d’entrée principal de Gestion du parc et réorganise les sous-menus WordPress pour accéder directement aux grandes rubriques.
* Regroupe Tarifs groupes, Devis groupes et Guides pédagogiques sous « Groupes », et Pop-up + Calendrier de l’Avent sous « Communication », sans mélanger leurs moteurs ni leurs données.
* Transforme la Vue d’ensemble en tableau de bord : année administrée, état des cinq activations annuelles, dates automatiques, accès rapide aux modules et état des mises à jour.
* Adapte l’updater au dépôt GitHub public : détection et téléchargement sans clé, mise à jour automatique sans token, vérification forcée manuelle et contrôle SHA-256 conservé.

= 1.17.1 =
* Ajoute une Administration générale et le référentiel d’apparence globale non destructif.
* Regroupe les informations globales du parc, les saisons et les cinq activations annuelles existantes.
* Conserve les données, moteurs horaires, tarifs, devis, guides et compatibilité PHP 7.4 / 8.1 / 8.2 / 8.3.

= 1.17.0 =
* Ajoute Contenus & traductions pour centraliser les principaux textes publics FR / EN / DE.
* Conserve les moteurs horaires, tarifs, devis, guides, exports, migrations et données annuelles existants.

Pour l’historique détaillé des versions antérieures, consultez CHANGELOG.md dans le dépôt GitHub.