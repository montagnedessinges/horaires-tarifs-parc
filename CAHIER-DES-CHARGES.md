# Horaires et tarifs du parc — version 1.0

## Principe

Une archive identique est installée séparément sur chaque site. Chaque installation conserve uniquement les données du parc concerné.

## Priorités horaires

1. fermeture exceptionnelle ;
2. horaires exceptionnels ;
3. horaires habituels ;
4. fermé si aucune période ne correspond.

À priorité numérique égale, une fermeture exceptionnelle l’emporte.

## Calendrier public

- horaire du jour en grand ;
- mois en cours sélectionné automatiquement, ou prochain mois ouvert hors saison ;
- un seul mois affiché en grand ;
- sélection des mois, flèches et balayage tactile ;
- `!` pour des horaires exceptionnels ;
- `×` pour une fermeture exceptionnelle complète ;
- détail complet après sélection d’une journée.

Une interruption d’accès au domaine n’a aucun symbole dans le calendrier. Son texte apparaît uniquement, en petit, après sélection d’une journée concernée.

## Langues

Les données chiffrées sont communes. Les textes sont saisis séparément en FR, EN et DE dans l’administration. Chaque module possède un shortcode distinct par langue.

## Tarifs

Trois onglets accessibles : Individuels par défaut, Tarifs réduits, Groupes. Les lignes peuvent être ajoutées ou supprimées indépendamment sur chaque site.

## Cache

L’horaire du jour est calculé dans le navigateur avec le fuseau Europe/Paris. L’enregistrement des réglages déclenche également une purge LiteSpeed.


## Évolution 1.0.2 — moyens de paiement
Les moyens de paiement peuvent être accompagnés de pictogrammes paramétrables depuis WordPress. Le bloc est affiché au-dessus des onglets de sélection des tarifs et s’adapte aux écrans mobiles.


## Mise à jour 1.0.3
- Pop-up d’alerte automatique sur le site, sans shortcode, avec sélection FR/EN/DE côté navigateur et affichage une fois par session.
- Protection des réglages contre les soumissions incomplètes : les anciennes valeurs sont conservées si une section est tronquée.
- Migration MDS : restauration des horaires 2026 préremplis uniquement si une ancienne sauvegarde les a entièrement perdus.
- « Ouvert aujourd’hui » n’affiche plus l’heure de fermeture.
- Fonds de contenu transparents et texte hérité du thème ; boutons en fond uni ; cases calendrier en fond uni selon la couleur horaire avec contraste automatique du texte.
- Le titre des tarifs est isolé des styles H2 du thème afin d’éviter son remontage en haut de page.
- Les liens des boutons billetterie et devis restent modifiables par langue dans les réglages.
- Les ressources lourdes du calendrier et des tarifs ne sont plus chargées globalement sur toutes les pages.

## Évolution 1.1.0 — saisons, mises à jour et qTranslate-XT

- Une saison est ajoutée explicitement par l'administrateur ; aucune année future n'est créée automatiquement.
- Une saison peut rester en brouillon puis être publiée. Une saison en brouillon n'est jamais exposée côté public.
- Si plusieurs saisons sont publiées, le calendrier affiche un sélecteur d'année. Avec une seule saison publiée, ce sélecteur est masqué.
- Les mois n'affichent plus l'année dans chaque bouton ; l'année est gérée au niveau de la saison.
- Une saison publiée sans dates complètes renvoie un état « dates et horaires pas encore disponibles » et non un état « fermé ».
- Les données calendaires sont communes aux langues ; seuls les textes éditoriaux disposent des onglets FR / EN / DE.
- qTranslate-XT est utilisé comme source de langue lorsqu'il est disponible, notamment pour le pop-up général d'alerte.
- La migration depuis les versions 1.0.x conserve les données existantes et crée une sauvegarde interne avant conversion.
- Le remplacement du ZIP conserve le même dossier d'extension afin de permettre la mise à jour par remplacement dans WordPress.


## Évolution 1.1.2 — personnalisation visuelle et migration des jours

- Toutes les couleurs principales de l'affichage public sont configurables depuis WordPress : textes, titres, pictogrammes, boutons, onglets, bordures, calendrier et alertes.
- Les fonds des titres Aujourd'hui, Calendrier, Tarifs et Moyens de paiement peuvent être transparents ou utiliser une couleur choisie.
- Les cases du calendrier restent en fond uni selon la couleur de leur horaire/statut.
- Lors d'une mise à jour, les tableaux `weekdays` des périodes horaires et des règles du domaine sont conservés. Si une migration antérieure les a vidés et qu'une sauvegarde pré-1.1.0 existe, ils sont restaurés sans écraser des jours déjà présents.
- Une période active sans aucun jour concerné déclenche un avertissement dans l'administration.

## Évolutions 1.1.3

- Une saison publiée affiche les douze mois de son année ; une date non couverte par une période d'ouverture est fermée par défaut.
- Une exception de type « horaires exceptionnels » peut ouvrir une date normalement fermée.
- Les jours fériés conservent la couleur d'horaire de leur case et reçoivent un cadre configurable (couleur + épaisseur), avec un message FR/EN/DE au clic.
- Le panneau Groupes peut afficher, lorsque le parc est fermé au public, un message FR/EN/DE et une adresse e-mail configurables pour les demandes hors période.
- Shortcodes d'en-tête : `[parc_horaire_fr]`, `[parc_horaire_en]`, `[parc_horaire_de]`. Ils affichent uniquement la ligne dynamique destinée à remplacer un texte tel que « 10h à 18h00 » : horaire habituel, horaire exceptionnel, fermeture exceptionnelle, prochaine réouverture, puis décompte à partir de J-30.


## Évolutions 1.1.5

- Les réglages de couleur sont déplacés dans la section fonctionnelle correspondante (horaires, jours fériés, alertes, tarifs).
- Le tableau des tarifs devient personnalisable ligne par ligne : libellé, précision et prix ont chacun leur couleur facultative.
- Chaque ligne tarifaire peut avoir un fond transparent ou uni et une couleur de séparation/bordure.
- L’ajout de nouvelles lignes tarifaires reste disponible pour Individuels, Tarifs réduits et Groupes.
- Les couleurs facultatives utilisent un sélecteur visuel et une option d’héritage du thème.
- La sauvegarde conserve les valeurs absentes si un formulaire est tronqué par les limites PHP.

## Évolutions 1.2.0
- Une seule base technique commune aux langues : saisons, dates, horaires, jours concernés, exceptions, prix et couleurs.
- Sélecteurs FR/EN/DE locaux uniquement sur les champs de texte traduisibles.
- Protection des jours concernés lors d'une sauvegarde/migration ; distinction entre champ volontairement vide et champ non reçu.
- Lignes tarifaires autonomes avec titre, sous-titre, texte secondaire, prix et couleurs.
- Moyens de paiement ajoutables/supprimables avec nom FR/EN/DE, visibilité par langue, pictogramme et couleurs.
- Formats d'horaires localisés côté public : français, anglais AM/PM et allemand avec « Uhr ».
- Auteur/développeur : Tanguy Huriez – Montagne des Singes.


## Évolution 1.3.0 — événements, lisibilité et intégration thème

- La rubrique vacances devient un moteur générique « Périodes spécifiques / événements ».
- Les vacances scolaires et autres périodes repères restent distinctes des vrais événements (fête, concours, animation, etc.).
- Tous les événements utilisent une étoile comme repère visuel ; la couleur de l’étoile est configurable par événement.
- Au clic sur une date, le nom de l’événement, son message et son bouton facultatif sont affichés au-dessus des horaires de la journée.
- Un événement peut déclencher le moteur commun de pop-up avec image facultative, texte FR/EN/DE, bouton et lien.
- La période de communication du pop-up est indépendante de la période réelle : même jour, X jours avant ou date/heure personnalisée.
- La même anticipation est disponible pour les horaires et fermetures exceptionnels.
- Les anciennes vacances scolaires sont conservées/migrées et peuvent continuer à neutraliser les règles d’accès au domaine.
- Les tailles sont pilotées d’abord par des préréglages simples ; les champs en pixels restent disponibles uniquement en réglages avancés.
- Le bloc de date sélectionnée est volontairement plus lisible sur mobile, tandis que la grille du mois reste compacte.
- Le titre public du calendrier affiche le mot « Calendrier » puis l’année seule.
- Les shortcodes `parc_statut_*` et `parc_horaire_*` permettent à un thème de récupérer les données automatiques tout en gardant son propre CSS.


## Version 1.5.5 — Typographie multi-sites
- Profil `Hériter du thème` : aucune adaptation typographique spécifique.
- Profil `Optimisé pour Caltons Typeface` : titres légèrement agrandis, interlignes resserrés, sans charger la police dans l’extension.
- Profil `Personnalisé` : conserve les réglages avancés de tailles déjà disponibles.
- Le bloc compact de la page d’accueil utilise les mêmes variables typographiques.


## Version 1.5.7 — Bloc horaires de la page d’accueil
- Le thème conserve son contenant, son fond et son pictogramme horloge.
- L’extension n’injecte que le contenu dynamique.
- Suppression complète de la date du jour dans ce bloc.
- Pendant l’ouverture : statut OUVERT en gras, horaire du jour, dernière entrée calculée.
- Avant ouverture : Ouvert aujourd’hui / À partir de l’heure d’ouverture / dernière entrée.
- Après fermeture, si réouverture le lendemain : À demain ! / Ouverture à l’heure prévue.
- Sinon : Prochaine ouverture / date + heure.
- Le bloc hérite de la police du thème et n’impose pas Caltons.


## Version 1.5.8 — Info-bulle accès limité et administration par onglets
- Le titre du bloc « Accès temporairement limité » peut afficher un petit point d’exclamation discret.
- Un clic ou un toucher ouvre une bulle d’information accessible, refermable par clic extérieur ou touche Échap.
- Le texte de cette bulle est configurable en FR / EN / DE et peut être désactivé par règle.
- Les règles existantes reçoivent un texte d’explication par défaut, qui reste entièrement modifiable.
- L’administration principale est présentée sous forme d’onglets ; un seul grand bloc de réglages est affiché à la fois.
- L’onglet actif est conservé après l’enregistrement.

## Version 1.5.9 — Test des pop-up et format d’image

- Chaque bloc disposant de l’option « Activer le pop-up » propose un bouton « Tester le pop-up » lorsque ses réglages sont visibles.
- La prévisualisation utilise les valeurs en cours de saisie et permet de choisir FR, EN ou DE sans publier le contenu.
- Pour les pop-up avec image, l’administration recommande un fichier de 800 × 450 px au format 16:9.
- L’image est recadrée sans déformation et reste compacte : largeur maximale harmonisée sur ordinateur et adaptation automatique sur mobile.



## Version 1.5.10 — Tarifs flexibles et billets spéciaux

- Les onglets tarifaires peuvent être réordonnés ; le premier devient l’onglet affiché par défaut.
- Les lignes d’un onglet peuvent être déplacées par glisser-déposer ou avec les flèches haut/bas, dupliquées et supprimées.
- Chaque onglet possède des colonnes de prix personnalisables : ajout, nom FR/EN/DE, ordre et suppression.
- La colonne de libellé du tarif reste structurelle ; au moins une colonne de prix est conservée.
- Les anciennes lignes à prix unique sont migrées dans la colonne `price` sans perte de données.
- Une ligne peut être définie comme « Offre / billet spécial » tout en restant dans son onglet tarifaire habituel.
- Les billets spéciaux gèrent : point de mise en avant, badge facultatif, ancien prix barré par colonne, période de validité, période d’affichage, canal de vente et lien d’achat FR/EN/DE.
- Le front reste responsive : les colonnes s’alignent sur ordinateur et se replient avec leur libellé sur mobile.
