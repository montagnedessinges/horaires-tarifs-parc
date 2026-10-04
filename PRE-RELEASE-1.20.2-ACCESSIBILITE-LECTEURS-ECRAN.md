# Pré-release 1.20.2 — Accessibilité lecteurs d’écran

Date de cadrage : 04/10/2026
Base auditée : 1.20.0
Version cible : 1.20.2, après la future 1.20.1 IA/calendrier

## Objectif

Rendre les interfaces publiques de Gestion du parc réellement utilisables avec un lecteur d’écran et au clavier, sans créer de « mode aveugle » séparé. L’interface normale doit être accessible par défaut.

Référentiel cible : WCAG 2.2 niveau AA, conformément aux standards d’accessibilité WordPress pour le code nouveau ou modifié.

Lecteurs d’écran visés pour validation manuelle :
- NVDA + Firefox/Chrome sous Windows
- VoiceOver + Safari sous macOS et iOS
- TalkBack + Chrome sous Android
- JAWS en contrôle complémentaire si un environnement/licence est disponible

## Sources officielles consultées avant développement

- WordPress Accessibility Coding Standards : https://developer.wordpress.org/coding-standards/wordpress-coding-standards/accessibility/
- WCAG 2.2 : https://www.w3.org/TR/WCAG22/
- WAI-ARIA Authoring Practices Guide : https://www.w3.org/WAI/ARIA/apg/
- Grid Pattern : https://www.w3.org/WAI/ARIA/apg/patterns/grid/
- Tabs Pattern : https://www.w3.org/WAI/ARIA/apg/patterns/tabs/
- Date Picker Dialog Example : https://www.w3.org/WAI/ARIA/apg/patterns/dialog-modal/examples/datepicker-dialog/
- Keyboard Interface : https://www.w3.org/WAI/ARIA/apg/practices/keyboard-interface/

## Ce qui existe déjà et doit être conservé

Le code 1.20.0 possède déjà plusieurs bonnes bases :
- jours du calendrier rendus avec de vrais boutons ;
- `aria-label` sur les jours ;
- zone de détail du jour avec `aria-live="polite"` ;
- pictogrammes décoratifs masqués avec `aria-hidden="true"` ;
- dialogues publics avec `role="dialog"`, `aria-modal="true"`, bouton de fermeture nommé et piège de focus ;
- certains groupes d’onglets tarifaires ont déjà `role="tablist"`, `role="tab"`, `aria-selected`, `aria-controls` et gestion de focus.

La mise à jour doit améliorer ces bases sans casser les comportements souris/tactile existants.

## Lacunes confirmées dans le calendrier actuel

1. Le calendrier interactif n’est pas exposé comme une vraie structure de grille accessible.
2. Les jours sont tous des boutons normalement tabulables : un mois peut donc créer 28 à 31 arrêts Tab successifs.
3. Les flèches directionnelles ne permettent pas de naviguer de jour en jour/semaine en semaine.
4. Le jour courant est uniquement identifié visuellement par une classe CSS ; il n’est pas annoncé avec `aria-current="date"`.
5. Le jour sélectionné est identifié visuellement par une classe CSS ; son état n’est pas annoncé explicitement aux technologies d’assistance.
6. Les `aria-label` des jours annoncent seulement un suffixe générique « événement » lorsqu’un événement/période existe, sans donner le titre réel de l’événement ou de la période.
7. Les intitulés de jours de semaine sont des abréviations visuelles très courtes (`L M M J V S D`, etc.) et ne fournissent pas systématiquement un nom accessible complet.
8. Les boutons précédent/suivant sont nommés en français dans le HTML initial ; la langue accessible doit être correcte dès le rendu initial FR/EN/DE.
9. Les boutons de mois ont `role="tab"` mais le motif ARIA Tabs n’est pas complet : pas de roving tabindex cohérent, pas de navigation Left/Right/Home/End complète, et pas d’association explicite à un panneau de contenu du mois.
10. Les changements de mois/jour doivent être annoncés sans créer de bavardage excessif du lecteur d’écran.

## Architecture cible du calendrier

### Structure

- donner au calendrier un nom accessible clair ;
- utiliser une structure de grille interactive conforme au motif WAI-ARIA, ou une structure HTML native équivalente si elle est plus robuste ;
- exposer les en-têtes de colonnes lundi à dimanche avec noms accessibles complets ;
- conserver un seul point d’entrée Tab pertinent dans la grille grâce à un roving tabindex ;
- conserver les boutons de jours afin que l’activation reste naturelle avec Entrée/Espace.

### Navigation clavier

Dans la grille des jours :
- Flèche droite : jour suivant ;
- Flèche gauche : jour précédent ;
- Flèche bas : même colonne, semaine suivante ;
- Flèche haut : même colonne, semaine précédente ;
- Home : début de ligne/semaine ;
- End : fin de ligne/semaine ;
- éventuellement Page Up / Page Down pour changer de mois si cela reste cohérent avec le reste de l’interface ;
- Entrée/Espace : sélectionner le jour et afficher son détail.

Quand la navigation traverse un changement de mois, le nouveau mois doit être affiché et annoncé proprement.

### Noms et états annoncés

Pour chaque jour, le lecteur d’écran doit pouvoir entendre au minimum :
- date complète ;
- ouvert / fermé ;
- horaires utiles ;
- horaire ou fermeture exceptionnelle, le cas échéant ;
- nom réel de chaque événement public applicable ;
- nom réel de chaque période publique applicable ;
- jour férié si affiché ;
- état « aujourd’hui » via `aria-current="date"` ;
- état sélectionné avec un mécanisme ARIA cohérent avec la structure retenue.

Ne jamais faire prononcer les symboles décoratifs `!`, `×`, `★`, bandes de couleur ou icônes SVG lorsqu’une information textuelle équivalente existe.

### Changements dynamiques

- conserver `aria-live="polite"` pour le détail du jour ;
- éviter de placer toute la grille dans une live region ;
- annoncer le changement de mois par une zone dédiée courte ;
- ne pas répéter intégralement toutes les informations du calendrier après chaque déplacement de focus.

## Onglets mois / années / tarifs

Revoir tous les motifs `tablist/tab/tabpanel` de l’extension :
- un seul onglet actif dans la séquence Tab ;
- Left/Right pour les groupes horizontaux ;
- Home/End ;
- `aria-controls` et `aria-labelledby` cohérents ;
- focus conservé de façon prévisible après activation ;
- aucune différence de fonctionnement entre FR, EN et DE.

Si un composant n’est pas réellement un système d’onglets, préférer des boutons simples plutôt que d’utiliser `role="tab"` artificiellement.

## Autres interfaces publiques à auditer dans la même release

- bloc Aujourd’hui / statut d’ouverture ;
- tarifs et sélecteurs d’année ;
- infobulles / aides ;
- pop-up et alertes ;
- FAQ ;
- guides pédagogiques publics ;
- portail/devis groupe si affiché publiquement ;
- boutons de téléchargement PDF ;
- éléments ajoutés par la future 1.20.1 IA/calendrier.

## PDF

Ne pas considérer le PDF comme l’unique solution accessible pour les horaires. Le site HTML doit rester l’alternative accessible principale. Une éventuelle production de PDF balisé/tagué pourra faire l’objet d’un chantier séparé si nécessaire.

## Tests obligatoires avant release

### Automatisés

- conserver WordPress Plugin Check catégorie accessibilité ;
- ajouter un contrat de régression accessibilité dédié ;
- tester les noms/rôles/états ARIA attendus ;
- tester le roving tabindex ;
- tester la navigation clavier des jours et mois ;
- tester FR/EN/DE ;
- vérifier qu’aucun symbole décoratif ne devient le seul nom accessible ;
- vérifier qu’aucune ancienne fonctionnalité publique ne régresse.

### Manuels

Un test automatisé n’est pas suffisant. Avant publication, dérouler un scénario réel :
1. entrer dans le calendrier au clavier ;
2. identifier le mois et l’année ;
3. parcourir les jours aux flèches ;
4. entendre correctement ouvert/fermé et les horaires ;
5. identifier un événement et une période par leur vrai nom ;
6. sélectionner un jour et entendre le détail mis à jour ;
7. changer de mois ;
8. quitter le calendrier avec Tab sans devoir parcourir 31 boutons ;
9. parcourir les tarifs et pop-up ;
10. refaire le contrôle en FR, EN et DE.

## Relation avec 1.20.1 IA/calendrier

La future 1.20.1 doit privilégier un HTML sémantique côté serveur qui aide aussi les lecteurs d’écran. La 1.20.2 ne devra pas reconstruire une deuxième représentation parallèle du calendrier.

Principe : une seule source publique sémantique, utile à la fois aux visiteurs, lecteurs d’écran, moteurs de recherche et IA.

## Ce qu’il ne faut pas faire

- ne pas créer un bouton « mode aveugle » obligatoire ;
- ne pas afficher une version séparée et appauvrie du site aux utilisateurs de lecteurs d’écran ;
- ne pas cacher du texte uniquement pour tromper les robots ;
- ne pas supprimer l’interface visuelle existante ;
- ne pas surcharger l’interface de `aria-label` quand du texte visible/native HTML suffit ;
- ne pas annoncer chaque décoration ou changement mineur via `aria-live`.

## Critères de réussite

La release pourra être considérée prête si :
- WCAG 2.2 AA est la cible documentée et les critères concernés par l’extension sont couverts ;
- le calendrier est utilisable sans souris ;
- un lecteur d’écran peut comprendre la structure du calendrier et les états de chaque jour ;
- les événements/périodes sont annoncés par leur vrai nom ;
- la navigation ne nécessite pas 31 pressions sur Tab pour traverser un mois ;
- les changements dynamiques sont annoncés de façon utile et non envahissante ;
- FR/EN/DE sont cohérents ;
- tous les tests historiques et nouveaux sont verts ;
- une validation manuelle avec au moins NVDA et VoiceOver est documentée avant fusion sur `main`.

Aucune modification fonctionnelle n’est effectuée dans ce document de cadrage.