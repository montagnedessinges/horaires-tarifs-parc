# Mise à jour 1.20.1 — indexation Google, moteurs et IA

Date de cadrage et développement : 04/10/2026.

## Objectif

La version 1.20.1 ne cherche pas à créer un « mode IA ». Elle rend les informations publiques générées par Gestion du parc compréhensibles directement dans le HTML initial afin qu’elles soient plus facilement explorables et indexables par Google, les autres moteurs de recherche et les assistants IA.

Le problème identifié en 1.20.0 est principalement architectural : le calendrier visuel contient les données dans `window.ParcsHTPData`, puis JavaScript construit les mois, les jours et les marqueurs. Un crawler qui ne dépend pas du rendu JavaScript peut donc voir la structure du calendrier et ses symboles sans disposer d’une représentation textuelle suffisamment explicite des périodes et événements.

## Sources officielles consultées avant développement

- Google Search Central — JavaScript SEO : https://developers.google.com/search/docs/crawling-indexing/javascript/javascript-seo-basics
- Google Search Central — Dynamic rendering : https://developers.google.com/search/docs/crawling-indexing/javascript/dynamic-rendering
- Google Search Central — politiques relatives aux données structurées : https://developers.google.com/search/docs/appearance/structured-data/sd-policies
- Google Search Central — données structurées Event : https://developers.google.com/search/docs/appearance/structured-data/event
- Schema.org — OpeningHoursSpecification : https://schema.org/OpeningHoursSpecification
- Schema.org — specialOpeningHoursSpecification : https://schema.org/specialOpeningHoursSpecification
- OpenAI — ChatGPT Search / OAI-SearchBot : https://help.openai.com/fr-fr/articles/9237897-recherche-chatgpt

## Architecture retenue

### Une seule source de vérité

Aucune donnée n’est ressaisie pour Google ou les IA. Le renderer `Parcs_HT_Calendar_Semantic` lit directement `Parcs_HT_Defaults::all_settings()` et les saisons canoniques déjà utilisées par le calendrier.

Il ne contient aucun `update_option()` et ne possède aucun stockage propre.

### Rendu côté serveur

Les shortcodes Calendrier et Horaires & Tarifs reçoivent après leur rendu visuel un accordéon HTML public et discret contenant, lorsque les éléments sont publiés :

- dates de saison ;
- horaires habituels ;
- horaires exceptionnels ;
- fermetures exceptionnelles ;
- événements ;
- périodes repères ;
- jours fériés lorsqu’ils sont affichés publiquement.

Les dates utilisent l’élément HTML `<time datetime="YYYY-MM-DD">`.

Le contenu existe dans la réponse HTML WordPress avant JavaScript. Le moteur interactif actuel reste inchangé et devient une amélioration progressive de cette information.

### Pas de contenu réservé aux robots

La couche n’utilise ni `display:none`, ni `visibility:hidden`, ni détection de crawler. L’information est réellement accessible aux visiteurs dans un `<details>` qu’ils peuvent ouvrir.

Cette règle est également protégée par un test de régression.

### Pas de faux schéma Event

La page calendrier contient potentiellement plusieurs événements et périodes. La 1.20.1 ne génère donc pas automatiquement de JSON-LD `Event` pour ces marqueurs.

Le socle 1.18.0 conserve en revanche les schémas horaires `OpeningHoursSpecification` et `specialOpeningHoursSpecification` du parc.

Un futur événement disposant d’une page publique dédiée pourra être étudié séparément pour un vrai balisage `Event` conforme aux recommandations Google.

### Multilingue

Les libellés d’interface du rendu sémantique existent en FR / EN / DE.

Pour le titre d’un événement, d’une période ou d’un jour férié, le renderer n’invente pas une traduction et ne reprend pas automatiquement le français dans une page EN/DE. Si aucun titre public n’existe dans la langue courante, il utilise seulement un type générique localisé comme « Event » ou « Zeitraum ».

Les `internal_label` restent strictement internes et ne sont jamais utilisés comme repli public.

## Compatibilité avec le socle IA & Google 1.18.0

La 1.20.1 ne remplace pas `Parcs_HT_AI_Google`.

Le socle 1.18.0 continue de gérer :

- l’identité `TouristAttraction` / `LocalBusiness` ;
- `OpeningHoursSpecification` ;
- `specialOpeningHoursSpecification` ;
- la suspension automatique du JSON-LD du plugin lorsque SEOPress PRO est détecté ;
- les contrôles d’indexation déjà présents dans l’administration.

La nouvelle classe complète ce socle avec une représentation HTML serveur du calendrier qui manquait en 1.18.0.

## Relation avec l’accessibilité 1.20.2

La couche 1.20.1 a volontairement été conçue pour être réutilisable lors du chantier lecteurs d’écran / clavier prévu pour la 1.20.2.

Il ne faudra pas créer demain une seconde représentation « spéciale aveugle » contenant d’autres données. L’objectif est de renforcer progressivement la même information sémantique et la même source de vérité.

## Tests ajoutés

`tests/indexation-1201-contract.php` vérifie notamment :

- le chargement du renderer ;
- la lecture des sources canoniques ;
- la prise en charge des horaires, exceptions, périodes, événements et jours fériés ;
- l’absence de JSON-LD Event ;
- l’absence de contenu masqué aux visiteurs ;
- l’absence de stockage propre ;
- l’absence de `internal_label` dans le renderer.

`tests/indexation-1201-runtime.php` construit une saison de test et vérifie réellement le HTML obtenu en français et en anglais.

Le contrat 1.18.0 appelle automatiquement ces tests à partir de la version 1.20.1, y compris dans le paquet de production nettoyé exécuté par la CI existante.

## Protection et restauration

Avant le développement, le point de restauration suivant a été créé depuis la 1.20.0 publiée :

`checkpoint/1.20.0-before-ai-calendar`

Issue de cadrage : #91 — « Préparer 1.20.1 — indexation Google, moteurs et IA du calendrier sans dépendre du JavaScript ».

Branche de développement : `feature/1.20.1-indexation-google-ia`.

## Contrôles à effectuer après déploiement

Sur La Montagne des Singes et La Forêt des Singes :

1. ouvrir le code source HTML de la page contenant le calendrier et confirmer que les horaires, événements et périodes publics importants sont déjà présents sans exécuter JavaScript ;
2. vérifier que le calendrier visuel et les interactions restent identiques ;
3. contrôler FR / EN / DE ;
4. vérifier les données structurées horaires avec les outils Google adaptés ;
5. utiliser Search Console pour demander/contrôler l’exploration des pages concernées ;
6. contrôler le `robots.txt`, le CDN et le pare-feu pour Googlebot et OAI-SearchBot ;
7. refaire un audit « contexte zéro » avec un moteur/assistant ne disposant pas de l’historique du parc.

## Hors périmètre de la 1.20.1

- refonte clavier du calendrier ;
- roving tabindex et navigation aux flèches ;
- optimisation VoiceOver / NVDA / TalkBack ;
- plugin global d’accessibilité WordPress ;
- création automatique de pages événement ;
- balisage Event sans URL événement dédiée.

Ces sujets restent séparés afin que la mise à jour d’indexation puisse être validée sans mélanger le chantier accessibilité prévu en 1.20.2.
