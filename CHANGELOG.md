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
