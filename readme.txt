=== Gestion du parc ===
Contributors: equipe-parcs
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.17.10

Gestion centralisée et multilingue des horaires, calendriers, tarifs, événements, devis groupes et outils du parc.

== Description ==

Cette extension gère plusieurs saisons de parc, les horaires habituels et exceptionnels, le calendrier public, les périodes et événements, les tarifs individuels et groupes, les devis, les guides pédagogiques et le Calendrier de l’Avent.

Les données de chaque saison restent séparées. Les mises à jour sont conçues pour conserver les réglages déjà enregistrés.

== Mise à jour manuelle ==

Téléversez le ZIP depuis Extensions > Ajouter une extension > Téléverser une extension. WordPress peut proposer de remplacer la version installée ; les réglages sont conservés dans la base de données.

Une sauvegarde du site et de la base de données reste recommandée avant toute mise à jour.

== Changelog ==

= 1.17.10 =
* Transforme la dernière étape 1.17.x en version corrective et d’audit des écrans refaits de 1.17.2 à 1.17.9.
* Ajoute un garde d’intégrité commun aux sauvegardes d’administration afin qu’un POST tronqué par `max_input_vars`, `post_max_size` ou une requête incomplète ne puisse plus être interprété comme une suppression volontaire.
* Annule l’enregistrement avant toute écriture lorsque le marqueur de fin de formulaire n’est pas reçu ; les réglages existants restent alors inchangés.
* Protège les sauvegardes Horaires, Périodes/événements, Tarifs visiteurs, Groupes, Devis groupes, Guides pédagogiques, Pop-up, Calendrier de l’Avent, Administration générale, Contenus & traductions et import CSV.
* Conserve les suppressions volontaires : un tableau réellement vidé reste enregistrable lorsque le formulaire complet a bien été reçu.
* Corrige les contrats 1.17.8 et 1.17.9 afin qu’ils continuent à protéger leurs fonctionnalités dans les versions ultérieures au lieu d’exiger un numéro de version littéral.
* Conserve les moteurs publics, les migrations, les shortcodes historiques et les protections d’isolation annuelle existantes.

= 1.17.9 =
* Sépare la rubrique Communication en deux pages techniques dédiées : Pop-up et Calendrier de l’Avent.
* Simplifie les alertes autonomes : leurs réglages détaillés restent masqués tant que le pop-up n’est pas activé, avec contenus FR / EN / DE, bouton facultatif et prévisualisation locale.
* Laisse les pop-up d’événements et d’horaires exceptionnels dans leur module d’origine afin d’éviter les contenus dupliqués, tout en conservant le moteur public commun.
* Conserve les réglages d’apparence historiques des pop-up et replie les options avancées sans forcer de raccordement au socle d’apparence globale.
* Donne au Calendrier de l’Avent sa propre page sans contexte d’année de saison ; campagnes, contenus, partenaires, résultats, import CSV et apparence par campagne restent inchangés.
* Affiche les shortcodes spécifiques de chaque campagne Avent avec l’attribut `campagne` et conserve tous les shortcodes publics historiques.
* Charge les nouveaux assets d’administration uniquement sur les pages Communication concernées et préserve le chargement conditionnel des assets publics.

= 1.17.8 =
* Remplace l’ancien accès aux Guides pédagogiques par un écran métier annuel dédié, plus lisible et repliable.
* Regroupe documents, cycles/niveaux, langues, badges, titres, descriptions, PDF et couvertures sans créer de nouveau stockage.
* Conserve les identifiants permanents des guides, les statistiques anonymes, les mécanismes Consulter/Télécharger et les shortcodes publics existants.
* Renvoie les libellés publics des guides, cycles/niveaux et langues vers Contenus & traductions FR / EN / DE tout en gardant les codes internes stables.
* Conserve les réglages visuels historiques des guides et replie les options avancées ; aucun raccordement forcé à l’apparence globale n’est introduit.
* Charge les nouveaux assets d’administration uniquement sur l’écran Guides pédagogiques.

= 1.17.7 =
* Ajoute un écran métier annuel « Devis groupes » séparé des Tarifs groupes et des Guides pédagogiques.
* Fiabilise le contexte d’année des écrans Groupes et des écritures admin/AJAX afin qu’aucune grille tarifaire d’une autre année ne puisse être utilisée.
* Réconcilie l’activation annuelle canonique des devis avec l’ancien stockage technique sans fallback inter-années.
* Conserve le recalcul serveur et lie chaque devis uniquement aux identifiants permanents de la grille Groupes de l’année sélectionnée.
* Supprime définitivement du renderer du portail Groupes la phrase parasite « Les tarifs groupes ne sont pas disponibles pour cette année. » et son ancien nœud d’affichage.
* Purge le cache LiteSpeed lors de la mise à jour de l’extension afin d’éviter qu’un ancien HTML public conserve ce message après installation.
* Regroupe les formulaires Contact Form 7 FR / EN / DE, l’accès au devis et les champs techniques ; les options avancées restent repliées.

= 1.17.6 =
* Remplace l’ancien passage par l’onglet historique des tarifs groupes par un écran métier annuel « Groupes » directement accessible dans WordPress.
* Conserve une seule grille tarifaire Groupes canonique, réutilisée par le portail Groupes et par le rendu public partagé existant.
* Isole strictement la sauvegarde Groupes : les tarifs Individuels, Réduits, moyens de paiement visiteurs, réglages PDF et autres clés tarifaires restent inchangés.
* Regroupe sur l’écran Groupes le titre, l’introduction, les moyens de paiement, les informations pratiques, le bouton de devis et l’apparence propres au bloc public Groupes.
* Affiche l’état effectif de publication, l’état de la grille et la validité de la liaison avec le devis, sans créer un second interrupteur annuel.
* Conserve l’activation annuelle dans la Vue d’ensemble et l’Administration générale, avec les mêmes clés et la même logique d’apparition/disparition automatiques.
* Maintient Devis groupes et Guides pédagogiques comme écrans techniques séparés au sein de la famille Groupes afin de ne charger que les outils nécessaires.
* Ne modifie ni le moteur de devis, ni le calendrier canonique Groupes, ni le comportement 1.16.8 des années Groupes publiées avant les tarifs visiteurs.

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