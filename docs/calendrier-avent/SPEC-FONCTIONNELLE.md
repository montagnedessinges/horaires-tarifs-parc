# Spécification fonctionnelle — Calendrier de l’Avent

## Objectif général

Créer un vrai calendrier de l’Avent interactif affiché par shortcode, et non un simple importeur.

Shortcode générique prévu :

`[parc_calendrier_avent]`

Le même moteur doit servir MDS et FDS avec des données de campagne séparées.

Le module n’a pas vocation à publier automatiquement sur Facebook ou Instagram. Il prépare les contenus dans l’administration WordPress afin qu’ils soient relus, copiés puis publiés manuellement par l’équipe du parc.

## Rendu public avant le calendrier

Avant `date_ouverture_calendrier` :

- afficher uniquement les teasings dont la date/heure d’ouverture est atteinte ;
- le nombre de teasings est libre ;
- les teasings peuvent être planifiés à des dates différentes ;
- les contenus futurs ne doivent pas être envoyés au navigateur avant leur ouverture si cela expose des informations sensibles ou non publiées.

## Rendu public du calendrier

Pendant la période du calendrier :

- grille de 24 cases ;
- jours futurs verrouillés ;
- jour courant ouvert automatiquement à sa date/heure ;
- jours passés toujours consultables ;
- clic sur un jour ouvert : afficher le contenu complet dans une modale ou un panneau sans changement de page ;
- navigation jour précédent / jour suivant dans la vue détaillée ;
- mobile prioritaire et navigation tactile utilisable.

Le détail d’un jour peut afficher selon les données disponibles :

- visuel ;
- `intro_partenaire_fr` ;
- partenaire ;
- lot quotidien ;
- `intro_question_fr` ;
- question ;
- réponses proposées ;
- pictogramme du grand jeu si applicable ;
- rappel du jeu ;
- liens vers la publication Facebook et/ou Instagram si renseignés.

## Jour final et grand jeu

Le dernier jour du calendrier doit conserver deux blocs distincts :

1. le jeu quotidien normal avec son propre `lot_fr` ;
2. la finale du mot mystère avec son propre `grand_jeu_lot_fr`.

La finale du mot mystère :

- commence par un champ de saisie du mot ;
- validation côté serveur ;
- mot correct : le formulaire final devient accessible ;
- mot faux : le formulaire final reste masqué ;
- la date et l’heure d’ouverture du grand jeu viennent des données de campagne ;
- la date et l’heure de fermeture sont configurables et peuvent être postérieures au 28 décembre ;
- aucune date ne doit être codée en dur.

## Indices du mot mystère

Certains jours peuvent comporter un pictogramme/loupe indiquant qu’un indice est caché dans le visuel.

Règles :

- les anciens jours restent consultables afin de retrouver les indices ;
- la présence d’un indice peut être visible ;
- `indice_lettre` et `indice_position` restent serveur et secrets jusqu’à la date de révélation ;
- après la date de révélation, le mode archive peut afficher les indices et reconstruire la solution à partir des données de campagne ;
- aucune solution annuelle ne doit être codée dans le plugin.

## Jeux quotidiens

Formats admis :

- QCM ;
- vrai/faux ;
- choix multiples.

Pas de réponse libre pour les jeux quotidiens.

Les bonnes réponses doivent être validées côté serveur. Le navigateur ne reçoit pas la valeur canonique de la réponse avant la date de révélation.

`explication_reponse_fr` est facultatif. Son rôle principal est d’expliquer, dans le commentaire de résultat et éventuellement dans l’archive, pourquoi la réponse est correcte ou d’apporter une précision utile sur le comportement observé, les Magots, une activité ou un élément du parc. Si ce champ est vide, le commentaire de résultat reste court et passe directement de la bonne réponse aux gagnants.

## Mode archive

Après Noël et selon les dates configurées :

- les jours restent accessibles ;
- les résultats peuvent apparaître lorsque leur date/heure de révélation est atteinte ;
- les indices et le mot mystère peuvent apparaître uniquement après la date de révélation prévue ;
- le formulaire final est fermé après la date/heure de fermeture du grand jeu ;
- l’archive reste pilotée par les données de campagne.

## Administration — navigation

Ne pas afficher 24 formulaires complets les uns sous les autres.

Prévoir un tableau de bord compact avec vues/onglets :

- Campagne ;
- Teasings ;
- Calendrier ;
- Grand jeu ;
- Partenaires ;
- Résultats ;
- Import / export.

### Vue Calendrier

- grille 24 jours cliquables ;
- chaque case affiche au minimum numéro, date, statut, partenaire éventuel, visuel présent/manquant et indice oui/non ;
- clic sur une case : ouvrir uniquement l’éditeur du jour sélectionné ;
- retour rapide à la grille ;
- filtres utiles : brouillon, prêt, visuel manquant, partenaire, indice.

### Vue Teasings

- liste/cartes chronologiques séparées des 24 jours ;
- nombre libre ;
- chaque teasing cliquable et éditable.

### Création d’une campagne

Prévoir une action de création/initialisation qui crée la structure des 24 jours d’une campagne vide, tout en permettant qu’un import complète immédiatement les données.

## Horaires et planning

Le fonctionnement courant doit rester simple : une date et une heure principales pour chaque contenu.

Par défaut :

- `date_publication` + `heure_publication` servent de référence de planning ;
- cette même date/heure pilote l’ouverture du contenu sur le site ;
- elle sert aussi de repère pour la publication manuelle sur les réseaux sociaux ;
- l’extension ne déclenche aucune publication automatique.

Des horaires spécifiques par réseau ou un horaire d’ouverture distinct peuvent rester possibles uniquement comme options avancées et facultatives. Ils ne doivent pas alourdir l’interface standard ni être exigés pour préparer une journée normale.

## Aperçu admin

Le module Avent doit utiliser le moteur d’aperçu commun du plugin.

Règle validée :

- réutiliser le contrôle global `Date à tester` ;
- ajouter/tenir compte de `Heure à tester` ;
- l’aperçu ne doit pas ouvrir l’éditeur interne du jour ;
- il doit afficher le **vrai rendu du shortcode** tel qu’un visiteur le verrait à la date/heure simulée.

Exemples :

- avant ouverture : teasings correspondant à la date simulée ;
- 5 décembre après l’heure d’ouverture : jours 1 à 5 accessibles ;
- 24 décembre avant l’heure du jour : jour final encore verrouillé si sa configuration l’exige ;
- 24 décembre après l’heure prévue : jour final ouvert et finale affichée seulement si sa propre ouverture est atteinte ;
- après clôture/révélation : mode archive selon les données.

La simulation admin ne modifie jamais la date réelle du site public.

## Réseaux sociaux — préparation avant publication

L’extension doit générer automatiquement des descriptions Facebook et Instagram à partir des données structurées.

Éléments possibles :

- partenaire ;
- lot ;
- `intro_partenaire_fr` ;
- `intro_question_fr` ;
- question ;
- réponses ;
- rappel du jeu quotidien ;
- mention du grand jeu si applicable ;
- hashtags ;
- liens.

Le texte doit rester court, naturel et adapté aux réseaux sociaux.

Dans l’administration :

- zone d’aperçu du texte généré avant publication ;
- bouton `Copier le texte` ;
- possibilité de prévisualiser/copier séparément Facebook et Instagram si leurs textes divergent ;
- `texte_post_override_fr` reste disponible pour remplacer totalement le générateur sur un contenu particulier ;
- aucune publication automatique vers Meta n’est prévue : l’équipe copie le texte puis le publie manuellement.

## Réseaux sociaux — résultat après tirage

Une fois la bonne réponse et les gagnants renseignés, l’administration doit également générer les textes de résultat prêts à copier-coller.

Sorties attendues :

- commentaire de résultat Facebook ;
- commentaire de résultat Instagram ;
- texte court de Story résultat ;
- bouton `Copier` pour chaque sortie ;
- aperçu éditable avant copie ;
- possibilité d’un override manuel exceptionnel.

Le commentaire de résultat doit pouvoir être composé automatiquement, selon les données disponibles, dans cet ordre logique :

1. bonne réponse ;
2. `explication_reponse_fr` si elle est renseignée ;
3. gagnant(s) du réseau concerné ;
4. remerciement au partenaire du jour ;
5. rappel éventuel des conditions liées au partenaire ;
6. relance vers la suite du calendrier.

La relance doit s’adapter automatiquement au planning :

- si la nouvelle question est déjà ouverte, proposer une formulation du type « La nouvelle question du jour est déjà en ligne, vous pouvez participer dès maintenant ! » ;
- si elle n’est pas encore ouverte, proposer une formulation du type « Rendez-vous demain à [heure] pour la prochaine question ! » ;
- pour le dernier résultat, ne pas annoncer une prochaine question inexistante ; utiliser un texte de clôture adapté à la campagne.

Le texte Story doit être plus court. Il peut reprendre la bonne réponse, le ou les gagnants, le partenaire et une courte invitation à poursuivre le calendrier. S’il y a un gagnant Facebook et un gagnant Instagram différents, le générateur doit pouvoir les distinguer clairement.

La publication du commentaire et de la Story reste entièrement manuelle.

## Langues

Architecture du site : FR / EN / DE.

Orientation actuelle :

- réseaux sociaux principalement en français ;
- français obligatoire dans la campagne ;
- EN/DE facultatifs via le bloc TRADUCTIONS ;
- comportement de fallback encore configurable et à finaliser avant développement.

## Sécurité

Données à ne jamais exposer au navigateur avant révélation :

- `mot_mystere`
- `bonne_reponse_code`
- `bonne_reponse_texte_fr`
- `indice_lettre`
- `indice_position`

Principes :

- validation serveur des réponses et du mot mystère ;
- nonce/capacités pour actions admin ;
- sanitation à l’import ;
- aucune confiance dans un champ caché ou une valeur JS pour les réponses ;
- aucune donnée sensible dans `wp_localize_script`, attributs `data-*` ou JSON public avant autorisation ;
- isolation stricte des données `mds` et `fds`.

## Visuels

- visuel facultatif lors de l’import ;
- URL externe ou média WordPress ;
- modification manuelle possible après import ;
- placeholder au bon ratio lorsqu’un visuel manque dans l’aperçu/admin ;
- un réimport texte ne supprime jamais un visuel manuel si aucune nouvelle image n’est fournie.

Attention : une URL de média déjà publique dans la médiathèque WordPress peut rester accessible directement. Ne pas promettre une confidentialité absolue d’une image si elle a déjà une URL publique connue ; la protection stricte porte au minimum sur les données sensibles textuelles et les payloads envoyés par le module.
