# Référentiel d’import — Calendrier de l’Avent

Version de cadrage : **0.7**  
`schema_version = 3`

Ce document décrit le contrat canonique entre :

- le futur fichier Excel / Google Sheets d’import ;
- les champs WordPress du module Calendrier de l’Avent.

**Règle forte : les noms de champs doivent rester identiques entre import et WordPress.**

Le module prépare les textes sociaux dans l’administration mais ne publie jamais automatiquement sur Facebook ou Instagram.

## CAMPAGNE

Champs principaux :

- `schema_version`
- `campagne_id`
- `parc_code` — strictement `mds` ou `fds`
- `annee`
- `nom_campagne`
- `statut_campagne` — brouillon / active / archivee
- `langue_sociale`
- `langues_site`
- `fallback_traduction`
- `timezone`
- `date_ouverture_calendrier`
- `date_fin_calendrier`
- `heure_ouverture_globale`
- `page_calendrier_url`
- `page_reglement_url`
- `titre_bloc_calendrier_fr`
- `texte_intro_calendrier_fr`
- `libelle_comment_participer_fr`
- `texte_comment_participer_fr`
- `libelle_reglement_complet_fr`
- `reglement_complet_fr`
- `texte_tirage_non_effectue_fr`
- `teasing_site_visuel_source` — `aucun`, `url`, `wordpress`
- `teasing_site_visuel_url`
- `teasing_site_visuel_alt_fr`
- `reglement_quotidien_fr`
- `texte_rappel_grand_jeu_fr`
- `texte_lien_calendrier_social_fr`
- `grand_jeu_date_ouverture`
- `grand_jeu_heure_ouverture`
- `grand_jeu_date_fermeture`
- `grand_jeu_heure_fermeture`
- `grand_jeu_date_revelation`
- `mot_mystere`
- `grand_jeu_lot_fr`
- `grand_jeu_pictogramme_url`
- `grand_jeu_texte_saisie_fr`
- `grand_jeu_texte_erreur_fr`
- `grand_jeu_texte_succes_fr`
- `grand_jeu_formulaire_shortcode`
- `instagram_parc`
- `facebook_slug`
- `facebook_url_override`
- `hashtags_defaut`

### Règles CAMPAGNE

`page_calendrier_url` et `page_reglement_url` sont configurables et propres à chaque installation. Aucune URL MDS/FDS ne doit être codée en dur.

`reglement_complet_fr` alimente le shortcode `[parc_reglement_avent]` de la campagne active.

Le site utilise un seul visuel teasing public avant le premier jour. Les teasings sociaux restent des contenus séparés et n’imposent pas plusieurs teasings publics sur la page calendrier.

Tous les textes publics ont des valeurs par défaut dans le plugin mais restent éditables. Les libellés/microcopies non importés doivent eux aussi rester modifiables depuis WordPress.

### Sécurité CAMPAGNE

`mot_mystere` et `grand_jeu_formulaire_shortcode` sont des données serveur sensibles tant que le visiteur n’est pas autorisé.

Ils ne doivent pas être intégrés prématurément dans : HTML, JavaScript, `data-*`, JSON public, `wp_localize_script`, champs cachés ou commentaires publics.

Le mot saisi est validé côté serveur. Le shortcode du formulaire final n’est exécuté qu’après validation correcte et autorisation serveur.

## PARTENAIRES

Champs :

- `partenaire_id`
- `nom`
- `type_partenaire` — `externe` ou `parc`
- `instagram_handle`
- `instagram_url_override`
- `facebook_slug`
- `facebook_url_override`
- `site_url`
- `description_fr`
- `hashtags`
- `logo_source` — `aucun`, `url`, `wordpress`
- `logo_url`

Un réimport avec `logo_url` vide ne supprime pas un logo choisi manuellement dans WordPress.

## CONTENUS

Une même structure couvre les teasings sociaux et les 24 journées.

Champs de structure et planning :

- `contenu_id`
- `type_contenu` — `TEASING` ou `JOUR`
- `jour_numero` — obligatoire pour `JOUR`, 1 à 24 exactement une fois
- `date_publication`
- `heure_publication`
- `heure_ouverture` — override facultatif/avancé
- `facebook_actif`
- `facebook_date_publication` — override facultatif/avancé
- `facebook_heure_publication` — override facultatif/avancé
- `instagram_actif`
- `instagram_date_publication` — override facultatif/avancé
- `instagram_heure_publication` — override facultatif/avancé
- `phase`
- `titre_fr`

### Planning simplifié

Pour le fonctionnement normal :

- `date_publication` + `heure_publication` servent de date/heure principale ;
- elles pilotent par défaut l’ouverture de la case ;
- elles servent de repère à la publication sociale manuelle ;
- le plugin ne publie rien automatiquement.

Les overrides avancés peuvent rester vides.

Les teasings `TEASING` servent principalement au planning/générateur social avant le lancement. Le rendu public avant le 1er décembre utilise le visuel teasing unique défini dans CAMPAGNE.

### Champs éditoriaux JOUR

- `intro_partenaire_fr`
- `intro_question_fr`
- `partenaire_id`
- `lot_fr`
- `format_jeu` — `QCM`, `VRAI_FAUX`, `CHOIX_MULTIPLE`
- `question_fr`
- `reponse_a_fr`
- `reponse_b_fr`
- `reponse_c_fr`
- `reponse_d_fr`
- `bonne_reponse_code`
- `bonne_reponse_texte_fr`
- `explication_reponse_fr`

Pas de réponse libre pour les jeux quotidiens.

`explication_reponse_fr` est facultatif. Il sert au commentaire de résultat et à l’archive pour expliquer pourquoi la réponse est correcte ou apporter une précision utile.

### Indice du mot mystère

Champs :

- `indice_actif` — `oui` ou `non`
- `indice_lettre`
- `indice_position`
- `afficher_rappel_grand_jeu` — `oui`, `non`, `auto`
- `rappel_grand_jeu_override_fr`

Règles :

- l’admin présente `indice_actif` comme une case à cocher ;
- si cochée, afficher les champs lettre + position ;
- la loupe peut être publique le jour J ;
- la lettre et la position restent serveur pendant le jeu ;
- le texte social généré ajoute automatiquement `texte_rappel_grand_jeu_fr`, sauf override explicite ;
- `rappel_grand_jeu_override_fr` permet un texte spécifique pour une journée ;
- ni la lettre ni le numéro ne doivent apparaître dans le texte social avant révélation ;
- l’indice est révélé avec le résultat du jour, donc au plus tôt à J+1 et uniquement si le résultat est publié.

La date/heure de révélation de l’indice est donc dérivée du résultat quotidien ; il n’est plus nécessaire d’avoir un champ annuel séparé `date_revelation_indice` pour chaque contenu.

### Données sensibles CONTENUS

Sont sensibles avant révélation :

- `bonne_reponse_code`
- `bonne_reponse_texte_fr`
- `indice_lettre`
- `indice_position`

Le navigateur ne doit jamais être la source de vérité.

### Visuels

Champs :

- `visuel_source` — `aucun`, `url`, `wordpress`
- `visuel_url`
- `visuel_alt_fr`

Règles :

- format public prioritaire 4:5 vertical ;
- le visuel est central et peut être nécessaire pour répondre ;
- possibilité de l’agrandir côté public ;
- URL externe ou média WordPress ;
- réimport vide = conservation du média manuel ;
- placeholder 4:5 dans l’admin si absent ;
- `visuel_alt_fr` ne doit jamais révéler réponse ou indice.

### Réseaux sociaux — texte avant publication

Champs :

- `texte_post_override_fr`
- `facebook_post_url`
- `instagram_post_url`
- `statut`

Sans override, le générateur compose le texte à partir des données structurées.

Pour une journée avec indice, il ajoute automatiquement le rappel du grand jeu.

Pour toutes les publications quotidiennes générées, il ajoute l’URL `page_calendrier_url` avec le texte `texte_lien_calendrier_social_fr` afin de renvoyer vers la page centrale, l’explication et le règlement.

Le texte est copié puis publié manuellement par l’équipe.

## RÉSULTATS

Les gagnants restent séparés du bloc CONTENUS.

Champs :

- `resultat_id`
- `contenu_id`
- `date_revelation_resultat`
- `heure_revelation_resultat`
- `gagnant_facebook`
- `gagnant_instagram`
- `texte_resultat_override_fr`
- `story_resultat_override_fr`
- `statut_resultat` — `brouillon`, `pret`, `publie`

### Règle de révélation J+1

Par défaut, le résultat d’un jour devient révélable à la date/heure d’ouverture du jour suivant.

Exemple : jour 1 ouvert le 1er décembre à 9 h → résultat du jour 1 révélable le 2 décembre à 9 h.

Même si le résultat est saisi avant, il reste caché avant cette date/heure.

Après cette date/heure :

- si `statut_resultat != publie`, le public voit le texte configurable `texte_tirage_non_effectue_fr` ;
- si `statut_resultat = publie`, afficher bonne réponse, explication éventuelle, gagnants et indice éventuel.

La saisie seule des gagnants ne publie donc rien. L’admin doit disposer d’une action explicite `Publier le résultat`.

Le dernier jour peut avoir une date/heure de révélation spécifique puisqu’il n’existe pas de jour 25.

### Génération automatique des résultats sociaux

Une fois les données renseignées, l’admin génère :

- commentaire résultat Facebook ;
- commentaire résultat Instagram ;
- Story résultat ;
- bouton `Copier` pour chaque sortie.

Ordre logique :

1. bonne réponse ;
2. `explication_reponse_fr` si présente ;
3. gagnant du réseau ;
4. partenaire ;
5. conditions utiles ;
6. relance dynamique vers la suite du calendrier ou clôture finale.

Aucune publication automatique.

## GRAND JEU FINAL

Le 24 décembre conserve :

- le jeu quotidien normal ;
- la finale du mot mystère, séparée.

Le visiteur saisit le mot dans le bloc final lorsque la date/heure d’ouverture est atteinte.

Mot faux : le formulaire n’est pas rendu.

Mot correct : le serveur crée une autorisation temporaire puis exécute/rend `grand_jeu_formulaire_shortcode`.

Le formulaire ne doit jamais être préchargé et masqué avec CSS/JS.

Prévoir une limitation raisonnable des tentatives pour réduire le brute force.

## RÈGLEMENT DYNAMIQUE

Le shortcode `[parc_reglement_avent]` affiche `reglement_complet_fr` de la campagne active.

La page WordPress qui contient ce shortcode est créée manuellement par le site. Le plugin ne crée pas la page et n’impose pas son URL.

L’URL utilisée par le bouton public est `page_reglement_url`.

## TRADUCTIONS

Le français est la source principale. Les traductions restent dans un bloc séparé :

- `reference_id`
- `champ`
- `langue` — `en` ou `de`
- `texte`

Exemples de champs traduisibles : titre du bloc, introduction, explication `Comment participer ?`, règlement, lots, questions, réponses, explications, textes de finale.

## RÉIMPORT INTELLIGENT

Identifiants stables :

- `campagne_id`
- `partenaire_id`
- `contenu_id`
- `resultat_id`

Avant écriture, rapport obligatoire :

- créations ;
- modifications ;
- inchangés ;
- avertissements ;
- erreurs.

Éléments absents du nouvel import : conserver par défaut.

Visuels manuels : conserver si aucune nouvelle image n’est fournie.

Remplacement complet : uniquement après confirmation explicite.

## Séparation MDS / FDS

`parc_code` obligatoire : `mds` ou `fds`.

Refuser l’import si le `parc_code` ne correspond pas à l’installation.

Aucune campagne MDS ne doit apparaître sur FDS, et inversement.

## Format de fichier

- `.xlsx` principal ;
- CSV accepté pour les tests et éventuellement en complément si les textes multilignes restent fiables ;
- modèle officiel téléchargeable depuis WordPress ;
- `schema_version` obligatoire ;
- aide contextuelle par champ ;
- possibilité future de copier les instructions correspondant au schéma courant.

## RENDU PUBLIC — règles non négociables pour le développement

- `[parc_calendrier_avent]` rend uniquement un bloc, jamais une page complète ;
- arrivée sur la page = grille visible, aucune case ouverte automatiquement ;
- avant le premier jour = grille fermée + visuel teasing unique ;
- case ouverte = clic volontaire du visiteur ;
- visuel 4:5 central dans le détail ;
- jour J = question sans réponse/gagnants ;
- J+1 = résultat possible selon horaire + statut publié ;
- gagnants et indice restent ensuite visibles en archive ;
- `Comment participer ?` ouvre une explication courte dans le bloc ;
- bouton vers le règlement complet via URL configurable ;
- finale : mot validé côté serveur avant rendu du shortcode de formulaire.

## Contrat effectif après correction des réimports (novembre 2026)

Cette section prévaut sur le cadrage historique ci-dessus. Schéma **3**, **150 colonnes** dans le modèle/export courant, CSV UTF-8 (BOM accepté), séparateur `;`. Les noms de colonnes, pas leur position, pilotent l’import. Un sous-ensemble de colonnes connues est accepté ; `schema_version`, `parc_code` et `campagne_id` doivent être renseignés sur chaque ligne. Les colonnes inconnues/dupliquées, lignes de largeur incorrecte et identifiants d’entités répétés sont refusés. Les guillemets CSV protègent les points-virgules et retours à la ligne ; utiliser un export CSV, pas une concaténation manuelle.

### Écarts constatés dans la version 1.21.8

- `description_fr` était déjà persisté. Le générateur social ignorait cette description, `intro_partenaire_fr`, les liens/comptes et les hashtags partenaires. Le calendrier donnait déjà priorité à `intro_partenaire_fr`, pouvant masquer une description partenaire réimportée.
- Les médias étaient systématiquement ignorés malgré leur présence dans le schéma/export. Les statuts étaient ignorés volontairement et le restent.
- Les cellules vides conservaient les champs ordinaires, mais une traduction vide pouvait écraser le texte existant. Ce comportement est désormais cohérent.
- Sauvegarde complète avant import, analyse préalable, contrôle des 24 jours et empreinte anti-écrasement concurrent existaient déjà. Ils sont conservés ; le diff montre désormais les valeurs avant/après et une relecture contrôle l’écriture.
- Aucun statut propre au règlement n’empêchait la diffusion d’un brouillon dans une campagne active. Une validation manuelle liée au contenu exact couvre maintenant règlement complet, rappel quotidien, URL du règlement et leurs traductions.

### Réimport et effacement explicite

Une colonne absente ou une cellule vide conserve la valeur existante. Une valeur non vide remplace uniquement le champ fourni. Le marqueur exact `[VIDER]` efface exclusivement : `description_fr`, `hashtags`, `instagram_handle`, `instagram_url_override`, `facebook_slug`, `facebook_url_override`, `site_url`, `intro_partenaire_fr`, `intro_question_fr`, `texte_post_override_fr`, `reglement_complet_fr`, `reglement_quotidien_fr`, `page_reglement_url`, `hashtags_defaut`, `texte` (traduction). Tout autre effacement est refusé. Une traduction vide retombe sur le français lorsque le réglage de repli le prévoit.

Pour réutiliser la présentation courte du partenaire sur une journée ayant un texte spécifique, mettre `[VIDER]` dans `intro_partenaire_fr` de cette journée. Ce choix s’applique au calendrier et au générateur social. Les textes `texte_post_override_fr` gardent leur priorité éditoriale ; les hashtags partenaires et campagne sont ajoutés en fin de publication. Aucun compte n’est déduit du nom d’un partenaire : compte absent = nom conservé sans arobase inventée. Les URL sociales explicites priment sur les handles/slugs. Sur Instagram, un handle sans URL prioritaire donne `@handle` ; Facebook utilise l’URL de page (aucune garantie de mention native lors du collage).

### Médias et statuts

Par défaut, aucune URL média importée ne remplace la médiathèque. Cocher **Autoriser les URL médias non vides** avant l’analyse autorise explicitement les URL HTTP(S) de `logo_url`, `visuel_url`, `teasing_site_visuel_url` et `grand_jeu_pictogramme_url`. Les champs source associés deviennent `url`. Les URL sont référencées, pas téléchargées dans la médiathèque. Une cellule vide conserve le média, même avec cette option ; `[VIDER]` y est interdit. Pour retirer un média, passer par l’éditeur WordPress. Les seuls champs `*_source` n’effectuent aucune suppression.

`statut_campagne`, `statut`, `statut_resultat` sont toujours ignorés avec avertissement. L’import ne publie ni campagne, ni journée, ni résultat et ne change pas leurs statuts existants. Une nouvelle entrée conserve son état initial brouillon.

### Règlement : migration et validation

**Après installation de cette correction, les règlements existants nécessitent une validation manuelle**, même si la campagne est active. Dans Campagne, relire les textes et traductions puis choisir **Valider et autoriser la publication des textes enregistrés** et enregistrer. La validation n’est pas importable et n’ajoute aucune colonne au CSV. « Conserver » garde l’empreinte existante ; « Retirer » la révoque. Toute modification des champs couverts, par CSV ou dans l’éditeur, rend l’empreinte inapplicable jusqu’à une nouvelle validation. Un réimport identique ne la modifie pas. L’état de publication de la campagne reste inchangé.

Sans validation applicable, le shortcode règlement, son lien dans le calendrier et le rappel quotidien du générateur social sont masqués. Cette protection couvre ces champs structurés ; elle ne peut pas détecter un règlement copié dans un texte libre de publication, une page WordPress indépendante ou un PDF externe.

### Contexte FDS / MDS et contrôle sur site

Les deux Sheets de référence ont été rouverts le 18 novembre 2026 (lecture seule). L’onglet FDS **Campagne – plugin** confirme `campagne_id=2026`, `parc_code=fds`. L’exemple `fds_avent_2026` de l’ancien onglet référentiel n’est pas l’identifiant de cette campagne WordPress. Pour MDS, conserver l’identifiant réellement exporté et `parc_code=mds`. Aucun identifiant social des Sheets n’est codé en dur dans l’extension.

1. Installer la future release d’abord sur une copie de test ; exporter la campagne et conserver une sauvegarde de base complète.
2. Sélectionner la campagne voulue, importer le CSV et **Analyser sans écrire**. Vérifier parc, campagne, textes avant/après, avertissements et absence de modifications de statuts. Les sauvegardes d’import sont conservées dans les options WordPress `parcs_ht_advent_backup_<campagne>_*` ; ce sont des instantanés complets du stockage Avent, à restaurer uniquement après revue administrative (pas de bouton de restauration ajouté).
3. Appliquer une présentation courte et un hashtag modifiés ; rouvrir le partenaire et les aperçus Facebook/Instagram. Tester aussi un partenaire sans compte confirmé.
4. Vérifier une journée ouverte : description de repli ou introduction spécifique, logo, site et réseaux. Tester le retour au texte partenaire avec `[VIDER]`.
5. Réimporter à l’identique : aucune modification. Réimporter des cellules vides : conservation. Tester le remplacement média avec et sans option, puis l’interdiction d’effacement média.
6. Vérifier les campagnes 2026 et 2027, les deux parcs sur leurs installations, et le refus d’un import destiné à l’autre parc/campagne.
7. Dans une campagne active, importer un règlement provisoire : aucun rendu règlement ni rappel social. Valider manuellement, vérifier le rendu, puis modifier le texte ou sa traduction : validation de nouveau requise.
8. Modifier la campagne après analyse : l’application du rapport périmé doit être refusée. Purger le cache du site après installation/import pour vérifier le rendu frais.

Tests : `php tests/advent-import-runtime.php` (fonctions réelles, interfaces WordPress simulées en local), `wp eval-file tests/advent-import-runtime.php` et `wp eval-file tests/advent-import-wp.php` sur installation jetable uniquement (ces tests écrivent des données fictives). La CI teste PHP 7.4/8.1/8.2/8.3 et l’application réelle dans WordPress. Ces corrections sont livrées dans la version 1.21.9. La publication GitHub ne signifie pas que les sites WordPress ont été mis à jour ; installer la version puis effectuer la recette ci-dessus.
