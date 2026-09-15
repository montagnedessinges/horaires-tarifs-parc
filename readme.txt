=== Gestion du parc ===
Contributors: equipe-parcs
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.15.9

Gestion multilingue des horaires, saisons, calendrier, exceptions, alertes et tarifs des parcs.

== Version 1.5.2 ==
* Correctif visuel du calendrier.
* Les jours fermés retrouvent un numéro clairement barré.
* Événement ponctuel : pastille jaune fixe avec pictogramme événement fixe en haut à gauche.
* Événement long : petit point jaune discret en haut à gauche.
* Suppression du choix de pictogramme et de couleur pour les événements afin d’unifier la lecture.
* Nouveau choix de fond des jours ouverts : transparent ou fond uni personnalisable.

== Version 1.5.0 ==
* Refonte visuelle du calendrier : cases ouvertes transparentes/blanches, jours fermés simplement grisés.
* Les horaires et fermetures exceptionnels restent signalés en haut à droite de la case.
* Les événements sont signalés à gauche ; les événements longs peuvent utiliser un simple point discret.
* Les périodes repères utilisent désormais un fin bandeau en bas de la case, sans colorer toute la journée.
* La légende des périodes est construite à partir des périodes réellement visibles dans le mois et reprend leur nom et leur couleur.
* L’information d’accès temporairement limité n’a plus aucune pastille dans le calendrier et apparaît simplement sous les horaires de la journée.
* Le texte automatique de l’accès est raccourci : dernière entrée, interruption, reprise des visites.
* Les événements dans le détail de la journée affichent titre, texte court et bouton aligné à droite lorsqu’il est activé.
* Ajout du choix « événement ponctuel » ou « événement long » dans l’administration.
* Conservation des boutons et liens FR / EN / DE de la version 1.4.1.

== Version 1.4.1 ==
* Correctif multilingue des boutons et pop-up.
* La langue qTranslate du site est désormais prioritaire pour les pop-up automatiques.
* Les boutons peuvent avoir un texte et un lien distincts en FR / EN / DE.
* Les événements et périodes affichent réellement leur bouton dans le détail du calendrier lorsque l’option est activée.
* Les champs de bouton restent masqués dans l’administration tant que « Afficher un bouton » n’est pas activé.
* Les anciens liens uniques sont migrés dans les trois langues afin de ne perdre aucun réglage.
* Un lien manquant dans une langue ne renvoie plus automatiquement vers le lien français.

== Version 1.4.0 ==
* Version corrective et optimisation avant refonte de l’administration.
* Évite de reconstruire les réglages complets sur chaque page lorsque aucun pop-up automatique n’est actif.
* Cache les réglages fusionnés pendant une même requête PHP afin d’éviter les traitements répétés.
* Conserve les réglages et la structure WordPress des versions 1.3.x.

== Version 1.1.0 ==

* Migration automatique des réglages 1.0.0 à 1.0.3 vers une structure multi-saisons.
* Sauvegarde de sécurité interne avant la première migration 1.1.0.
* Ajout manuel de saisons dans l'administration ; aucune nouvelle année n'est créée automatiquement.
* Publication indépendante de chaque saison. Les saisons en brouillon restent invisibles sur le site.
* Sélecteur d'année public uniquement lorsqu'au moins deux saisons sont publiées.
* Une saison publiée sans dates complètes affiche une information d'indisponibilité et n'est pas considérée comme fermée.
* Dates, horaires, jours fériés, vacances, règles du domaine et exceptions communs aux langues.
* Textes FR / EN / DE conservés dans les onglets de traduction.
* Détection de la langue qTranslate-XT lorsqu'il est disponible, avec mécanismes de secours.
* Pop-up d'alerte général léger et indépendant des shortcodes.
* Moyens de paiement sélectionnables, liens billets/devis modifiables par langue.
* Bouton devis réservé à l'onglet Groupes.
* Fonds de contenu transparents, boutons à fond uni, cases du calendrier conservant leur couleur horaire.
* Protection contre les formulaires PHP tronqués et conservation des anciennes valeurs.
* Validation, nonces et contrôles de capacité sur les opérations d'administration.

== Mise à jour manuelle ==

Téléversez le nouveau ZIP depuis Extensions > Ajouter une extension > Téléverser une extension. WordPress peut alors proposer de remplacer la version déjà installée. Le dossier et l'identifiant de l'extension restent identiques ; les réglages sont stockés dans la base WordPress et migrés automatiquement.

Il reste recommandé d'effectuer une sauvegarde du site et de la base de données avant toute mise à jour d'extension.

== Changelog ==

= 1.8.1 =
* Affichage intégral des doubles créneaux sur la première page, le bandeau, le calendrier et le détail d’une journée.
* Le résumé « Horaires du mois » reprend chaque combinaison horaire distincte, second créneau compris.
* Entre deux créneaux, le site annonce désormais l’heure de réouverture au lieu d’indiquer que la journée est terminée.
* La dernière entrée est calculée à partir de la fermeture du dernier créneau.
* L’aperçu de l’administration et les libellés accessibles affichent eux aussi les deux plages.

= 1.8.0 =
* Diagnostic annuel, Santé du site, historique restaurable et notifications e-mail anti-spam.
* Priorités calendrier/PDF fiabilisées : fermeture, horaire exceptionnel, événement public et accès limité seulement les jours ouverts.
* Planning annuel complet avec légende automatique sur une seconde page lorsque nécessaire.
* PDF prégénérés et mis en cache après enregistrement, téléchargements publics limités et surveillés.
* Vérification SHA-256 obligatoire des mises à jour GitHub et protection renforcée du token privé.
* Sauvegarde par onglet, fuseau configurable, accessibilité des fenêtres et tests automatisés PHP/JavaScript.

= 1.7.10 =
* Toute journée sans horaire applicable est désormais affichée fermée et barrée dans le planning annuel.
* Une fermeture exceptionnelle n'est plus présentée comme un horaire exceptionnel.
* La légende explique les fermetures, horaires exceptionnels, événements, périodes repères et accès limité réellement présents.

= 1.7.9 =
* Les plages horaires complètes ne sont plus raccourcies dans la légende du planning annuel.
* Ajout d'un avertissement automatique avec le nom du parc, la date de génération et l'adresse du site à vérifier avant la visite.

= 1.7.8 =
* Planning annuel synthétique sur une page A3 paysage.
* Priorité visuelle des horaires exceptionnels et légende globale.
* Périodes, événements, jours fériés et accès limité alignés sur le calendrier public.
* Cache PDF révisé automatiquement après modification des données.

= 1.7.7 =
* Finalisation du système de mise à jour GitHub privé.
* Vérification manuelle sécurisée par capacité et nonce.
* Affichage explicite de l’état local/GitHub et de la dernière vérification.
* Validation renforcée du package de release.

= 1.7.6 =
* GitHub devient la source canonique des versions de l’extension.
* Ajout d’un bouton « Vérifier les mises à jour maintenant » dans l’administration.
* Affichage de la version installée, de la dernière version GitHub et de la dernière vérification.
* Ajout d’une option pour autoriser WordPress à installer automatiquement les nouvelles releases GitHub.
* Conservation des optimisations de performance de la 1.7.5 et du planning visuel de la 1.7.4.


= 1.7.5 =
* Audit massif de performance et simplification du bootstrap de l’extension.
* Les modules lourds d’administration, d’alertes et de mise à jour GitHub ne sont plus chargés sur les requêtes publiques qui n’en ont pas besoin.
* Suppression de la vérification de migration systématique sur chaque page publique ; elle reste exécutée à l’activation, dans l’administration et à la première lecture réelle des réglages.
* Ajout d’un bootstrap léger pour différer le chargement du moteur complet des shortcodes jusqu’à son utilisation réelle.
* Ajout d’un indicateur léger et autoloadé pour éviter de charger la grosse option de réglages sur chaque page lorsqu’aucun pop-up automatique n’est configuré.
* Mise en cache en mémoire, pendant une requête, des réglages fusionnés par saison.
* Réduction du payload JavaScript public : seuls les réglages généraux nécessaires au navigateur sont transmis.
* Mise en cache JavaScript des calculs d’horaires par date et des recherches de prochaine ouverture.
* Les erreurs GitHub sont désormais mises en cache temporairement et le délai réseau de contrôle est réduit afin qu’une panne GitHub ne ralentisse pas l’administration WordPress.
* Aucune requête SQL personnalisée, aucune tâche cron propre au plugin et aucune migration destructive ajoutée.
* Aucun horaire, tarif, événement, saison ou réglage existant n’est remplacé.

= 1.7.4 =
* Nouveau planning PDF visuel mois par mois, conçu pour l’impression et l’affichage interne.
* Le planning exporté résout chaque journée avec la même priorité que le calendrier public : horaire exceptionnel prioritaire, puis horaire normal, sinon fermé.
* Les doubles créneaux, fermetures et couleurs d’horaires sont repris dans les cases du calendrier PDF.
* Les événements et périodes visibles sur le calendrier sont également repris dans le planning téléchargé.
* Ajout d’un onglet « Mises à jour » permettant de configurer la clé GitHub privée directement dans WordPress, sans accès à wp-config.php.
* La clé GitHub n’est jamais intégrée au code ni réaffichée dans l’administration après enregistrement.
* Mise à jour non destructive : aucun horaire, tarif, événement ou réglage de parc existant n’est remplacé.


= 1.7.3 =
* Ajout du système de mises à jour natives WordPress depuis le dépôt GitHub privé.
* Aucun token GitHub n’est stocké dans le plugin ou dans les options de l’extension.
* Téléchargement sécurisé des assets de release sans transmettre le token au serveur de fichiers redirigé.
* Ajout du workflow de construction et publication automatique des releases GitHub.
* Aucune modification du schéma de données : horaires, tarifs, événements et réglages existants sont conservés.


= 1.7.2 =
* Version commune La Montagne des Singes / La Forêt des Singes : même base fonctionnelle pour les deux parcs.
* Mise à niveau de la base Montagne 1.6.8 avec les fonctions 1.7.1 sans remplacer les horaires, tarifs, événements ou réglages déjà enregistrés.
* Ajout d'un bouton discret « Télécharger le planning des horaires » sous le calendrier, avec génération PDF depuis les données réellement enregistrées.
* Le PDF prend en charge les périodes, jours concernés, doubles créneaux, fermetures et horaires prioritaires.
* Une exception dont la mention publique est décochée continue d'être appliquée au planning sans être présentée comme « exceptionnelle ».
* Harmonisation de l'affichage du statut ouvert et des noms « La Montagne des Singes » / « La Forêt des Singes ».
* Migration 1.7.2 non destructive : aucune donnée horaire ou tarifaire existante n'est réinitialisée.

= 1.7.1 =
* Horaires exceptionnels : ajout d'un second créneau facultatif, comme pour les horaires habituels.
* Les horaires exceptionnels restent prioritaires sur les horaires habituels et le calendrier reprend automatiquement ensuite.
* Pop-up des horaires exceptionnels simplifié : le contexte, le titre public et le message public sont réutilisés directement, sans double saisie.
* Options indépendantes pour afficher ou masquer les dates et les horaires dans le pop-up.
* Le contexte et le message apparaissent avant les informations pratiques dans le pop-up.
* Préconfiguration 2026 de La Forêt des Singes simplifiée grâce aux « jours concernés » : beaucoup moins de lignes à administrer.
* Migration prudente : la simplification automatique ne s'applique qu'à la préconfiguration Forêt des Singes 1.7.0 non personnalisée.
* Correction de compatibilité des doubles créneaux dans le moteur d'horaires exceptionnels.

= 1.7.0 =
* Ajout d’un second créneau d’ouverture facultatif par période.
* Préconfiguration Forêt des Singes : doubles créneaux, fortes chaleurs jusqu’au 31 août 2026, concours photo juillet-septembre, vacances Zone C confirmées et jours fériés masqués par défaut.
* Statut Ouvert renforcé en gras.

= 1.6.9 =
* Édition préconfigurée pour La Forêt des Singes de Rocamadour lors d'une nouvelle installation sur son domaine.
* Calendrier 2026 prérempli à partir du calendrier officiel publié par le parc.
* Tarifs 2026 individuels, réduits et groupes préremplis à partir de la page officielle.
* Billetterie, coordonnées et conditions groupes adaptées à La Forêt des Singes.
* Le module Devis groupe reste disponible mais est désactivé par défaut pour la Forêt des Singes.
* Les réglages restent entièrement modifiables depuis l'administration.

= 1.6.8 =
* Les messages importants du module Devis groupe peuvent être placés au-dessus ou sous le formulaire.
* Chaque message reste modifiable, activable/désactivable, réordonnable et possède sa propre couleur.
* Le message « Devis et validation » est placé sous le formulaire par défaut pour rappeler que l’envoi ne confirme pas encore la réservation.
* Les accordéons et liens rapides « Préparer votre visite » sont conservés.

= 1.6.7 =
* Nouveau bloc « Préparer votre visite » dans le module Devis groupe.
* Trois liens rapides préremplis : Horaires & Tarifs, Guide pédagogique et Infos pratiques.
* Liens rapides activables, réordonnables et modifiables en FR/EN/DE avec pictogramme.
* Suppression lors de la migration de l'ancien bloc d'exemple redondant « Devis automatique ».
* Ancienne introduction d'exemple supprimée uniquement si elle n'avait pas été personnalisée.
* Accordéons existants conservés.
* Blocs d'information conservés comme éléments complémentaires facultatifs.

= 1.6.6 =
* Refonte visuelle du module Devis groupe pour éviter les conflits avec le header et les styles du thème.
* Suppression des cadres, fonds et ombres imposés autour du module ; fond transparent et couleurs héritées du thème.
* Titres du module rendus indépendants des styles H2/H3 du thème.
* Nouveau système de messages importants avant le formulaire, réordonnables et avec couleur personnalisable.
* Quatre messages d’exemple FR/EN/DE préremplis : tarif groupe, moins de 20 personnes, réservation obligatoire, devis et validation.
* Présentation plus compacte et lisible sur ordinateur et mobile.

= 1.6.5 =
* Correctif Contact Form 7 pour les identifiants modernes de type hash (ex. 6c681fb).
* Suppression de la pré-validation WPCF7_ContactForm::get_instance() qui pouvait rejeter à tort un formulaire valide.
* Contact Form 7 résout désormais lui-même son identifiant lors du rendu.
* Callback des shortcodes rendu pleinement compatible avec les trois arguments de l’API WordPress.
* Diagnostic Devis groupe basé sur le rendu réel du shortcode CF7.

= 1.6.4 =
* Correctif majeur Devis groupe : [parc_devis_groupe] ne dépend plus d’un interrupteur d’activation séparé.
* Le simple fait de placer le shortcode dans une page déclenche désormais le rendu.
* Diagnostic CF7 renforcé : plugin actif, shortcode valide et formulaire ciblé trouvé.
* Vérification du formulaire via l’API Contact Form 7 lorsqu’elle est disponible.
* Rendu toujours effectué avec apply_shortcodes(), méthode recommandée par Contact Form 7.

= 1.6.3 =
* Correctif mobile des tarifs : suppression du libellé « Tarif / Price / Preis » dans chaque cellule publique.
* Prix replacé directement à côté du nom du tarif, y compris sur mobile.
* Suppression du bouton public « Imprimer les tarifs ».
* Conservation d’un seul lien discret « Télécharger les tarifs en PDF ».
* Rendu Contact Form 7 du module Devis groupe renforcé avec apply_shortcodes et contrôle du résultat.
* Diagnostic Contact Form 7 ajouté dans l’administration Devis groupe.

= 1.6.2 =
* Correctif du shortcode Contact Form 7 dans le module Devis groupe.
* Validation CF7 assouplie sans autoriser d'autres shortcodes.
* Support du slash final et des variantes standards du shortcode CF7.
* Nouveau shortcode recommandé [parc_devis_groupe], avec [parc_devis] conservé comme alias.
* Variantes FR/EN/DE disponibles pour les deux noms.

= 1.6.1 =
* Consolidation sécurité/performance du module Devis groupe.
* Formulaire limité à un shortcode Contact Form 7 valide.
* Exemples préremplis et modifiables pour les blocs Devis groupe.
* Boutons optionnels FR/EN/DE dans les blocs et accordéons.
* Correction de l’onglet Devis groupe dans l’administration.

= 1.6.0 =
* Nouveau module « Devis / Groupes » pour habiller une page autour d’un formulaire existant sans modifier Contact Form 7.
* Nouveau shortcode [parc_devis] et variantes FR/EN/DE.
* Introduction, blocs d’informations et accordéons administrables et réordonnables.
* Fond transparent et styles légers héritant du thème.

= 1.5.12 =
* Ajout des boutons « Imprimer les tarifs » et « Télécharger PDF » dans le module Tarifs.
* Génération automatique du PDF à partir des tarifs publiés, dans l’ordre configuré dans l’administration.
* Export multilingue FR / EN / DE, prise en charge des offres spéciales et anciens prix barrés.
* Réglages simples pour le titre, le pied de page, la date de génération et l’orientation A4.
* La génération n’est exécutée qu’à la demande et n’alourdit pas le chargement normal des pages.

= 1.5.10 =
* Tarifs : réorganisation des onglets Individuels / Tarifs réduits / Groupes par glisser-déposer ou flèches.
* Réorganisation libre des lignes tarifaires avec glisser-déposer, flèches et duplication.
* Ajout, renommage, réorganisation et suppression des colonnes de prix dans chaque onglet.
* Conservation automatique des tarifs existants lors de la migration.
* Nouveau type de ligne « Offre / billet spécial » intégré au tableau classique.
* Une offre spéciale peut afficher un petit point, un ancien prix barré, un nouveau prix, une période de validité, une période d’affichage et un canal de vente.
* Lien d’achat spécifique facultatif en FR / EN / DE pour les billets spéciaux.
* Affichage responsive des nouvelles colonnes sur mobile.

= 1.5.9 =
* Ajout d’un bouton « Tester le pop-up » dans chaque bloc où un pop-up peut être activé.
* Prévisualisation immédiate à partir des valeurs saisies, avec choix FR / EN / DE.
* Recommandation d’image affichée dans l’administration : 800 × 450 px, format 16:9.
* Image des pop-up rendue plus harmonieuse et responsive sur ordinateur et mobile.

= 1.5.8 =
* Calendrier : ajout d’un point d’information cliquable sur les règles d’accès temporairement limité.
* La bulle d’explication est activable et son texte est modifiable en français, anglais et allemand.
* Administration réorganisée en onglets persistants pour éviter une longue page de réglages.
* Aucun réglage existant n’est supprimé.

= 1.5.7 =
* Bloc horaires de la page d’accueil simplifié : aucune date affichée.
* OUVERT devient la première information visuelle : gras et légèrement plus grand, sans imposer Caltons.
* Horaire du jour affiché juste dessous, puis dernière entrée en information secondaire.
* Avant ouverture : « Ouvert aujourd’hui » / « À partir de … » / dernière entrée.
* Après fermeture avec réouverture le lendemain : « À demain ! » / « Ouverture à … ».
* Sinon : « Prochaine ouverture » + date et heure de la prochaine ouverture.
* FR / EN / DE pris en charge.
* Aucun changement du contour, du fond ou du pictogramme de la page d’accueil, qui restent gérés par le thème.

= 1.5.5 =
* Nouveau profil Typographie : Hériter du thème / Optimisé pour Caltons Typeface / Personnalisé.
* Le mode Caltons agrandit légèrement les titres et resserre leur interligne sans embarquer ni imposer la police.
* Le profil typographique s'applique aussi aux shortcodes compacts de la page d'accueil.
* Conservation de l'affichage compact OUVERT / FERMÉ et de la prochaine ouverture introduit en 1.5.4.


= 1.5.3 =
* Suppression réelle de « Jour férié » dans la légende publique du calendrier.
* Ajout de la légende « Événement » avec pastille jaune et étoile, identique au langage visuel des événements ponctuels.
* La légende Événement apparaît uniquement lorsqu’un événement visible existe dans le mois affiché.
* Les légendes de périodes restent générées automatiquement selon les périodes visibles dans le mois.
* Les jours fériés restent disponibles comme donnée interne pour les règles, sans être imposés dans la légende publique.
= 1.5.0 =
* Calendrier minimaliste, pastilles gauche/droite, bandeaux de périodes et événements longs discrets.
* Détail de journée simplifié avec information d’accès sous les horaires et bouton événement aligné à droite.
* Légende des périodes générée dynamiquement à partir du mois affiché.

= 1.4.1 =
* Liens de boutons FR / EN / DE pour alertes, événements, périodes et pop-up liés aux exceptions.
* Priorité à qTranslate pour la langue des pop-up.
* Boutons du détail calendrier rendus fonctionnels avec l’URL de la langue active.
* Affichage conditionnel des réglages de bouton dans l’administration.

= 1.3.1 =
* Interface d’administration simplifiée : options secondaires repliées et réglages de pop-up masqués tant que le pop-up n’est pas activé.
* Séparation visuelle entre périodes spécifiques et événements.
* Les vacances scolaires sont traitées comme une période spécifique, pas comme un événement.
* Les événements peuvent durer un jour ou plusieurs mois et disposent d’un pictogramme et d’une couleur personnalisables.
* Bouton/lien et pop-up facultatifs conservés pour les événements.


= 1.3.0 =
* Nouveau système générique « Périodes spécifiques / événements » : vacances scolaires, événements, autres périodes repères.
* Même pictogramme étoile pour les événements, avec couleur choisie indépendamment pour chaque événement.
* Le nom, le message et un bouton facultatif d’un événement apparaissent dans le détail de la journée sélectionnée.
* Pop-up facultatif pour les événements, avec image optionnelle, bouton/lien et période de communication distincte de la date réelle.
* Les pop-up d’horaires et fermetures exceptionnels peuvent commencer X jours avant ou à une date/heure personnalisée.
* Les anciennes vacances scolaires sont migrées automatiquement vers le nouveau système sans perte de données.
* Réglages de taille simplifiés : Compacte / Standard / Grande lecture ; détail de la journée sélectionnée Standard / Grand / Très grand.
* Le détail de la journée sélectionnée est plus lisible par défaut sur mobile, sans agrandir la grille.
* Titre du calendrier simplifié : « Calendrier » puis année, sans répétition.
* Nouveaux shortcodes [parc_statut_fr], [parc_statut_en], [parc_statut_de] pour l’intégration OUVERT/FERMÉ dans un thème.
* Les shortcodes d’en-tête n’imposent ni police, ni marge, ni cadre : le thème conserve son design.
* Conservation des saisons, horaires, tarifs, couleurs, moyens de paiement, traductions et SVG lors de la mise à jour.


= 1.2.6 =
* Ajout d’un réglage Petit / Moyen / Grand pour la taille du calendrier sur mobile.
* Mode Moyen par défaut avec jours de semaine et légende plus lisibles.
* Les espacements restent compacts pour conserver le mois entier visible sur smartphone.

= 1.2.5 =
* Version de test reconstruite à partir de la 1.2.4 avec le même identifiant WordPress.
* Calendrier mobile encore plus compact : grille resserrée, cases et pictogrammes réduits, espaces verticaux diminués.
* Conservation des réglages existants : aucune réinitialisation de saisons, horaires, tarifs, couleurs, moyens de paiement ou SVG.
* Métadonnées WordPress/PHP et auteur conservés dans l’en-tête de l’extension.

= 1.2.4 =
* Bloc Aujourd’hui : affichage de la plage horaire du jour et de la dernière entrée.
* Calendrier mobile fortement compacté pour afficher le mois en cours plus clairement sur smartphone.
* Mois actif toujours recentré ; sur mobile seuls le mois précédent, actif et suivant sont affichés.
* Détail de la date sélectionnée déplacé au-dessus de la grille du calendrier.
* Message de réservation des groupes, texte du bouton, lien et message hors période entièrement modifiables et traduisibles.
* Réglages existants conservés lors de la migration vers le schéma 11.
* Sélecteurs de couleurs globaux des pictogrammes de paiement corrigés en vrais champs couleur.

= 1.2.3 =
* Moyens de paiement : carte bancaire et espèces en SVG intégrés, SVG personnalisé sécurisé ou aucun pictogramme.
* Les réglages et SVG existants sont conservés lors des mises à jour.
* Ajout des prérequis WordPress et PHP dans les métadonnées de l’extension.

= 1.2.2 =
* Correction de l’affichage des jours concernés après rechargement de l’administration.
* Une sauvegarde d’un autre réglage ne peut plus effacer les jours déjà enregistrés.
* Les jours ne sont modifiés que lorsque l’utilisateur agit réellement sur leur sélecteur.
* Normalisation des anciennes valeurs de jours pour les recocher correctement.
* Conservation de la correction de couleur des pictogrammes de moyens de paiement.

= 1.2.1 =
* Correction critique de la sauvegarde des jours concernés.
* Correction de la couleur du texte et des pictogrammes des moyens de paiement sur le site public.


= 1.1.5 =
* Réorganisation des réglages de couleurs : chaque section contient désormais ses propres réglages visuels.
* Tableau des tarifs : couleur du libellé, de la précision et du prix configurable indépendamment pour chaque ligne.
* Chaque ligne tarifaire peut utiliser un fond transparent ou une couleur unie et une bordure personnalisée.
* Les nouvelles lignes ajoutées héritent du thème par défaut et disposent immédiatement de leurs propres réglages de couleur.
* Couleurs séparées pour le petit titre TARIFS et le titre principal Tarifs + année.
* Couleurs dédiées aux notes des tarifs réduits et au message groupes hors période.
* Sélecteur de couleur administrable avec option « Hériter du thème » pour les couleurs facultatives.
* Protection supplémentaire de la sauvegarde lorsque les réglages généraux sont répartis entre plusieurs sections du formulaire.

= 1.1.2 =
* Personnalisation complète des couleurs et fonds de titres transparents ou unis.
* Couleurs séparées pour pictogrammes, textes de paiement, calendrier, tarifs, boutons et alertes.
* Conservation renforcée des jours concernés lors des mises à jour ; récupération depuis la sauvegarde de migration si une ancienne mise à jour les avait vidés.
* Avertissement en administration lorsqu'une période active ne contient aucun jour sélectionné.

= 1.1.1 =
* Ajout de la suppression et duplication sécurisées des saisons.
* Couleurs de texte configurables avec héritage du thème si laissées vides.
* Bloc Aujourd’hui simplifié : AUJOURD’HUI / OUVERT / dernière entrée.
* Suppression du texte redondant sous les moyens de paiement.
* Précision accompagnateur déplacée sous la ligne handicap.

= 1.1.3 =
* Calendrier affiché de janvier à décembre pour chaque saison publiée ; toute date sans consigne d'ouverture reste fermée.
* Une ouverture exceptionnelle peut ouvrir une journée normalement fermée.
* Jours fériés signalés par un cadre configurable sans modifier la couleur d'horaire de la case.
* Message jour férié configurable en FR/EN/DE.
* Message de contact pour les groupes affiché automatiquement lorsque le parc est fermé au public.
* Nouveaux shortcodes [parc_horaire_fr], [parc_horaire_en], [parc_horaire_de] pour le texte horaire dynamique d'en-tête.
* Le texte horaire affiche l'horaire normal ou exceptionnel, une fermeture exceptionnelle, puis la prochaine réouverture ou un décompte à J-30.

== Version 1.2.8 ==
* Pop-up facultatif directement lié aux horaires/fermetures exceptionnels.
* Contexte/motif affichable dans le détail calendrier et le bloc Aujourd’hui de la page complète.
* Les dates du pop-up lié à une exception reprennent automatiquement la période de cette exception.
* Libellé clarifié : vacances scolaires françaises – zone B.
* Conservation des alertes générales indépendantes et de leur apparence commune.

= 1.5.8 =
* Calendrier : ajout d’un point d’information cliquable sur les règles d’accès temporairement limité.
* La bulle d’explication est activable et son texte est modifiable en français, anglais et allemand.
* Administration réorganisée en onglets persistants pour éviter une longue page de réglages.
* Aucun réglage existant n’est supprimé.

= 1.5.5 =
* Nouveau profil Typographie : Hériter du thème / Optimisé pour Caltons Typeface / Personnalisé.
* Le mode Caltons agrandit légèrement les titres et resserre leur interligne sans embarquer ni imposer la police.
* Le profil typographique s'applique aussi aux shortcodes compacts de la page d'accueil.
* Conservation de l'affichage compact OUVERT / FERMÉ et de la prochaine ouverture introduit en 1.5.4.

= 1.4.1 =
* Liens de boutons FR / EN / DE pour alertes, événements, périodes et pop-up liés aux exceptions.
* Priorité à qTranslate pour la langue des pop-up.
* Boutons du détail calendrier rendus fonctionnels avec l’URL de la langue active.
* Affichage conditionnel des réglages de bouton dans l’administration.


= 1.2.8 (finalisation 09/08/2026) =
* Typographie réglable sur l’ensemble des modules.
* Shortcodes [parc_horaire_fr], [parc_horaire_en] et [parc_horaire_de] conservés pour les champs texte du thème.
* Pop-up et contexte liés aux exceptions, vacances scolaires configurables et règles d’accès au domaine conservés.
* Aucun réglage existant n’est réinitialisé lors de la mise à jour.

= 1.3.2 =
* Correctif : séparation stricte vacances / périodes repères / événements.
* Les périodes internes ne deviennent plus automatiquement des vacances scolaires.
* Une période peut être masquée du calendrier et désactiver temporairement les règles d’accès limité.
* Affichage des jours fériés désactivable (désactivé par défaut pour les nouvelles installations).
* Accès temporairement limité : titre personnalisable et utilisation réelle des horaires d’interruption, reprise et dernière entrée.
* Conservation des réglages existants et compatibilité des anciennes données.

= 1.3.3 =
* Correctif interne : séparation stricte entre vacances scolaires, périodes repères et événements.
* Les périodes internes n'activent plus implicitement les règles de vacances.
* Nettoyage des anciennes propriétés de compatibilité devenues inutiles.
* Réduction des données front-end : seules les lignes actives sont envoyées au navigateur.
* Évite un second chargement complet des réglages lors de la génération des données publiques.
