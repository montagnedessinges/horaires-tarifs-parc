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
