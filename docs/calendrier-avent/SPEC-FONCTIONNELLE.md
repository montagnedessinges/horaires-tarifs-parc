# Spécification fonctionnelle — Calendrier de l’Avent

## Objectif général

Créer un vrai calendrier de l’Avent interactif affiché par shortcode, réutilisable chaque année sur les deux sites :

- La Montagne des Singes (`mds`) ;
- La Forêt des Singes (`fds`).

Shortcodes prévus :

- `[parc_calendrier_avent]` : bloc interactif du calendrier ;
- `[parc_reglement_avent]` : bloc du règlement complet de la campagne active.

### Règle d’intégration WordPress

Les shortcodes **ne créent jamais une page WordPress complète**.

Ils rendent uniquement un bloc à l’endroit exact où ils sont insérés dans une page ou un article déjà créé par l’équipe du parc.

En particulier :

- ne pas créer de `H1` de page imposé par le plugin ;
- ne pas recréer l’en-tête, le pied de page ou la structure globale du thème ;
- ne pas appliquer de CSS global susceptible de casser le thème ;
- utiliser des styles strictement scoped au module ;
- laisser à WordPress et au thème la responsabilité de la page elle-même.

Le module n’a pas vocation à publier automatiquement sur Facebook ou Instagram. Il prépare les contenus dans l’administration afin qu’ils soient relus, copiés puis publiés manuellement par l’équipe.

## Principe éditorial de la page calendrier

La page Calendrier de l’Avent est le point central vers lequel renvoient les publications sociales.

Le bloc `[parc_calendrier_avent]` affiche, dans cet ordre logique :

1. un titre interne au bloc, par exemple « Calendrier de l’Avent 2026 — du 1er au 24 décembre » ;
2. une courte présentation expliquant l’objectif : des entrées/lots à gagner chaque jour, offerts par le parc ou ses partenaires, ainsi qu’un grand jeu final ;
3. un bouton `Comment participer ?` ;
4. le calendrier interactif de 24 cases ;
5. une zone de contenu qui affiche soit le visuel teasing avant ouverture, soit le détail du jour sélectionné.

Tous les textes publics doivent avoir une valeur par défaut utile mais rester modifiables dans l’administration. Aucun texte de campagne ne doit être codé en dur dans le plugin.

## Comment participer ? et règlement

Le bouton `Comment participer ?` ouvre dans le même bloc une explication courte et simple du fonctionnement du calendrier :

- un jeu quotidien ;
- des lots à gagner ;
- participation sur les réseaux selon les conditions de la campagne ;
- certains jours comportent un indice caché dans le visuel ;
- ces indices permettent de reconstituer le mot mystère ;
- le dernier jour, le mot correct déverrouille la participation au grand jeu final.

Cette explication courte doit rester modifiable.

À la fin de cette explication, un bouton `Consulter le règlement complet` renvoie vers une URL configurable propre au site.

La page de règlement peut être une page WordPress permanente contenant `[parc_reglement_avent]`. Le shortcode de règlement affiche le règlement complet de la campagne active. Le contenu du règlement est modifiable et archivable par campagne/année.

Aucune URL MDS ou FDS ne doit être codée en dur : l’URL de la page calendrier et l’URL de la page règlement sont configurables par installation/campagne.

## Affichage avant le 1er décembre

Le site n’a pas besoin de reproduire les multiples teasings programmés pour les réseaux sociaux.

Avant l’ouverture du premier jour :

- l’introduction du calendrier reste visible ;
- les 24 cases peuvent être visibles mais fermées/verrouillées ;
- aucune case ne s’ouvre ;
- la zone de contenu affiche un **seul visuel teasing public** configuré pour la campagne ;
- le bouton `Comment participer ?` et l’accès au règlement restent disponibles.

Les multiples teasings datés servent principalement au générateur de publications sociales et restent gérés séparément dans l’administration.

## Grille publique des 24 jours

Pendant la période du calendrier :

- grille de 24 cases ;
- jours futurs verrouillés ;
- jours dont la date/heure d’ouverture est atteinte cliquables ;
- jours passés toujours consultables ;
- le jour courant peut être visuellement mis en avant avec un état/couleur spécifique ;
- **le jour courant ne s’ouvre pas automatiquement** ;
- le visiteur choisit volontairement la case qu’il souhaite ouvrir.

Le clic sur une case ouverte affiche le détail du jour dans le même bloc, sans changer de page. Le rendu peut être un panneau/zone de détail responsive ; l’important est que le visiteur reste sur la page calendrier et puisse revenir facilement à la grille.

## Détail d’un jour — rôle central du visuel

Le visuel du jour est un élément central du jeu, et non une simple illustration.

Format de référence : **4:5 vertical**, compatible avec les créations Instagram.

Il doit être affiché suffisamment grand pour permettre :

- une question d’observation ;
- l’identification d’un comportement ;
- la recherche d’un détail ;
- la recherche de la lettre + du numéro du mot mystère.

Sur mobile, le visuel prend la largeur utile disponible. Sur écran plus large, il peut être associé au texte dans une composition adaptée, sans devenir secondaire.

Le visiteur doit pouvoir agrandir le visuel si nécessaire.

Le texte alternatif ou les métadonnées publiques du visuel ne doivent jamais révéler un indice caché ni la bonne réponse.

Le détail du jour affiche selon les données disponibles :

- visuel ;
- partenaire ;
- lot ;
- courte introduction partenaire éventuelle ;
- courte introduction de la question éventuelle ;
- question ;
- réponses proposées ;
- pictogramme/loupe du grand jeu si applicable ;
- rappel du grand jeu si applicable ;
- liens vers les publications Facebook et/ou Instagram si ces liens ont été renseignés après publication.

Le site sert de page centrale et d’archive. La participation quotidienne reste effectuée sur les réseaux sociaux selon les règles de la campagne ; le module public n’a pas à enregistrer les réponses quotidiennes sur le site.

## Ouverture quotidienne et révélation à J+1

Principe de référence 2026 : ouverture quotidienne à l’heure configurée, par exemple 9 h.

Exemple :

- 1er décembre à 9 h : le jour 1 devient cliquable et affiche uniquement le jeu du jour ;
- 2 décembre à 9 h : le jour 2 devient cliquable et le jour 1 peut désormais afficher son résultat ;
- 3 décembre à 9 h : même logique pour le jour 2, etc.

La bonne réponse, l’explication, les gagnants et l’indice éventuel ne doivent **jamais** être visibles le jour même du jeu.

La date/heure de révélation du résultat correspond par défaut à l’ouverture du jour suivant, tout en restant configurable.

Même si l’équipe saisit le résultat à l’avance dans WordPress, le serveur le garde caché avant la date/heure de révélation.

## Résultats quotidiens et gagnants

Chaque jour peut avoir un résultat distinct de son contenu principal.

Après la date/heure de révélation et uniquement lorsque le résultat est publié :

- afficher la bonne réponse ;
- afficher `explication_reponse_fr` si elle existe ;
- afficher le gagnant Facebook s’il existe ;
- afficher le gagnant Instagram s’il existe ;
- afficher l’indice du mot mystère si ce jour en comportait un.

Si l’heure de révélation est atteinte mais que le tirage n’a pas encore été publié, afficher un texte configurable du type : `Le tirage au sort n’a pas encore été effectué.`

La saisie de noms de gagnants ne doit pas rendre automatiquement le résultat public. Prévoir un état/action explicite `Publier le résultat`.

Les gagnants restent ensuite visibles dans l’archive de chaque journée afin que les visiteurs puissent retrouver les résultats précédents.

## Indices du mot mystère

Dans l’éditeur d’un jour, prévoir une case simple :

`Ce jour contient un indice du mot mystère`.

Quand elle est cochée :

- afficher les champs `indice_lettre` et `indice_position` dans l’administration ;
- afficher le pictogramme/loupe sur le rendu du jour ;
- ajouter par défaut le rappel du grand jeu au texte social généré ;
- permettre de remplacer le rappel général par un texte spécifique à ce jour ;
- ne jamais écrire la lettre ni le numéro réel dans le texte social avant révélation ;
- garder la lettre + position côté serveur pendant le jeu.

Exemple interne : `H` + `6` représente `H6`.

La lettre et son numéro sont physiquement cachés dans le visuel préparé par l’équipe.

### Révélation de l’indice

L’indice du jour devient publiquement visible dans l’archive **avec le résultat du jour**, donc au plus tôt à J+1 à l’heure de révélation et seulement si le résultat a été publié.

Exemple après tirage : `Indice du jour : H6`.

Cette révélation permet aux visiteurs de revenir sur une ancienne journée, de revoir le visuel, de retrouver où l’indice était caché et de vérifier leur collecte.

Le mot mystère complet reste une donnée distincte et peut avoir sa propre date de révélation finale après le grand jeu.

## Rappel du mot mystère dans les publications sociales

Le générateur de publication Facebook/Instagram doit détecter automatiquement les jours avec indice.

Pour ces jours, il ajoute un rappel configurable expliquant que :

- un indice se cache dans le visuel ;
- il faut repérer la lettre et son numéro ;
- les indices permettront de reconstituer le mot mystère ;
- le mot pourra être utilisé lors de la finale.

Le texte général est modifiable au niveau de la campagne et un override peut exister par jour.

Toutes les publications quotidiennes générées doivent également intégrer l’URL configurable de la page centrale du Calendrier de l’Avent, avec une formulation modifiable, afin de permettre aux visiteurs de retrouver le calendrier, les anciens jours, l’explication et le règlement.

## Jeux quotidiens

Formats admis :

- QCM ;
- vrai/faux ;
- choix multiples.

Pas de réponse libre pour les jeux quotidiens.

Le visuel peut être nécessaire pour répondre à la question.

Les bonnes réponses sont des données serveur sensibles et ne doivent pas être exposées avant révélation.

`explication_reponse_fr` est facultatif. Il sert principalement à expliquer pourquoi la réponse est correcte ou à apporter une précision utile dans le commentaire de résultat et dans l’archive.

## Jour 24 et grand jeu final

Le dernier jour conserve deux mécaniques distinctes :

1. le jeu quotidien normal du 24 décembre, avec son propre lot ;
2. la finale du mot mystère, avec son grand lot distinct.

Lorsque la date/heure de la finale est atteinte, le bloc affiche un champ permettant de saisir le mot mystère.

Le visiteur ne voit le formulaire final qu’après validation correcte du mot.

## Formulaire final par shortcode

Dans l’administration de la campagne, prévoir un champ permettant à l’équipe de renseigner le shortcode du formulaire final, par exemple un shortcode Contact Form 7 ou équivalent.

Ce shortcode :

- est indépendant du mot mystère ;
- n’est pas exécuté au chargement initial de la page ;
- n’est pas envoyé dans le HTML/JavaScript avant validation ;
- n’est rendu par le serveur qu’après validation correcte du mot et autorisation serveur.

Le formulaire final ne doit pas être simplement présent dans le DOM avec `display:none`.

## Sécurité du mot mystère

Le mot mystère ne doit jamais être disponible avant autorisation dans :

- HTML ;
- JavaScript ;
- attribut `data-*` ;
- JSON public ;
- `wp_localize_script` ;
- champ caché ;
- commentaire de code public ;
- réponse réseau non autorisée.

Validation obligatoire côté serveur.

Après un mot correct, le serveur peut délivrer une autorisation temporaire signée permettant de rendre le formulaire sans obliger le visiteur à retaper immédiatement le mot après un simple rechargement.

Prévoir une limitation raisonnable des tentatives afin d’éviter le brute force du mot mystère.

Avant la date/heure d’ouverture du grand jeu, le formulaire, son shortcode et les données nécessaires à sa génération ne doivent pas être exposés au navigateur.

## Page règlement dynamique

`[parc_reglement_avent]` affiche le règlement complet de la campagne active du parc.

Règles :

- règlement stocké par campagne/année ;
- contenu entièrement modifiable dans WordPress ;
- URL de la page qui contient le shortcode configurable ;
- aucune URL imposée par le plugin ;
- MDS et FDS peuvent utiliser des slugs/URL différents ;
- l’année suivante, une nouvelle campagne peut alimenter la même page permanente sans modifier le shortcode.

## Mode archive

Après Noël et selon les dates configurées :

- les 24 jours restent accessibles ;
- les réponses et gagnants publiés restent visibles ;
- les indices révélés restent visibles sur leurs journées ;
- le formulaire du grand jeu est fermé après sa date/heure de fermeture ;
- le mot mystère complet peut être révélé après sa date de révélation finale configurée ;
- aucune date n’est codée en dur.

## Administration — navigation

Ne pas afficher 24 formulaires complets les uns sous les autres.

Prévoir un tableau de bord compact avec vues/onglets :

- Campagne ;
- Teasings sociaux ;
- Calendrier ;
- Grand jeu ;
- Partenaires ;
- Résultats ;
- Import / export.

### Vue Campagne

Doit notamment permettre de modifier :

- dates/heures ;
- titre et texte d’introduction du bloc public ;
- texte `Comment participer ?` ;
- libellés des boutons ;
- URL de la page calendrier ;
- URL de la page règlement ;
- règlement complet ;
- visuel teasing public ;
- textes de rappel du grand jeu ;
- textes d’état public, dont `tirage non effectué` ;
- textes de la finale ;
- shortcode du formulaire final.

Tous les textes publics doivent avoir des valeurs par défaut éditables.

### Vue Calendrier

- grille 24 jours cliquables ;
- numéro, date, statut, partenaire, visuel présent/manquant, indice oui/non ;
- clic sur une case : ouvrir uniquement l’éditeur du jour sélectionné ;
- case `Ce jour contient un indice du mot mystère` ;
- si cochée : révéler dans l’admin les champs lettre + position + rappel social ;
- filtres utiles : brouillon, prêt, visuel manquant, partenaire, indice.

### Vue Teasings sociaux

- liste/cartes chronologiques séparées des 24 jours ;
- nombre libre ;
- servent principalement à préparer les publications sociales avant le lancement ;
- ne commandent pas l’affichage du teasing public unique de la page calendrier.

### Vue Résultats

Pour chaque jour :

- bonne réponse ;
- explication éventuelle ;
- gagnant Facebook ;
- gagnant Instagram ;
- aperçu du résultat public ;
- commentaire résultat Facebook généré ;
- commentaire résultat Instagram généré ;
- Story résultat générée ;
- bouton `Publier le résultat` distinct de la simple saisie ;
- boutons de copie pour les réseaux.

## Horaires et planning

Le fonctionnement courant doit rester simple : une date et une heure principales pour chaque contenu.

Par défaut :

- `date_publication` + `heure_publication` pilotent l’ouverture de la case ;
- elles servent aussi de repère pour la publication manuelle sur les réseaux ;
- l’extension ne déclenche aucune publication automatique.

La révélation du résultat est distincte : par défaut, elle correspond à l’ouverture du jour suivant.

Des overrides avancés peuvent exister, mais ne doivent pas alourdir l’interface standard.

## Aperçu admin

Le module Avent doit utiliser le moteur d’aperçu commun du plugin.

Règle :

- réutiliser `Date à tester` ;
- tenir compte de `Heure à tester` ;
- afficher le vrai rendu du shortcode tel qu’un visiteur le verrait ;
- ne jamais modifier la date réelle du site public.

Scénarios indispensables :

- avant le 1er décembre : 24 cases fermées + teasing public ;
- 1er décembre après ouverture : seule la case 1 est accessible, aucune case ouverte automatiquement ;
- 2 décembre après ouverture : cases 1 et 2 accessibles ; jour 1 affiche son résultat seulement s’il est publié ;
- jour avec indice : lettre/position invisibles pendant le jeu puis visibles après révélation + publication du résultat ;
- 24 décembre : jeu quotidien + finale ;
- mot faux : pas de formulaire ;
- mot correct : formulaire rendu côté serveur ;
- après clôture : archive et formulaire final fermé.

## Réseaux sociaux — préparation avant publication

Le générateur doit proposer un aperçu et un bouton `Copier le texte` pour Facebook et Instagram.

Le texte peut utiliser :

- partenaire ;
- lot ;
- introductions ;
- question ;
- réponses ;
- règles quotidiennes ;
- rappel du grand jeu si `indice_actif = oui` ;
- URL de la page calendrier sur toutes les publications quotidiennes ;
- hashtags.

Tous les textes automatiques doivent être modifiables via réglages et/ou override du contenu.

Aucune publication automatique vers Meta.

## Réseaux sociaux — résultat après tirage

Après saisie des résultats, l’administration génère :

- commentaire résultat Facebook ;
- commentaire résultat Instagram ;
- texte court de Story ;
- bouton `Copier` pour chaque sortie.

Ordre logique du commentaire :

1. bonne réponse ;
2. explication si renseignée ;
3. gagnant du réseau concerné ;
4. remerciement partenaire ;
5. éventuelles conditions utiles ;
6. relance vers la suite du calendrier.

La relance s’adapte au planning : prochaine question déjà ouverte, rendez-vous à venir ou clôture finale.

Aucune publication automatique.

## Langues

Architecture du site : FR / EN / DE.

- français obligatoire ;
- EN/DE facultatifs ;
- réseaux sociaux principalement en français ;
- comportement de fallback à finaliser avant release si nécessaire.

## Sécurité — synthèse

Données à ne jamais exposer prématurément :

- `mot_mystere` ;
- `bonne_reponse_code` ;
- `bonne_reponse_texte_fr` ;
- `indice_lettre` ;
- `indice_position` ;
- shortcode/configuration du formulaire final avant autorisation.

Principes :

- validation serveur ;
- nonce/capacités pour actions admin ;
- sanitation à l’import ;
- limitation des tentatives sur le mot final ;
- aucune confiance dans le client ;
- isolation stricte `mds` / `fds`.

## Visuels

- format public prioritaire 4:5 ;
- URL externe ou média WordPress ;
- remplacement manuel possible ;
- réimport vide = conservation du média manuel ;
- placeholder 4:5 dans l’admin si image manquante ;
- possibilité d’agrandir le visuel côté public ;
- ne jamais révéler un indice par l’alt, le nom public ou une donnée injectée par le module.
