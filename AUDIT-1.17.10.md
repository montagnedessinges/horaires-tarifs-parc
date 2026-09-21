# Audit final — Gestion du parc 1.17.10

La 1.17.10 cumule trois objectifs :

1. réaliser le nettoyage final prévu de la série 1.17.x après les refontes 1.17.2 à 1.17.9 ;
2. corriger transversalement les problèmes de sauvegarde pouvant ignorer ou supprimer des réglages ;
3. effectuer un audit global de stabilisation avant publication, avec contrôle des principaux moteurs publics et d’administration.

Cet audit distingue les éléments **corrigés**, **vérifiés sans anomalie détectée** et **conservés volontairement pour compatibilité**.

## 1. Corrigé en 1.17.10

### Sauvegardes d’administration

Certains écrans reconstruisent volontairement une liste complète depuis le POST ; une ligne absente signifie alors « supprimer ». Une requête tronquée par PHP pouvait donc être confondue avec une suppression volontaire.

La 1.17.10 ajoute `Parcs_HT_Admin_Save_Guard_11710` :

- les formulaires protégés sont sérialisés côté navigateur dans un snapshot JSON compact ;
- le snapshot utilise une seule variable POST et contourne ainsi `max_input_vars` ;
- les fichiers éventuels restent envoyés comme fichiers ;
- le contexte minimal nécessaire à la vérification du nonce reste disponible hors snapshot ;
- le garde vérifie capacité et nonce avant de reconstruire les données métier ;
- le serveur reconstruit ensuite `$_POST` avant le handler historique ;
- un marqueur final confirme que la requête est complète ;
- si le snapshot ou le marqueur est incomplet, la requête est arrêtée avant toute écriture ;
- une suppression volontaire reste possible, y compris une liste explicitement vide ;
- un snapshot destiné à une autre action est rejeté.

La protection couvre les principales sauvegardes des écrans Administration générale, Contenus & traductions, Horaires, Périodes/événements, Tarifs visiteurs, Groupes, Devis, Guides, Pop-up, Calendrier de l’Avent et import CSV.

### Navigation et couches historiques

- Le pont 1.17.2 « Périodes & événements » ne rend plus sa page intermédiaire : le slug `parcs-ht-periods` est rebondé sur l’écran métier canonique `Parcs_HT_Admin_Periods::page()` introduit en 1.17.4.
- L’ancienne page d’atterrissage `parcs-ht-communication` ne rend plus une seconde navigation intermédiaire : elle redirige vers la page Pop-up 1.17.9, qui contient déjà la navigation Pop-up / Calendrier de l’Avent.
- Les contrats 1.17.8 et 1.17.9 ne dépendent plus d’un numéro de version littéral et continuent donc de protéger leurs fonctionnalités dans les versions suivantes.

### Groupes / Tarifs groupes / Devis groupes

La source annuelle des tarifs Groupes reste `Parcs_HT_Public_Visibility::group_tariff_years()` :

- elle exige une grille Groupes réellement exploitable via `group_tariff_grid_ready()` ;
- elle applique séparément `group_tariffs_visible` et la fenêtre automatique de publication ;
- elle ne dépend pas de `group_quotes_enabled` ni de l’état du moteur de devis.

Le portail Groupes et le tableau public partagé lisent tous les deux cette même liste d’années. Le devis conserve sa propre disponibilité annuelle et sa propre liaison de tarifs.

Le message historique « Les tarifs groupes ne sont pas disponibles pour cette année. » et le marqueur `data-group-tariff-unavailable` ne doivent plus être produits par le portail, le renderer partagé, le shortcode Groupes ou l’administration Devis lorsque les tarifs sont réellement disponibles.

## 2. Vérifié sans anomalie détectée

### Isolation annuelle

Les tests couvrent notamment :

- année courante avec données ;
- année future avec données ;
- année sans données ;
- module masqué manuellement ;
- activation automatique pendant la fenêtre de publication ;
- désactivation automatique en fin de fenêtre ;
- tarifs Groupes publiés avec devis désactivé ;
- devis activé avec tarifs Groupes masqués ;
- conservation indépendante des prix 2026 et 2027 ;
- absence de fallback de devis ou de grille tarifaire vers une autre année.

Les moteurs Devis continuent d’utiliser `binding_for_year($year)` et `season_for_year($year)` pour l’année demandée uniquement.

### Horaires et calendrier

Vérifications de non-régression conservées :

- périodes d’ouverture habituelles ;
- second créneau facultatif ;
- horaires exceptionnels et fermetures exceptionnelles ;
- priorité des exceptions ;
- prochaine ouverture ;
- changement de mois du calendrier ;
- contexte d’une exception conservé dans le détail public ;
- import/export CSV des horaires ;
- catégories CSV absentes laissées intactes ;
- sécurité et taille des imports CSV.

### Tarifs visiteurs

Les tests valident notamment :

- séparation Individuels / Réduits / Groupes ;
- colonnes Sur place / En ligne ;
- absence de colonne vide lorsqu’un seul canal existe ;
- conservation des anciennes données tarifaires lors des normalisations ;
- absence de résurrection d’une valeur volontairement vidée ;
- achat en ligne limité au bloc Individuels ;
- moyens de paiement séparés des tarifs Groupes ;
- sélecteur d’année cohérent avec les années autorisées.

### Groupes et devis

Les tests vérifient :

- une seule grille Groupes canonique ;
- renderer public Groupes unique réutilisé par le portail et le tableau partagé ;
- moyens de paiement et informations Groupes conservés ;
- gratuités calculées sans dépasser l’effectif adulte ;
- identifiants permanents des lignes et colonnes ;
- devis calculé depuis les tarifs Groupes canoniques ;
- changement d’un prix Groupes répercuté sur le devis sans changer l’identité de la ligne ;
- état annuel du devis indépendant de la publication commerciale Groupes ;
- absence de fallback vers d’anciens prix ou une autre année ;
- validation serveur lorsque les horaires nécessaires au devis sont absents.

### Guides pédagogiques

Les tests valident :

- stockage historique conservé ;
- IDs permanents et non recyclés ;
- duplication de saison avec nouveaux IDs pour les copies ;
- filtres cycles et langues FR / EN / DE ;
- shortcodes historiques conservés ;
- statistiques Consulter / Télécharger ;
- absence de stockage IP/cookie dans ces statistiques ;
- sauvegardes successives vérifiées en relisant les données persistées ;
- apparence séparée conservée ;
- historique de sécurité quotidien conservé ;
- absence de double aperçu public dans l’écran Groupes.

### Pop-up

Les tests valident :

- moteur public partagé conservé ;
- sources alertes autonomes, exceptions et périodes spécifiques conservées ;
- réglages détaillés masqués tant que le pop-up n’est pas activé ;
- bouton facultatif ;
- textes FR / EN / DE ;
- apparence historique conservée ;
- chargement public conditionnel via la détection rapide des sources de pop-up.

### Calendrier de l’Avent

Les tests valident notamment :

- stockage et schéma historiques conservés ;
- isolation par installation ;
- exactement 24 journées ;
- campagnes, partenaires, contenus, résultats et traductions ;
- import avec dry-run et contrôle de campagne/parc ;
- conservation intelligente des médias lors d’un réimport ;
- sécurité nonce/capacité des actions d’administration ;
- détails d’un jour chargés à la demande ;
- données sensibles non exposées dans le payload initial ;
- mot mystère comparé côté serveur ;
- autorisation signée et limitation pour le grand jeu ;
- apparence par campagne ;
- pop-up quotidien optionnel ;
- comportement FR / EN / DE sans fuite de texte français dans les autres langues.

### Shortcodes et aperçus

Le registre central et les tests de non-régression couvrent les shortcodes Horaires, Aujourd’hui, Calendrier, Tarifs, Groupes, Devis, Guides, fermeture exceptionnelle et Calendrier de l’Avent, ainsi que leurs variantes de langue lorsque prévues.

Les aperçus d’administration utilisent les vrais renderers et ne chargent que l’aperçu sélectionné au lieu de rendre tous les shortcodes à l’ouverture de la page.

### JavaScript

La CI exécute :

- vérification syntaxique de tous les fichiers JavaScript ;
- tests du moteur frontend ;
- synchronisation de statut ;
- état d’affichage ;
- changements d’année et de calendrier ;
- réponses de devis arrivant dans le désordre ;
- suppression dans la page devis ;
- régressions UX Avent ;
- parité du sélecteur annuel.

### Sécurité et compatibilité

La CI de la PR 1.17.10 est validée sur :

- PHP 7.4 ;
- PHP 8.1 ;
- PHP 8.2 ;
- PHP 8.3.

Elle couvre la syntaxe PHP, les tests métier, les tests JavaScript, le paquet de production et PHPCS / WordPress Coding Standards / PHPCompatibility.

Le contrôle officiel WordPress Plugin Check reste exécuté par le workflow de publication sur `main`, avant création du ZIP et de la release GitHub.

### Paquet de production

La CI fabrique une copie nettoyée du plugin et revalide sur cette copie :

- syntaxe PHP ;
- syntaxe JavaScript ;
- principaux contrats métier ;
- shortcodes ;
- tarifs ;
- Guides ;
- Calendrier de l’Avent ;
- absence de fichiers de développement interdits.

Le workflow de release vérifie ensuite la structure du ZIP, sa version interne et son SHA-256.

### Performances et chargement conditionnel

- les composants propres à 1.17.10 restent administratifs ;
- le garde de sauvegarde et le nettoyage de navigation ne sont initialisés que dans l’administration ;
- le JavaScript du garde n’est chargé que sur les pages de l’extension ;
- aucun nouvel asset public n’est ajouté en 1.17.10 ;
- le moteur d’aperçu ne rend qu’un shortcode sélectionné ;
- le chargement conditionnel historique des pop-up et des modules publics est conservé.

## 3. Conservé volontairement pour compatibilité

- Les anciennes URLs `page=parcs-horaires-tarifs&tab=...` restent routées vers les nouveaux écrans. Elles servent de compatibilité de liens et de retours d’actions, pas de seconde interface.
- `Parcs_HT_Tariff_Public_Fixes` et `Parcs_HT_Tariff_Shared_1168` restent présents car le tableau public partagé réutilise encore explicitement leurs renderers canoniques.
- Les normaliseurs et migrations d’anciennes installations sont conservés lorsqu’ils restent nécessaires à la compatibilité des données existantes.
- Les shortcodes historiques restent enregistrés.
- Les clés de stockage existantes sont conservées ; aucune migration destructive n’est introduite par 1.17.10.
- Les moteurs historiques Guides, Devis et Avent restent les sources métier canoniques lorsqu’ils sont encore utilisés par les nouveaux écrans d’administration.

## 4. Tests bloquants propres à 1.17.10

- contrat du garde anti-troncature ;
- restauration runtime du snapshot JSON ;
- suppression volontaire d’un tableau vide ;
- conservation du contexte annuel ;
- refus d’un snapshot destiné à une autre action ;
- vérification du nonce avant reconstruction du snapshot ;
- contrat de nettoyage des pages-ponts ;
- source annuelle Groupes unique ;
- indépendance Tarifs Groupes / Devis Groupes ;
- année avec tarifs / sans tarifs ;
- activation manuelle et automatique ;
- absence du faux message d’indisponibilité et de son ancien marqueur ;
- conservation du renderer Groupes canonique ;
- isolation 2026 / 2027 et absence de fallback inter-années.

## 5. État de validation avant publication

La branche 1.17.10 doit rester non publiée tant que tous les contrôles ne sont pas verts. Au dernier audit de la branche, la matrice de qualité de la PR passe sur PHP 7.4, 8.1, 8.2 et 8.3, y compris tests métier, JavaScript, nettoyage du paquet de production et lint sécurité/compatibilité.

La publication finale reste soumise au workflow de `main`, notamment au WordPress Plugin Check officiel et à la validation du ZIP de release.
