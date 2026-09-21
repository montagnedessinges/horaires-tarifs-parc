## 1.17.10

- Finalise le nettoyage de l’administration après les refontes 1.17.2 à 1.17.9 : les pages-ponts devenues inutiles ne constituent plus une seconde interface, tandis que les anciennes URLs restent compatibles par redirection.
- Réoriente directement `parcs-ht-periods` vers l’écran métier Périodes & événements 1.17.4 et l’ancienne page d’atterrissage Communication vers l’écran Pop-up 1.17.9.
- Audite les sources de vérité Groupes / Tarifs groupes / Devis groupes : le portail Groupes et le tableau public partagé utilisent la même liste annuelle canonique, tandis que la disponibilité du devis reste indépendante de la disponibilité des tarifs.
- Verrouille l’absence du faux message « Les tarifs groupes ne sont pas disponibles pour cette année. » et de l’ancien marqueur `data-group-tariff-unavailable` lorsque la grille Groupes de l’année est réellement disponible.
- Renforce les contrôles d’isolation annuelle : grilles, liaisons et calculs du devis restent strictement liés à l’année demandée, sans repli vers une autre saison.
- Conserve les renderers, migrations, normaliseurs et shortcodes historiques encore nécessaires ; les couches de compatibilité publiques ne sont pas supprimées tant qu’elles restent la source canonique réellement utilisée.
- Ajoute un garde transversal contre les sauvegardes d’administration tronquées : les formulaires protégés utilisent un snapshot JSON compact qui contourne `max_input_vars`, puis sont reconstruits côté serveur avant les handlers existants.
- Refuse toute requête incomplète avant écriture, tout en conservant les suppressions volontaires et les tableaux explicitement vidés lorsque le formulaire complet a bien été reçu.
- Protège Administration générale, Contenus & traductions, Horaires, Périodes/événements, Tarifs visiteurs, Groupes, Devis groupes, Guides pédagogiques, Pop-up, Calendrier de l’Avent et import CSV contre les pertes de données liées à un POST incomplet.
- Ajoute un audit documenté `AUDIT-1.17.10.md` et des tests bloquants pour le nettoyage final, la source annuelle Groupes, l’indépendance Tarifs/Devis, le transport compact des sauvegardes et le paquet de production.

## 1.17.9

- Sépare la rubrique Communication en deux pages techniques dédiées : Pop-up et Calendrier de l’Avent.
- Simplifie les alertes autonomes : les réglages détaillés restent masqués tant que le pop-up n’est pas activé, avec contenus FR / EN / DE, bouton facultatif et prévisualisation locale.
- Laisse les pop-up d’événements et d’horaires exceptionnels dans leur module d’origine afin d’éviter les contenus dupliqués, tout en conservant le moteur public commun.
- Conserve les réglages d’apparence historiques des pop-up et replie les options avancées sans forcer de raccordement au socle d’apparence globale.
- Donne au Calendrier de l’Avent sa propre page sans contexte d’année de saison ; campagnes, contenus, partenaires, résultats, import CSV et apparence par campagne restent inchangés.
- Affiche les shortcodes spécifiques de chaque campagne Avent avec l’attribut `campagne` et conserve tous les shortcodes publics historiques.
- Charge les nouveaux assets d’administration uniquement sur les pages Communication concernées et préserve le chargement conditionnel des assets publics.

## 1.17.8

- Remplace l’ancien accès aux Guides pédagogiques par un écran métier annuel dédié, plus lisible et repliable.
- Regroupe documents, cycles/niveaux, langues, badges, titres, descriptions, PDF et couvertures sans créer de nouveau stockage.
- Conserve les identifiants permanents des guides, les statistiques anonymes, les mécanismes Consulter/Télécharger et les shortcodes publics existants.
- Renvoie les libellés publics des guides, cycles/niveaux et langues vers Contenus & traductions FR / EN / DE tout en gardant les codes internes stables.
- Conserve les réglages visuels historiques des guides et replie les options avancées ; aucun raccordement forcé à l’apparence globale n’est introduit.
- Charge les nouveaux assets d’administration uniquement sur l’écran Guides pédagogiques.

## 1.17.7

- Ajoute un écran métier annuel « Devis groupes » séparé des Tarifs groupes et des Guides pédagogiques.
- Fiabilise le contexte d’année des écrans Groupes et des écritures admin/AJAX afin qu’aucune grille tarifaire d’une autre année ne puisse être utilisée.
- Réconcilie l’activation annuelle canonique des devis avec l’ancien stockage technique sans fallback inter-années.
- Conserve le recalcul serveur et lie chaque devis uniquement aux identifiants permanents de la grille Groupes de l’année sélectionnée.
- Supprime définitivement du renderer du portail Groupes la phrase parasite « Les tarifs groupes ne sont pas disponibles pour cette année. » et son ancien nœud d’affichage.
- Purge le cache LiteSpeed lors de la mise à jour de l’extension afin d’éviter qu’un ancien HTML public conserve ce message après installation.
- Regroupe les formulaires Contact Form 7 FR / EN / DE, l’accès au devis et les champs techniques ; les options avancées restent repliées.

## 1.17.6

- Remplace l’ancien passage par l’onglet historique des tarifs groupes par un écran métier annuel « Groupes » directement accessible depuis le sous-menu WordPress.
- Conserve une seule grille tarifaire Groupes canonique : le portail Groupes et le tableau public partagé continuent d’utiliser les mêmes prix et le même renderer existant.
- Isole strictement la sauvegarde Groupes : Individuels, Tarifs réduits, moyens de paiement visiteurs, impression/PDF et autres réglages tarifaires sont restaurés avant enregistrement et ne peuvent pas être écrasés par cet écran.
- Regroupe sur la page Groupes le titre, l’introduction, les moyens de paiement, les informations pratiques, le bouton de devis, le message d’année future et l’apparence propres au bloc public Groupes.
- Affiche l’état effectif de publication, l’état de préparation de la grille et la validité de la liaison avec le devis sans créer de second interrupteur annuel ; l’activation reste pilotée par les clés canoniques de la Vue d’ensemble / Administration générale.
- Maintient « Devis groupes » et « Guides pédagogiques » dans la famille Groupes tout en conservant des pages techniques séparées afin de ne charger que les outils nécessaires.
- Préserve le calendrier canonique Groupes, le moteur de devis et le comportement 1.16.8 lorsqu’une année Groupes est publiée avant les tarifs visiteurs.
- Ajoute un contrat de non-régression 1.17.6 exécuté sur les sources et sur le paquet de production.

## 1.17.5

- Remplace l’ancien pont « Tarifs visiteurs » par une page métier dédiée aux catégories Individuels et Tarifs réduits.
- Conserve les colonnes, lignes, identifiants permanents, offres spéciales, périodes de validité et canaux de vente déjà utilisés par le moteur public.
- Conserve les moyens de paiement visiteurs avec les canaux indépendants Sur place / En ligne ; l’achat en ligne reste réservé à Individuels.
- Isole strictement l’enregistrement des tarifs visiteurs : les prix, colonnes et réglages Groupes de la même année sont restaurés avant sauvegarde et ne sont jamais écrasés par cette page.
- Réutilise les enrichissements existants des offres, des colonnes visibles et des moyens de paiement sans créer de second moteur tarifaire.
- Ajoute le contexte d’année commun et un accès vers Contenus & traductions pour les textes éditoriaux FR / EN / DE.
- Replie les réglages avancés d’apparence et d’impression tout en conservant les réglages historiques pour compatibilité.

## 1.17.4

- Remplace l’ancien écran-pont Périodes & événements par un espace métier annuel dédié réunissant périodes repères, événements, exceptions et accès temporairement limité.
- Sépare visuellement les périodes de contexte et les événements sans fusionner leurs règles métier ni leurs données.
- Chaque événement peut conserver sa propre couleur et son propre pictogramme parmi les marqueurs déjà pris en charge par le calendrier public.
- Les horaires exceptionnels conservent la priorité sur les horaires habituels, avec un ou deux créneaux et retour automatique au planning normal à la fin de l’exception.
- Les pop-up d’exception réutilisent le contexte, le titre public et le message public comme source unique ; les dates et horaires peuvent être affichés indépendamment.
- Les réglages d’un pop-up restent masqués tant que « Activer le pop-up » n’est pas coché.
- Le module Accès temporairement limité et ses textes FR / EN / DE sont conservés, ainsi que le moteur CSV commun.
- Les anciens liens Horaires → Périodes, Exceptions et Accès limité redirigent vers le nouvel espace sans modifier les shortcodes publics ni le moteur calendrier canonique.

## 1.17.3

- Remplace le pont historique « Horaires & calendrier » par un écran métier dédié, léger et directement accessible depuis le sous-menu WordPress.
- Réorganise les horaires habituels autour d’un ou deux créneaux par période ; le second reste facultatif et les options avancées sont repliées.
- Ajoute le sélecteur d’année commun directement sur l’écran et redirige les anciens liens `htp-regular` vers le nouvel écran sans casser les liens existants.
- Réutilise exactement les données `regular_periods`, le moteur calendrier public canonique et le moteur CSV existant ; événements, exceptions et accès limité restent inchangés.
- Conserve tous les réglages visuels historiques propres aux horaires et au calendrier, notamment les couleurs fonctionnelles des états et des cases, sans raccordement global imposé.
- Renvoie les textes éditoriaux FR / EN / DE vers « Contenus & traductions » au lieu de les dupliquer dans le module.
- Préserve les shortcodes et toutes les données historiques ; aucun second moteur de calendrier n’est introduit.

## 1.17.2

- Fait de « Vue d’ensemble » l’entrée principale de « Gestion du parc » et stabilise l’ordre des sous-menus WordPress.
- Ajoute une navigation directe vers Horaires & calendrier, Périodes & événements, Tarifs visiteurs, Groupes, Communication, Aperçu, Mises à jour et Shortcodes, sans obliger à revenir par le tableau de bord.
- Regroupe visuellement « Tarifs groupes », « Devis groupes » et « Guides pédagogiques » sous la rubrique « Groupes », tout en conservant leurs moteurs et écrans techniques séparés.
- Regroupe visuellement « Pop-up » et « Calendrier de l’Avent » sous « Communication » ; chaque campagne du Calendrier de l’Avent reste indépendante avec ses propres données et son propre shortcode.
- Enrichit la Vue d’ensemble avec l’année administrée, les cinq activations annuelles, les dates automatiques, l’état effectif des modules, la version installée/disponible et les actions de mise à jour.
- Permet de modifier les activations annuelles depuis la Vue d’ensemble en réutilisant la même action et les mêmes clés canoniques que l’Administration générale, sans second stockage.
- Adapte l’updater au dépôt GitHub public : détection et téléchargement sans clé, mise à jour automatique sans token, vérification manuelle forcée et contrôle SHA-256 conservé ; une clé reste facultativement compatible si le dépôt redevient privé.
- Rend les libellés de l’Apparence globale explicitement visibles sans brancher prématurément tous les modules sur le nouveau socle visuel.
- Ne réécrit aucun moteur métier : la refonte interne des écrans Horaires, Tarifs, Groupes, Devis, Guides et Communication continue progressivement dans les versions 1.17.3 et suivantes.

## 1.17.1

- Ajoute une nouvelle « Administration générale » comme entrée principale de l’extension, tout en conservant l’ancienne vue détaillée comme filet de sécurité pendant la refonte.
- Regroupe les informations globales du parc, la gestion des saisons et la publication annuelle avec les cinq activations existantes et les dates automatiques d’activation / désactivation.
- Introduit un référentiel d’apparence globale non destructif pour les couleurs, textes, bordures, liens, focus, boutons, cartes, onglets, badges et espacements communs.
- Prépare la cascade future « apparence globale → personnalisation du module → personnalisation d’un élément », sans brancher encore les modules métier sur ces nouveaux jetons afin d’éviter tout changement visuel involontaire.
- Supprime le message public global « Les tarifs groupes ne sont pas disponibles pour cette année » du portail Groupes et ajoute un test de non-régression dédié.
- Ne supprime aucune clé historique, conserve les données et moteurs existants, et reste compatible avec PHP 7.4 / 8.1 / 8.2 / 8.3.

## 1.17.0

- Ajoute une vue d’ensemble légère dans l’administration pour accéder rapidement aux réglages canoniques sans dupliquer les données ni les moteurs existants.
- Ajoute un espace « Contenus & traductions » centralisant les principaux textes publics FR / EN / DE : tarifs, boutons, calendrier, statuts d’ouverture, messages Groupes et libellés génériques des guides pédagogiques.
- Permet de personnaliser par langue le lien de renvoi vers l’espace Groupes, tout en conservant la détection automatique et les réglages historiques comme repli.
- Corrige le message parasite « Les tarifs groupes ne sont pas disponibles pour cette année » lorsqu’un panneau tarifaire existe réellement pour l’année sélectionnée.
- Conserve les moteurs horaires, calendrier, tarifs, devis, guides, exports, Calendrier de l’Avent, migrations et données annuelles existants.
- Préserve la compatibilité PHP 7.4 / 8.1 / 8.2 / 8.3 et renforce les contrats de non-régression des versions 1.16.7 et 1.16.8 pour les versions ultérieures.

## 1.16.8

- Réutilise le même renderer canonique des tarifs groupes dans le portail Groupes et dans l’onglet Groupes du tableau public lorsque les tarifs visiteurs de l’année sont publiés.
- Les moyens de paiement, informations pratiques, styles et bouton de devis des groupes restent ceux de la configuration Groupes, sans fuite des moyens visiteurs ni bouton « Acheter vos billets ».
- Lorsqu’une année est déjà publiée pour les groupes mais pas encore pour les visiteurs, le tableau public n’expose aucun prix : l’onglet Groupes affiche un message dédié et un lien vers l’espace Groupes.
- Indique explicitement que les tarifs Individuels et Réduits de cette année ne sont pas encore disponibles.
- Détecte la page contenant le portail Groupes sans figer de slug ; le lien Groupes historique reste utilisé en secours.
- Conserve le sélecteur annuel Groupes, le calendrier canonique existant, la logique des dates automatiques et le moteur de devis sans changement.

## 1.16.7

- Ajoute deux canaux indépendants et cumulables pour chaque moyen de paiement visiteurs : Sur place et En ligne.
- Place les moyens de paiement juste sous l’onglet tarifaire actif, avec rappel de la catégorie affichée.
- Limite les tarifs réduits aux moyens de paiement Sur place et retire tout achat en ligne de cet onglet.
- Affiche le bouton « Acheter vos billets » uniquement dans l’onglet Individuels.
- Conserve la compatibilité des moyens historiques : carte bancaire Sur place + En ligne, autres moyens Sur place tant que l’administration n’est pas modifiée.
- Ne modifie pas le moteur du portail Groupes, le calendrier Groupes ni le moteur des devis.

## 1.16.6

- Gratuité des enfants de moins de 5 ans affichée uniquement sur place, sans lien d’achat ni doublon en ligne.
- Encadré unique utilisant la même présentation que les tarifs réduits.
- Correction du rendu des anciennes données sans migration ni modification des autres tarifs.
- Test de rendu couvrant 48 variantes de données et trois exclusions.

## 1.16.0

- Refonte complète du rendu public des tarifs sans modifier le moteur des horaires ni le calendrier existant.
- Nouveau composant compact et responsive pour les tarifs individuels, réduits et groupes, avec fond transparent conçu pour une section de page blanche.
- Onglets, années et moyens de paiement restent sur une ligne avec défilement horizontal local sur les petits écrans, sans débordement global de la page.
- Les cellules tarifaires vides ne sont plus rendues ; le canal Sur place reste neutre et le canal En ligne est visuellement distinct et cliquable lorsqu’un lien d’achat est disponible.
- Le shortcode Groupes affiche les tarifs avant les horaires, tout en conservant des moyens de paiement et des réglages distincts entre visiteurs et groupes.
- La charte graphique du parc fournit les valeurs par défaut ; les titres sont noirs sur les blocs transparents et les réglages de couleurs existants restent personnalisables.
- La version 1.15.18 et son correctif du moteur annuel des devis groupes sont conservés intégralement comme base de cette mise à jour.

## 1.15.18

- Refonte complète du moteur des devis groupes autour d’un état d’activation strictement indépendant pour chaque année.
- Une date de visite utilise exclusivement la grille tarifaire de son année : aucune liaison, colonne, ligne ou activation ne peut être héritée d’une année précédente ou future.
- Les tarifs 2026 et 2027 peuvent être actifs simultanément avec des prix et des identifiants différents sans conflit.
- Les données historiques servent uniquement à initialiser une fois les saisons déjà utilisées ; les saisons futures ne sont jamais activées automatiquement.
- Une migration d’administration aligne les anciens champs annuels avec le nouvel état afin que le bouton affiché et le moteur utilisent la même valeur.
- Le calcul des devis reste recalculé côté serveur et les effectifs sont validés comme entiers.
- Les tests couvrent les combinaisons 2026/2027 ON/OFF, les changements de grille et l’absence de tout fallback inter-années.

## 1.15.12

- Restaure les montants visiteurs des anciennes colonnes sans écraser les cellules existantes.
- Sur place en premier ; En ligne uniquement si des prix existent (2026 : Sur place seul).
- Quatre commandes annuelles visibles et indépendantes pour calendrier, visiteurs, devis groupes et groupes publics.
- Devis 2027 autorisé indépendamment du brouillon de la saison ; sauvegardes partielles préservées.
- Navigation visiteurs entre années en mémoire, sans requête ni rechargement, avec clavier et instances isolées.
- Tests de comportement PHP/DOM bloquants ajoutés à la CI.

## 1.15.9

- Harmonise le nom de l’extension affiché par le système de mise à jour privé avec le nom WordPress « Gestion du parc ».
- Synchronise le numéro de version de l’en-tête, de la constante interne et du fichier d’information de l’extension.
- Ajoute un contrat de non-régression exécuté sur les sources et sur le ZIP de production afin de bloquer toute divergence future des métadonnées de mise à jour.
- Corrige le contrat du Calendrier de l’Avent qui était figé sur la version 1.15.8, afin qu’il protège le correctif à partir de cette version sans bloquer les versions suivantes.
- Documente la règle de travail : une demande limitée au développement et à la publication reste entièrement dans GitHub ; les sites WordPress ne sont consultés que sur demande explicite.
- Aucun horaire, tarif, saison, événement, formulaire, contenu public ou réglage propre aux deux parcs n’est modifié.

## 1.15.6

- Clarifie le détail d’une journée du calendrier : la date reste en premier, suivie d’un titre « Horaires du parc », des horaires et de la dernière entrée.
- Distingue visuellement l’accès temporairement limité dans un bloc teinté sans le présenter comme une fermeture du parc.
- Rend modifiables le titre des horaires du parc, la couleur du bloc d’accès et les deux phrases automatiques de la règle d’accès.
- Les heures restent entièrement issues des champs existants (interruption, reprise, dernière entrée) et ne sont jamais figées dans le rendu.
- Conserve sans modification les textes complémentaires et l’infobulle déjà configurés sur la règle d’accès.
- Migration non destructive : les données existantes sont conservées ; seuls les nouveaux champs absents reçoivent des valeurs initiales modifiables.

# Historique des versions

## 1.15.3
- Le Calendrier de l’Avent est désormais intégré directement dans « Horaires du parc » comme un véritable onglet principal de l’extension ; le menu WordPress séparé est supprimé.
- L’entrée dans l’Avent ne change plus de page WordPress : le moteur d’onglets principal affiche l’espace Avent dans la même administration et masque proprement les réglages de saison qui ne le concernent pas.
- Les sous-sections Campagne, Teasings sociaux, Calendrier, Grand jeu, Partenaires, Résultats et Import / export restent chargées à la demande dans ce même onglet grâce à un fragment serveur dédié.
- La grille responsive des 24 jours introduite en 1.15.2 est conservée et l’espace Avent utilise désormais toute la largeur utile de l’administration.
- Les campagnes et données existantes sont conservées sans migration ni réinitialisation ; aucun contenu, partenaire, lot ou date MDS/FDS n’est ajouté en dur.

## 1.15.2
- Les onglets du Calendrier de l’Avent se chargent désormais dans la même interface sans rechargement complet de la page WordPress ; le serveur continue à ne rendre qu’une vue à la fois.
- Les clics sur un jour, un teasing, un partenaire ou un résultat utilisent le même moteur de navigation interne, avec conservation de l’historique précédent/suivant du navigateur et repli vers la navigation WordPress normale si JavaScript échoue.
- Le sélecteur de campagne utilise également cette navigation interne ; aucun éditeur des 24 jours ni aucune donnée sensible n’est préchargé dans le navigateur.
- Correction de l’incohérence entre le HTML et la feuille de style du calendrier d’administration : les 24 jours s’affichent désormais en vraie grille responsive de cartes au lieu d’une longue ligne de liens.
- Aucun contenu annuel, partenaire, date, lot ou donnée spécifique MDS/FDS n’est ajouté en dur.
- Ajout de tests de non-régression dédiés à la navigation interne et à la grille du calendrier.

## 1.15.1
- Refonte structurelle de l’accès au Calendrier de l’Avent dans l’administration : le module dispose désormais de son propre menu WordPress de premier niveau et d’un accès natif depuis la navigation de « Horaires du parc ».
- Suppression de l’injection JavaScript qui ajoutait après coup les shortcodes Avent à la table d’administration. La page Shortcodes est désormais alimentée directement par le registre central, source unique de vérité.
- Renommage du contrôleur admin Avent vers le fichier canonique `class-parcs-ht-advent-admin.php` ; l’ancien nom technique `-v2` est supprimé.
- Conservation du moteur Avent `schema_version = 3`, des données existantes, des shortcodes publics, de la sécurité serveur et de l’isolation MDS/FDS sans réinitialisation de campagne.
- Renforcement du test de contrat pour bloquer toute régression vers un sous-menu seul ou une surcouche DOM JavaScript.

## 1.15.0
- Premier prototype fonctionnel du Calendrier de l’Avent selon le cadrage 0.7 / `schema_version = 3` : campagnes, 24 journées, teasings sociaux, partenaires, résultats, grand jeu, règlement dynamique et import CSV de test.
- Ajout des shortcodes `[parc_calendrier_avent]` et `[parc_reglement_avent]` avec variantes FR / EN / DE et aperçu date + heure.
- Validation serveur des ouvertures, résultats, indices et mot mystère ; le formulaire final n’est rendu qu’après autorisation serveur.
## 1.13.8
- Séparation complète de l’apparence entre `[parc_tableau_tarifs]` et `[parc_tarifs_groupes]` : les deux shortcodes continuent de lire les mêmes données tarifaires, mais leurs couleurs sont désormais indépendantes.
- Ajout dans Groupes → Tarifs d’un bloc « Apparence du shortcode Tarifs groupes » reprenant le même modèle de couleurs utile au visuel tarifaire : titre, moyens de paiement, panneau, prix, message groupes et bouton de devis.
- Les couleurs de chaque ligne groupe sont également enregistrées séparément par identifiant permanent pour éviter qu’une couleur de ligne du tableau général ne modifie le shortcode groupes.
- Migration sans rupture visuelle : lors du passage en 1.13.8, la palette actuelle du tableau général et les couleurs actuelles des lignes groupes sont copiées une seule fois dans les réglages propres au shortcode groupes. Les modifications ultérieures sont indépendantes.
- Aucun tarif, libellé, colonne, règle de devis, moyen de paiement ou contenu public n’est dupliqué : seule la présentation est séparée.
- Ajout de tests vérifiant qu’une palette violette du tableau général peut coexister avec une palette rose du shortcode groupes, tout en partageant immédiatement le même prix canonique.
## 1.13.7
- Généralisation complète du contenu public du shortcode `[parc_tarifs_groupes]` : les moyens de paiement et les blocs d’information ne dépendent plus de `site_type` dans le rendu.
- Ajout dans Groupes → Tarifs de réglages modifiables par saison et par installation pour activer/masquer les moyens de paiement, modifier leur titre, ajouter/supprimer/réordonner les moyens, choisir une icône et saisir les libellés FR/EN/DE.
- Ajout d’une liste libre de blocs d’information sous les tarifs, chacun activable, supprimable, réordonnable et entièrement éditable en FR/EN/DE.
- Le titre, l’introduction et le bouton de devis du shortcode utilisent désormais réellement les réglages d’affichage groupes existants, sans créer de deuxième source de tarifs.
- Migration automatique du contenu MDS introduit en 1.13.6 vers les nouveaux réglages éditables afin de conserver l’affichage actuel après mise à jour.
- Les installations FDS et les autres sites ne reçoivent aucune condition MDS par défaut ; ils peuvent définir leurs propres moyens de paiement et informations.
- Les prix restent exclusivement issus de la grille canonique `tariffs.groups` de la saison publique ; les réglages d’affichage ne contiennent aucun prix.
- Ajout de tests couvrant la migration MDS, l’absence de fuite vers FDS, la personnalisation d’un autre site, l’ordre d’affichage et la source tarifaire unique.
## 1.13.6
- Amélioration du shortcode `[parc_tarifs_groupes]` pour la Montagne des Singes 2026 : le bloc reprend désormais la même hiérarchie visuelle que les tarifs classiques, avec les moyens de paiement placés sous le titre et avant les prix.
- Ajout des moyens de paiement groupes confirmés pour la Montagne des Singes : carte bancaire, espèces, chèque, bon de commande / voucher et Chorus Pro. Les ANCV individuels ne sont pas réutilisés automatiquement pour les groupes.
- Ajout après le tableau d'un rappel compact « Paiement et facturation » : règlement sur place, conditions du règlement différé, informations Chorus Pro, facturation selon le nombre réel de participants présents et absence de paiement avant la visite.
- Ajout d'un rappel « Devis et réservation » : réservation obligatoire, devis généré automatiquement et envoyé par e-mail, retour signé avec la mention « Bon pour accord » et présentation du devis imprimé le jour de la visite.
- Les informations spécifiques à la Montagne des Singes sont conditionnées au `site_type=mds` et ne sont pas appliquées automatiquement à la Forêt des Singes.
- Les tarifs restent lus exclusivement depuis la grille Groupes canonique de la saison publique ; aucun tarif ni moyen de paiement individuel n'est copié dans une seconde grille.
- Extension du test d’exécution du shortcode pour vérifier l’ordre moyens de paiement → tarifs → informations → bouton, ainsi que l’absence de fuite des règles MDS vers FDS.
## 1.13.5