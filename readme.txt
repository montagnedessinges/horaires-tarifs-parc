=== Gestion du parc ===
Contributors: equipe-parcs
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.20.2

Gestion centralisée et multilingue des horaires, calendriers, tarifs, événements, devis groupes, FAQ et outils du parc.

== Description ==

Cette extension gère plusieurs saisons de parc, les horaires habituels et exceptionnels, le calendrier public, les périodes et événements, les tarifs individuels et groupes, les devis, les guides pédagogiques, la FAQ globale et le Calendrier de l’Avent.

Les données saisonnières restent séparées. La bibliothèque de guides pédagogiques et la FAQ sont globales ; l’affichage des guides reste configurable par année. Les mises à jour sont conçues pour conserver les réglages et statistiques déjà enregistrés.

== Mise à jour manuelle ==

Téléversez le ZIP depuis Extensions > Ajouter une extension > Téléverser une extension. WordPress peut proposer de remplacer la version installée ; les réglages sont conservés dans la base de données.

Une sauvegarde du site et de la base de données reste recommandée avant toute mise à jour.

== Changelog ==

= 1.20.2 =
* Corrige la lecture des horaires groupes multi-années dans le HTML initial : une année publiée pour les groupes peut désormais être comprise sans attendre l’exécution JavaScript, même si cette année n’est pas encore publiée dans le calendrier visiteurs.
* Supprime la seconde implémentation concurrente du portail groupes : le shortcode groupes utilise désormais un seul renderer canonique, sans double logique 2026 / 2027.
* Rend chaque année publique du portail groupes accessible par une vraie URL de repli serveur tout en conservant le changement instantané par JavaScript pour le visiteur.
* Rend les tarifs groupes côté serveur pour l’année publique explicitement sélectionnée et conserve `Parcs_HT_Public_Visibility` comme source de décision des années réellement publiques.
* N’émet plus le faux texte « horaires non disponibles » dans le contenu textuel lorsque les horaires de l’année active existent réellement.
* Réduit le payload JavaScript propre au calendrier groupes à une liste blanche de champs publics et n’y expose jamais les libellés internes.
* Conserve l’apparence du calendrier, des tarifs et du portail groupes ; aucun nouveau stockage ni aucune double saisie n’est ajouté.
* Ajoute des tests 1.20.2 sur le rendu serveur, l’isolation calendrier visiteurs / horaires groupes, les tarifs par année et l’absence de fuite de libellés internes.

= 1.20.1 =
* Ajoute un rendu sémantique du calendrier directement dans le HTML initial afin que Google, les autres moteurs et les assistants IA puissent comprendre les données publiques sans dépendre de l’exécution JavaScript.
* Réutilise exclusivement les saisons, horaires, exceptions, événements, périodes et jours fériés déjà enregistrés dans Gestion du parc : aucune seconde saisie ni nouveau stockage n’est créé.
* Expose dans un accordéon public discret les horaires habituels, horaires exceptionnels, fermetures, événements et périodes réellement publiés, avec dates balisées en HTML sémantique.
* Conserve le calendrier visuel interactif existant et son moteur JavaScript inchangés ; la nouvelle couche sert de représentation serveur complémentaire et progressive.
* Ne transforme pas les périodes ni le calendrier multi-événements en faux schémas Event ; les données structurées horaires existantes restent gérées par le socle IA & Google et sa protection contre les doublons SEOPress PRO.
* Respecte FR / EN / DE sans reprendre automatiquement un titre français lorsqu’une traduction publique manque.
* Ajoute des contrats de régression statiques et d’exécution pour vérifier le HTML initial, l’absence de libellés internes et l’absence de contenu réservé aux robots.

= 1.20.0 =
* Refonte complète des pop-up : ils deviennent autonomes et ne dépendent plus des événements, périodes ou exceptions.
* Remplace le contenu texte/bouton du pop-up par un visuel FR / EN / DE sélectionné dans la médiathèque WordPress, avec lien facultatif et texte alternatif par langue.
* Ajoute les tailles Petit 480 px, Moyen 620 px, Grand 800 px et Personnalisé 320–1200 px ; les visuels restent entiers et responsives sans recadrage.
* Ajoute priorité, dates/heures d’affichage, brouillon/publié, activation, affichage unique ou réapparition après X heures, duplication et aperçu par langue.
* Utilise un moteur public unique via REST avec récupération non mise en cache, afin qu’un pop-up nouvellement activé ne dépende plus du HTML de page mis en cache.
* Étend la langue canonique de l’extension à qTranslate-XT, Polylang, WPML puis au locale WordPress ; le navigateur ne choisit jamais la langue du pop-up.
* Les anciens pop-up sont conservés comme brouillons désactivés à contrôler avant publication ; les anciens réglages Pop-up sont retirés visuellement des écrans Événements et Exceptions.

= 1.19.9 =
* Lit enfin les colonnes multilingues « Lien de redirection FR », « Weiterleitungslink DE » et « Redirect link EN » du CSV FAQ.
* Ajoute les colonnes facultatives « Texte bouton FR », « Button-Text DE » et « Button text EN » pour afficher volontairement un bouton cliquable sous une réponse.
* Le bouton utilise l’URL correspondant à la langue affichée ; une réponse directe sans libellé de bouton ne reçoit pas de bouton supplémentaire.
* Les liens d’action déjà gérés par la FAQ (horaires, tarifs et réponses dynamiques) adoptent le même rendu de bouton.
* Le style des boutons reste transparent et hérite des couleurs du thème.
* Aucun changement sur les saisons, horaires, tarifs, devis, guides ou formulaires de contact.

= 1.19.8 =
* Corrige le périmètre de la catégorie secondaire « Règles du parc » : elle est alimentée uniquement par le CSV normal de l’onglet du parc.
* Supprime l’import séparé « Connaissances singes - IA » ajouté en 1.19.7 ; cet onglet reste une base interne et n’est plus proposé dans WordPress.
* Conserve l’affichage discret « Règles du parc », la présence dans le HTML initial et la recherche interne.
* Les règles sont créées dans l’onglet Montagne des Singes ou Forêt des Singes avec la catégorie « Règles du parc », puis importées par le workflow CSV habituel.
* Aucun changement sur les saisons, horaires, tarifs, devis, guides ou formulaires de contact.

= 1.19.7 =
* Ajoute un affichage secondaire discret pour la catégorie « Règles du parc » : elle n’apparaît pas dans les filtres principaux de la FAQ et reste fermée par défaut.
* Les questions « Règles du parc » restent présentes dans le HTML initial, accessibles volontairement par le visiteur et trouvables via la recherche de la FAQ.
* Une recherche correspondant à une question secondaire ouvre automatiquement le bloc « Règles du parc » afin d’afficher le résultat.
* Les réponses secondaires peuvent afficher un lien « Source » lorsque la source officielle est une URL publique sûre.
* Ajoute dans Gestion du parc > FAQ un import CSV dédié à l’onglet « Connaissances singes - IA » ; les IDs SIN-COM-* validés et destinés à l’IA sont fusionnés dans le stockage FAQ existant sous « Règles du parc ».
* Ne crée aucune seconde base publique : les imports FAQ du parc et connaissances IA alimentent la même option globale, avec révision de sécurité et sans suppression automatique.
* Aucun changement sur les saisons, horaires, tarifs, devis, guides ou formulaires de contact.

= 1.19.6 =
* Ajoute sous les tarifs le même rendu coloré que les messages importants du devis en ligne.
* Les tarifs Individuels et Réduits peuvent recevoir plusieurs blocs d’information, chacun avec activation, ordre, contenu FR / EN / DE et couleur propre.
* Les blocs d’information Groupes existants gagnent eux aussi une couleur individuelle sans perte de contenu.
* La couleur de chaque bloc pilote à la fois le repère latéral et le fond légèrement teinté.
* Le devis en ligne reste inchangé ; aucun contenu n’est dupliqué entre Devis et Tarifs.

= 1.19.5 =
* Renforce le principe « FAQ d’abord, contact en dernier recours » dans le shortcode FAQ + Contact.
* Remplace le bouton générique « Nous écrire » par « Je n’ai pas trouvé ma réponse » avec des équivalents FR / EN / DE.
* Le formulaire Contact Form 7 reste masqué par défaut et ne s’affiche qu’après une action volontaire du visiteur.
* La FAQ reste la source principale : aucune réponse FAQ n’est dupliquée dans le formulaire.
* En cas de recherche sans résultat, le bloc de contact reste disponible mais le formulaire ne s’ouvre jamais automatiquement.
* Aucun changement sur les saisons, horaires, tarifs, devis, guides ou données FAQ.

= 1.19.4 =
* Transforme les guides pédagogiques en bibliothèque permanente commune à toutes les années, avec affichage configurable par année.
* Conserve les identifiants permanents et agrège les anciens identifiants saisonniers afin de préserver l’historique statistique.
* Maintient les statistiques par année ainsi que les vues 7 jours, 30 jours, saison et toutes saisons.
* Supprime l’enregistrement de l’ancien panneau Guides embarqué et les contournements qui pouvaient réafficher l’ancienne interface.
* Remplace les ponts Aperçu et Shortcodes par de vrais écrans dédiés et redirige les anciennes URL vers les écrans métier actuels.
* Aligne la couche de sauvegarde sécurisée sur le stockage v4 de la bibliothèque.

= 1.19.3 =
* Unifie les questions visiteurs et les règles de visite dans la FAQ importée par CSV : l’onglet du parc devient la source publique unique.
* Retire l’ancien accordéon « Règles de visite » ajouté automatiquement sous Horaires & Tarifs afin d’éviter une seconde source de vérité.
* Remplace dans IA & Google l’ancien bloc éditable de règles de visite par une information renvoyant vers Gestion du parc > FAQ.
* Ajoute un bouton « Nous écrire » après la FAQ ; le formulaire Contact Form 7 reste masqué jusqu’au clic et s’ouvre sur la même page.
* Lorsqu’une recherche FAQ ne trouve aucune réponse, le bloc de contact reste immédiatement disponible sous le message d’absence de résultat.
* Conserve Contact Form 7 comme seul moteur d’envoi d’e-mail et ne modifie ni les saisons, ni les horaires, ni les tarifs, ni les devis.

= 1.19.2 =
* Simplifie le rendu public de la FAQ : plus de faux en-tête de page ni de bloc de titre spécifique ; le titre et l’introduction utilisent du texte simple qui hérite du thème.
* Ajoute les shortcodes [parc_faq_contact], [parc_faq_contact_fr], [parc_faq_contact_en] et [parc_faq_contact_de].
* Ajoute dans Gestion du parc > FAQ les champs pour renseigner les shortcodes Contact Form 7 FR / EN / DE.
* Le formulaire reste envoyé par Contact Form 7 selon ses destinataires habituels ; l’extension ne crée pas de second système d’e-mail.
* Les réglages du formulaire FAQ sont stockés séparément dans parcs_ht_faq_contact et ne touchent jamais aux saisons, horaires, tarifs ou devis.

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
