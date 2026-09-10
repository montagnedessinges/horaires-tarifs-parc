# Handoff — lancement du développement Calendrier de l’Avent

Ce fichier est destiné au chat qui va réellement développer le module.

## À lire avant de coder

Lire intégralement :

1. `README.md`
2. `SPEC-FONCTIONNELLE.md`
3. `REFERENTIEL-IMPORT.md`
4. `PLAN-DEVELOPPEMENT.md`
5. `POINTS-A-DEBATTRE.md`

Le référentiel d’import courant est **0.7** avec `schema_version = 3`.

Les feuilles `IMPORT plugin - référentiel` des deux fichiers Google Sheets 2026 — Montagne des Singes et Forêt des Singes — ont été resynchronisées sur ce cadrage le **9 septembre 2026**. Ne pas créer de nouveau fichier ou de nouvelle feuille de référence en parallèle.

## Règles à ne pas réinventer

- Le shortcode principal est `[parc_calendrier_avent]`.
- Le règlement dynamique utilise `[parc_reglement_avent]`.
- Les shortcodes rendent uniquement des blocs dans des pages WordPress existantes : ne pas créer de page complète, de H1 global, de header/footer ou de CSS global.
- Aucune URL propre à MDS/FDS n’est codée en dur.
- Avant le 1er décembre : un seul visuel teasing public et 24 cases fermées.
- Les multiples teasings sont surtout destinés aux réseaux sociaux.
- Quand le calendrier est ouvert, aucune case ne s’ouvre automatiquement à l’arrivée sur la page.
- Le jour courant devient cliquable et peut être mis en avant visuellement, mais le visiteur doit cliquer pour ouvrir son contenu.
- Le visuel 4:5 est central et doit pouvoir être agrandi.
- Jour J : afficher visuel + partenaire + lot + question + réponses proposées, sans solution ni gagnants.
- Résultat : révélable à J+1 à l’heure configurée, pas le jour J.
- Si l’heure de révélation est passée mais que le résultat n’est pas publié : afficher le texte configurable de tirage non effectué.
- Les gagnants Facebook et Instagram sont saisis séparément et deviennent visibles sur la journée après publication explicite du résultat.
- Un jour avec indice possède une case admin dédiée ; elle révèle les champs lettre + position.
- Lettre + numéro restent serveur pendant le jeu et sont révélés avec le résultat du jour.
- Le générateur social ajoute automatiquement le rappel du mot mystère pour les jours avec indice.
- Toutes les publications quotidiennes générées renvoient vers `page_calendrier_url`.
- `Comment participer ?` ouvre une explication courte dans le bloc ; le règlement complet est sur la page `page_reglement_url`.
- **Prochaine mise à jour à intégrer : le contenu `Comment participer ?` doit être présenté en deux colonnes distinctes sur écran large : `Jeu quotidien` et `Mystère de Noël`. Sur mobile, les deux blocs s’empilent verticalement.**
- **Colonne `Jeu quotidien` : expliquer que la participation se fait sur Facebook ou Instagram, rappeler la bonne réponse en commentaire, la mention d’une personne avec qui venir et l’abonnement au parc ainsi qu’au partenaire du jour lorsqu’il y en a un. Ajouter des boutons `Participer sur Facebook` et `Participer sur Instagram`.**
- **Les boutons sociaux doivent pointer en priorité vers les URL de la publication du jour si elles sont renseignées ; sinon vers les comptes officiels du parc configurés dans la campagne.**
- **Colonne `Mystère de Noël` : expliquer la mécanique des indices lettre + chiffre, le chiffre donnant l’ordre de la lettre dans le mot, la conservation des indices puis la saisie du mot le 24 décembre pour accéder au formulaire du grand lot.**
- **Sous les deux colonnes : un bouton commun `Consulter le règlement complet`.**
- Tous les textes publics/socials ont des valeurs par défaut mais restent modifiables.
- Le 24 comporte le jeu quotidien normal ET la finale du mot mystère.
- Le mot final est validé exclusivement côté serveur.
- `grand_jeu_formulaire_shortcode` ne doit pas être envoyé ni exécuté avant validation correcte du mot.
- Le formulaire final ne doit jamais être préchargé puis simplement caché en CSS/JS.
- Prévoir une autorisation temporaire signée après mot correct et une limitation raisonnable des tentatives.
- Le plugin ne publie jamais automatiquement sur Facebook ou Instagram.

## Habillage visuel validé pour la prochaine mise à jour

Le prototype actuel est jugé globalement propre : ne pas refaire entièrement la structure ni déplacer le visuel 4:5. L’objectif de la prochaine mise à jour est surtout de **l’habiller avec davantage de couleur**, sans surcharger la page.

À intégrer :

- conserver la disposition actuelle visuel 4:5 à gauche / contenu à droite sur écran large ;
- conserver la grille simple des 24 jours ;
- utiliser des couleurs d’accent pour mieux distinguer `Partenaire`, `Lot du jour`, `Question du jour`, `Résultat` et `Mystère de Noël` ;
- prévoir des états de couleur clairement distincts pour les cases : fermée, ouverte, jour courant, résultat disponible et éventuellement jour avec indice ;
- rendre le bloc `Mystère de Noël` plus identifiable visuellement avec un fond ou accent léger ;
- rendre le bloc `Résultat` plus identifiable sans modifier profondément la mise en page ;
- distinguer visuellement les gagnants Facebook et Instagram avec des badges ou repères simples ;
- afficher l’indice révélé dans un encadré identifiable comme `Indice du Mystère de Noël` ;
- conserver des fonds majoritairement transparents ou légers et hériter autant que possible des couleurs du thème, conformément aux règles générales du plugin ;
- éviter les gros contours noirs qui donnent un effet technique/focus ; privilégier une mise en avant plus douce et cohérente avec le thème du site ;
- ne pas imposer une palette figée commune aux deux parcs : les couleurs doivent pouvoir s’adapter au thème ou être configurables.

### Partenaires cliquables

Le partenaire affiché dans le détail d’un jour doit être cliquable lorsqu’un lien est disponible.

Priorité de destination recommandée :

1. Instagram du partenaire si renseigné ;
2. sinon Facebook du partenaire si renseigné ;
3. sinon site web du partenaire si renseigné.

Le lien partenaire s’ouvre dans un nouvel onglet afin de conserver la page Calendrier de l’Avent ouverte. Le nom peut être accompagné d’un bouton ou pictogramme discret `Voir le partenaire`, notamment sur mobile.

## Première cible de développement

Produire un premier prototype installable permettant déjà de tester :

- administration d’une campagne ;
- grille 24 jours ;
- édition d’un jour ;
- visuel 4:5 ;
- ouverture des cases par date/heure ;
- aperçu avec date + heure simulées ;
- résultat J+1 ;
- gagnants ;
- indice ;
- `Comment participer ?` ;
- règlement dynamique ;
- finale sécurisée avec shortcode de formulaire ;
- générateur de textes à copier ;
- import d’une campagne de test selon le schéma 3.

## Test attendu avec l’utilisateur

Après ce premier prototype, utiliser une campagne fictive complète (CSV/XLSX de démonstration) couvrant plusieurs cas différents.

Le but est de permettre à l’utilisateur de tester visuellement le rendu à des dates simulées : avant ouverture, jour 1, jour 2 avec résultat, jour avec indice, jour sans tirage publié, jour 24 et archive.

Ne pas attendre que toutes les vraies données 2026 soient finalisées pour fournir ce prototype.

## Version / release

Ne pas annoncer le module comme livré tant que :

- le code n’est pas réellement présent ;
- les tests sont passés ;
- une nouvelle version est publiée ;
- le ZIP de production est disponible et vérifié.