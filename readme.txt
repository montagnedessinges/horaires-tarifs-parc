=== Gestion du parc ===
Contributors: equipe-parcs
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.19.1

Gestion centralisée et multilingue des horaires, calendriers, tarifs, événements, devis groupes, FAQ et outils du parc.

== Description ==

Cette extension gère plusieurs saisons de parc, les horaires habituels et exceptionnels, le calendrier public, les périodes et événements, les tarifs individuels et groupes, les devis, les guides pédagogiques, la FAQ globale et le Calendrier de l’Avent.

Les données de chaque saison restent séparées. La FAQ dispose de son propre stockage global, indépendant des saisons. Les mises à jour sont conçues pour conserver les réglages déjà enregistrés.

== Mise à jour manuelle ==

Téléversez le ZIP depuis Extensions > Ajouter une extension > Téléverser une extension. WordPress peut proposer de remplacer la version installée ; les réglages sont conservés dans la base de données.

Une sauvegarde du site et de la base de données reste recommandée avant toute mise à jour.

== Changelog ==

= 1.19.1 =
* Remplace le parcours Google Sheet / Apps Script de la FAQ par un import CSV simple dans Gestion du parc > FAQ.
* Le CSV est d’abord analysé sans écriture : l’extension vérifie le parc, les colonnes, les IDs et les statuts puis affiche les fiches nouvelles, modifiées, identiques, bloquées ou invalides.
* Conserve la validation humaine avant application, l’absence de suppression automatique et la création d’une révision de sécurité avant chaque import.
* Refuse un CSV contenant des IDs d’un autre parc ou des IDs dupliqués et limite les fichiers à 5 Mo / 2 000 lignes.
* Reconnaît directement les colonnes FR / EN / DE utilisées par la base FAQ actuelle, dont les intitulés allemands et anglais déjà présents dans le Google Sheet.
* Désactive les anciens endpoints d’administration Apps Script et retire le parcours Google de l’écran FAQ ; les shortcodes et le rendu public 1.19.0 restent inchangés.
* Le stockage FAQ reste indépendant de parcs_ht_settings : aucun import CSV FAQ ne peut modifier les horaires, tarifs, saisons ou devis.

= 1.19.0 =
* Ajoute un écran global FAQ indépendant des années et des saisons, avec stockage séparé, recherche, filtres et accordéons rendus côté serveur.
* Ajoute les shortcodes [parc_faq], [parc_faq_fr], [parc_faq_en] et [parc_faq_de].
* Ajoute l’aperçu avant import, la sélection manuelle, le blocage des lignes non validées/non publiques, l’absence de suppression automatique et les révisions de sécurité.
* Prévoit les champs FR / EN / DE, les synonymes de recherche, les liens officiels et trois modes de réponse pour les données dynamiques.

= 1.18.0 =
* Ajoute l’écran IA & Google, les données structurées du parc et une première base de connaissances officielle FR / EN / DE.
* Conserve les termes de recherche internes hors du HTML public et évite les doublons potentiels avec SEOPress PRO.

Pour l’historique détaillé des versions antérieures, consultez CHANGELOG.md dans le dépôt GitHub.
