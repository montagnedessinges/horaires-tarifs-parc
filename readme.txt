=== Gestion du parc ===
Contributors: equipe-parcs
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.17.2

Gestion centralisée et multilingue des horaires, calendriers, tarifs, événements, devis groupes et outils du parc.

== Description ==

Cette extension gère plusieurs saisons de parc, les horaires habituels et exceptionnels, le calendrier public, les périodes et événements, les tarifs individuels et groupes, les devis, les guides pédagogiques et le Calendrier de l’Avent.

Les données de chaque saison restent séparées. Les mises à jour sont conçues pour conserver les réglages déjà enregistrés.

== Mise à jour manuelle ==

Téléversez le ZIP depuis Extensions > Ajouter une extension > Téléverser une extension. WordPress peut proposer de remplacer la version installée ; les réglages sont conservés dans la base de données.

Une sauvegarde du site et de la base de données reste recommandée avant toute mise à jour.

== Changelog ==

= 1.17.2 =
* Fait de la Vue d’ensemble le point d’entrée principal de Gestion du parc et réorganise les sous-menus WordPress pour accéder directement aux grandes rubriques.
* Regroupe Tarifs groupes, Devis groupes et Guides pédagogiques sous « Groupes », et Pop-up + Calendrier de l’Avent sous « Communication », sans mélanger leurs moteurs ni leurs données.
* Transforme la Vue d’ensemble en tableau de bord : année administrée, état des cinq activations annuelles, dates automatiques, accès rapide aux modules et état des mises à jour.
* Permet de modifier les cinq activations annuelles depuis la Vue d’ensemble en réutilisant l’action d’enregistrement canonique de l’Administration générale.
* Adapte l’updater au dépôt GitHub public : détection et téléchargement sans clé, mise à jour automatique sans token, vérification forcée manuelle et contrôle SHA-256 conservé.
* Conserve la compatibilité avec une clé GitHub facultative si le dépôt redevient privé.
* Rend plus lisibles les libellés de l’Apparence globale sans raccorder prématurément tous les modules au nouveau socle visuel.
* Le Calendrier de l’Avent reste indépendant : chaque campagne conserve ses propres données et son propre shortcode.

= 1.17.1 =
* Ajoute une nouvelle Administration générale comme entrée principale de l’extension, sans supprimer l’ancienne vue détaillée utilisée comme filet de sécurité.
* Regroupe les informations globales du parc, la gestion des saisons et la publication annuelle avec les cinq activations existantes et leurs dates automatiques.
* Introduit un référentiel d’apparence globale non destructif pour les couleurs, textes, bordures, boutons, cartes, onglets, badges et espacements communs.
* Prépare la cascade future apparence globale → personnalisation du module → personnalisation d’un élément, sans forcer de changement visuel aux modules métier dans cette version.
* Supprime le message public global « tarifs groupes indisponibles » du portail Groupes lorsqu’il n’est pas pertinent et ajoute un test de non-régression dédié.
* Conserve les données, clés historiques, moteurs horaires, tarifs, devis, guides, exports et compatibilité PHP 7.4 / 8.1 / 8.2 / 8.3.

= 1.17.0 =
* Ajoute un espace « Contenus & traductions » pour modifier les principaux textes publics FR / EN / DE sans modifier le code.
* Centralise notamment les libellés des tarifs, boutons, calendrier, statuts d’ouverture, messages Groupes et textes génériques des guides pédagogiques.
* Permet de personnaliser le lien de renvoi vers l’espace Groupes par langue, tout en conservant le lien automatique existant comme repli.
* Corrige l’affichage parasite du message « tarifs groupes indisponibles » lorsqu’un panneau tarifaire existe réellement pour l’année sélectionnée.
* Conserve les moteurs horaires, tarifs, devis, guides, exports, migrations et données annuelles existants.
* Renforce les tests de non-régression de la 1.16.8 afin qu’ils protègent aussi les versions ultérieures.

= 1.16.8 =
* Réutilise le même tableau Tarifs groupes dans le portail Groupes et dans le tableau public lorsqu’une année visiteurs est publiée.
* Les moyens de paiement, informations, styles et bouton de devis restent ceux de la configuration Groupes ; aucun moyen visiteurs ni bouton « Acheter vos billets » n’est injecté dans Groupes.
* Si une année est déjà disponible pour les groupes mais pas encore pour les visiteurs, l’onglet Groupes public affiche seulement un renvoi vers l’espace Groupes et précise que les tarifs Individuels et Réduits ne sont pas encore disponibles.
* Le lien de renvoi détecte la page du portail Groupes sans slug codé en dur, avec le lien Groupes historique en secours.
* Préserve le calendrier canonique, la synchronisation annuelle du portail Groupes, les dates automatiques et le moteur de devis.

= 1.16.7 =
* Ajoute pour chaque moyen de paiement visiteurs deux canaux indépendants et cumulables : Sur place et En ligne.
* Place les moyens de paiement juste sous l’onglet tarifaire actif et rappelle la catégorie affichée.
* Les tarifs réduits utilisent uniquement les moyens de paiement Sur place et ne proposent aucun achat en ligne.
* Le bouton « Acheter vos billets » est affiché uniquement dans l’onglet Individuels.
* Les moyens historiques restent compatibles : carte bancaire Sur place + En ligne, autres moyens Sur place tant qu’ils ne sont pas modifiés dans l’administration.
* Cette étape ne modifie pas le moteur du portail Groupes, le calendrier Groupes ni le moteur des devis.

= 1.16.6 =
* Affiche la gratuité des enfants de moins de 5 ans uniquement sur place, dans un encadré unique au même format que les tarifs réduits et sans lien vers la billetterie.
* Préserve les autres tarifs et les données enregistrées de chaque saison.

= 1.16.5 =
* Corrige l’affichage des tarifs groupes des années futures lorsque l’année est activée dans le shortcode Groupes.
* Une grille groupes est considérée exploitable dès qu’une ligne active contient un tarif, sans dépendre de l’ancien état des colonnes.
* Le rendu public des groupes utilise une seule colonne canonique « Tarif » / « Price » / « Preis » sans modifier les données enregistrées.

= 1.16.4 =
* Remplace l’ancienne logique de fenêtre par deux dates maîtresses d’activation et de désactivation automatiques.
* Applique cette bascule au calendrier public, aux tarifs visiteurs, aux horaires groupes, aux devis groupes et aux tarifs groupes.
* Les interrupteurs restent manuels avant l’activation automatique et la désactivation reste prioritaire.

= 1.16.3 =
* Rétablit le sélecteur d’années dans le shortcode des tarifs groupes.
* Force l’affichage des tarifs réduits en « Sur place » uniquement.
* Conserve l’architecture de shortcodes composés sans recréer le moteur de calendrier.

= 1.16.2 =
* Simplifie les shortcodes composés : Horaires & Tarifs assemble les shortcodes autonomes de l’état du jour, du calendrier et des tarifs.
* Le shortcode Groupes assemble les tarifs groupes et le calendrier existant, sans second moteur d’horaires.

= 1.16.1 =
* Restaure le shortcode complet Horaires & Tarifs avec l’état du jour, le calendrier existant et le nouveau tableau de tarifs.
* Corrige les cellules En ligne vides et conserve le vrai calendrier public dans l’espace Groupes.

= 1.16.0 =
* Refonte du rendu public des tarifs sans modifier le moteur des horaires ni le calendrier existant.
* Nouveau composant compact et responsive pour les tarifs individuels, réduits et groupes.
* Les réglages de couleurs existants restent personnalisables.
