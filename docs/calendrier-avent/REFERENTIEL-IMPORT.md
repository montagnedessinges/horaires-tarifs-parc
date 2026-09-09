# Référentiel d’import — Calendrier de l’Avent

Version de cadrage : **0.6**  
`schema_version = 2`

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
- `grand_jeu_date_ouverture`
- `grand_jeu_heure_ouverture`
- `grand_jeu_date_fermeture`
- `grand_jeu_heure_fermeture`
- `grand_jeu_date_revelation`
- `mot_mystere`
- `grand_jeu_lot_fr`
- `grand_jeu_pictogramme_url`
- `instagram_parc`
- `facebook_slug`
- `facebook_url_override`
- `hashtags_defaut`
- `reglement_url`
- `reglement_quotidien_fr`
- `texte_grand_jeu_fr`

### Sécurité CAMPAGNE

`mot_mystere` est une donnée serveur sensible. Elle ne doit jamais être intégrée dans le HTML, le JavaScript, un `data-*`, un JSON public ou une donnée localisée côté navigateur avant la date de révélation autorisée.

Le mot saisi par le visiteur doit être validé côté serveur.

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

Un réimport avec `logo_url` vide ne doit pas supprimer un logo choisi manuellement dans WordPress.

## CONTENUS

Une même structure couvre les teasings et les 24 journées.

Champs de structure et planning :

- `contenu_id`
- `type_contenu` — `TEASING` ou `JOUR`
- `jour_numero` — obligatoire pour `JOUR`, 1 à 24 exactement une fois
- `date_publication`
- `heure_publication`
- `heure_ouverture` — facultative et avancée ; vide = même heure que `heure_publication`, elle-même pouvant reprendre `CAMPAGNE.heure_ouverture_globale`
- `facebook_actif`
- `facebook_date_publication` — facultative et avancée
- `facebook_heure_publication` — facultative et avancée
- `instagram_actif`
- `instagram_date_publication` — facultative et avancée
- `instagram_heure_publication` — facultative et avancée
- `phase`
- `titre_fr`

### Règle de planning simplifié

Pour le fonctionnement normal, une seule date/heure doit suffire :

- `date_publication` + `heure_publication` servent de date/heure principale du contenu ;
- elles pilotent par défaut l’ouverture de la case ou du teasing sur le site ;
- elles servent aussi de repère pour la publication manuelle sur les réseaux ;
- le plugin ne publie rien automatiquement.

Les champs `heure_ouverture`, `facebook_date_publication`, `facebook_heure_publication`, `instagram_date_publication` et `instagram_heure_publication` ne sont que des overrides avancés. Ils doivent pouvoir rester vides et ne pas alourdir l’interface standard.

Champs éditoriaux :

- `intro_partenaire_fr` — 1 à 2 phrases maximum, courte mise en avant du partenaire
- `intro_question_fr` — courte introduction du thème
- `partenaire_id`
- `lot_fr`
- `format_jeu` — uniquement `QCM`, `VRAI_FAUX`, `CHOIX_MULTIPLE`
- `question_fr`
- `reponse_a_fr`
- `reponse_b_fr`
- `reponse_c_fr`
- `reponse_d_fr`
- `bonne_reponse_code`
- `bonne_reponse_texte_fr`
- `explication_reponse_fr`

### Règle des jeux

Pas de réponse libre pour les jeux quotidiens.

- QCM : une bonne réponse.
- Vrai/Faux : réponse fermée.
- Choix multiple : plusieurs réponses possibles.

`explication_reponse_fr` est facultatif. Il sert principalement à compléter le commentaire de résultat après l’annonce de la bonne réponse : expliquer pourquoi elle est correcte ou apporter une précision utile sur un comportement, les Magots, une activité ou un élément du parc. S’il est vide, le texte de résultat reste plus court.

### Données sensibles CONTENUS

Sont sensibles :

- `bonne_reponse_code`
- `bonne_reponse_texte_fr`
- `indice_lettre`
- `indice_position`

Ces données doivent rester côté serveur avant leur révélation prévue.

Le navigateur ne doit jamais être la source de vérité pour valider une réponse.

### Grand jeu et indices

Champs :

- `indice_actif` — `oui`, `non`, `presentation`, `final`
- `indice_lettre`
- `indice_position`
- `date_revelation_indice`
- `afficher_rappel_grand_jeu`

La présence d’une loupe peut être publique, mais la lettre et la position réelles ne doivent pas être transmises au client avant révélation.

### Visuels

Champs :

- `visuel_source` — `aucun`, `url`, `wordpress`
- `visuel_url`
- `visuel_alt_fr`

Règles :

- une URL externe peut préremplir un visuel ;
- le visuel peut ensuite être remplacé par la médiathèque WordPress ;
- un réimport avec visuel vide conserve le média manuel déjà choisi ;
- sans visuel, l’aperçu admin garde un placeholder propre au format prévu, notamment 4:5, afin de ne pas casser la mise en page.

### Réseaux sociaux — texte avant publication

Champs :

- `texte_post_override_fr`
- `facebook_post_url`
- `instagram_post_url`
- `statut`

`texte_post_override_fr` reste facultatif. S’il est renseigné, il remplace le texte généré automatiquement.

Sinon le plugin doit pouvoir composer le texte depuis les champs structurés : partenaire, lot, `intro_partenaire_fr`, `intro_question_fr`, question, réponses, rappel du jeu, grand jeu éventuel, hashtags et liens.

Le texte est ensuite copié depuis l’administration et publié manuellement par l’équipe du parc.

## RÉSULTATS

Les gagnants ne doivent pas être stockés dans le bloc principal CONTENUS.

Champs :

- `resultat_id`
- `contenu_id`
- `date_revelation_resultat`
- `heure_revelation_resultat`
- `gagnant_facebook`
- `gagnant_instagram`
- `texte_resultat_override_fr`
- `story_resultat_override_fr`
- `statut_resultat`

`texte_resultat_override_fr` remplace exceptionnellement le commentaire de résultat généré automatiquement.

`story_resultat_override_fr` remplace exceptionnellement le texte Story généré automatiquement.

### Génération automatique des résultats

Une fois la bonne réponse et les gagnants renseignés, le plugin doit pouvoir générer depuis l’administration :

- un commentaire résultat Facebook ;
- un commentaire résultat Instagram ;
- un texte court de Story résultat ;
- un bouton `Copier` pour chaque sortie.

Le commentaire résultat est composé, lorsque les données existent, à partir de :

1. `bonne_reponse_texte_fr` ;
2. `explication_reponse_fr` ;
3. gagnant(s) du réseau concerné ;
4. partenaire du jour ;
5. rappel éventuel des conditions partenaire ;
6. relance vers la suite du calendrier.

La relance doit être dynamique :

- si le contenu suivant est déjà ouvert, inviter à participer dès maintenant ;
- sinon annoncer le prochain rendez-vous à partir de sa date/heure ;
- après le dernier jour, utiliser une clôture adaptée et ne jamais annoncer une question inexistante.

La Story est une version courte pouvant reprendre bonne réponse, gagnant(s), partenaire et invitation à poursuivre le calendrier.

Aucune de ces sorties n’est publiée automatiquement par le plugin.

Avant la date/heure de révélation, le serveur ne doit pas envoyer la solution ou les données de résultat au navigateur.

## TRADUCTIONS

Le français est la source principale actuelle. Les traductions sont stockées sans multiplier toutes les colonnes du tableau principal.

Champs :

- `reference_id`
- `champ`
- `langue` — `en` ou `de`
- `texte`

Exemples de champs traduisibles :

- `titre_fr`
- `intro_partenaire_fr`
- `intro_question_fr`
- `lot_fr`
- `question_fr`
- `explication_reponse_fr`
- `description_fr`

## RÉIMPORT INTELLIGENT

Le mode par défaut doit fonctionner par identifiant stable :

- `campagne_id`
- `partenaire_id`
- `contenu_id`
- `resultat_id`

Avant écriture, afficher obligatoirement un rapport :

- créations ;
- modifications ;
- inchangés ;
- avertissements ;
- erreurs.

Aucune écriture silencieuse dès la sélection du fichier.

Éléments absents du nouvel import : conserver par défaut. Leur suppression doit nécessiter une confirmation explicite.

Les visuels WordPress manuels doivent être conservés si le nouvel import ne fournit pas de valeur de remplacement.

Un mode « remplacement complet » peut exister, mais doit demander une confirmation explicite. La suppression des médias doit être une option séparée et décochée par défaut.

## Séparation MDS / FDS

`parc_code` est obligatoire et ne peut valoir que :

- `mds`
- `fds`

L’import doit être refusé si le `parc_code` du fichier ne correspond pas à l’installation WordPress concernée.

Aucune campagne MDS ne doit pouvoir apparaître sur FDS, et inversement.

## Format de fichier envisagé

- principal : `.xlsx`
- CSV : optionnel si cela reste fiable pour les textes multilignes
- un modèle officiel doit être téléchargeable depuis WordPress
- le modèle doit intégrer `schema_version`
- une aide `?` doit documenter chaque colonne
- un bouton futur « Copier les instructions pour une IA » doit permettre de générer une consigne complète correspondant au schéma courant
