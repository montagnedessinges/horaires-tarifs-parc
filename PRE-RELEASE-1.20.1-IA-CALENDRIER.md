# Pré-release 1.20.1 — Lisibilité IA du calendrier

Date de cadrage : 04/10/2026
Version publique de départ : 1.20.0
Tag de référence : v1.20.0

## Objectif

Préparer une évolution ciblée de l’extension afin que les informations actuellement portées par le calendrier (horaires, exceptions, événements, périodes repères et autres marqueurs) soient compréhensibles par les moteurs de recherche et les assistants IA sans dépendre uniquement de l’exécution JavaScript.

Ce document est un cadrage préalable. Il ne modifie aucun comportement fonctionnel du plugin.

## Constat vérifié

- La couche IA / Google introduite en 1.18.0 existe toujours en 1.20.0.
- Elle génère déjà les horaires réguliers et exceptionnels en JSON-LD via `OpeningHoursSpecification` / `specialOpeningHoursSpecification`.
- Le calendrier public est actuellement construit principalement côté JavaScript : dans le HTML initial, on retrouve surtout la structure du calendrier et les symboles de légende (`!`, `×`, `★`) mais pas l’ensemble des libellés et informations calendaires sous forme textuelle directement exploitable.
- Le test `tests/ai-google-1180-contract.php` contrôle la présence de la couche IA existante, mais ne garantit pas que les événements, périodes repères et marqueurs soient exposés de manière lisible aux robots.

## Recherche externe effectuée avant développement

### Google Search Central — JavaScript

Google indique qu’il exécute JavaScript lors du rendu, mais recommande toujours le rendu côté serveur ou le pré-rendu, notamment parce que tous les robots ne savent pas exécuter JavaScript et parce que le rendu peut intervenir dans une phase distincte de l’exploration initiale.

Sources :
- https://developers.google.com/search/docs/crawling-indexing/javascript/javascript-seo-basics
- https://developers.google.com/search/docs/crawling-indexing/javascript/dynamic-rendering

Conséquence retenue : ne pas créer une solution spécifique aux robots. Préférer un HTML initial utile et cohérent pour tout le monde, puis laisser JavaScript enrichir l’expérience.

### Google Search Central — données structurées

Google recommande JSON-LD mais précise que les données structurées doivent représenter fidèlement le contenu de la page et ne pas décrire des informations cachées aux utilisateurs.

Source :
- https://developers.google.com/search/docs/appearance/structured-data/sd-policies

Conséquence retenue : aucune information IA ne doit être ajoutée uniquement dans un bloc invisible ou uniquement destiné aux crawlers.

### Google Search Central — Event

Google demande notamment une URL unique pour chaque événement et indique que son expérience de recherche d’événements vise des pages centrées sur un seul événement. Google déconseille également d’utiliser `Event` pour représenter de simples horaires d’ouverture ou des promotions.

Source :
- https://developers.google.com/search/docs/appearance/structured-data/event

Conséquence retenue : ne pas injecter automatiquement un graphe `Event` pour chaque marqueur sur la page calendrier multi-événements. Un véritable `Event` ne sera envisagé que lorsqu’un événement dispose d’une page publique dédiée correspondant réellement au contenu balisé.

### Schema.org — horaires

`OpeningHoursSpecification` et `specialOpeningHoursSpecification` sont adaptés aux horaires réguliers et exceptions d’un lieu. La couche 1.18.0 utilise déjà cette architecture.

Sources :
- https://schema.org/OpeningHoursSpecification
- https://schema.org/specialOpeningHoursSpecification

Conséquence retenue : conserver cette partie et ne pas la dupliquer.

### OpenAI / ChatGPT Search

OpenAI indique qu’un site doit autoriser `OAI-SearchBot` et que l’hébergeur ou CDN doit également laisser passer ses adresses IP publiées pour pouvoir être inclus dans ChatGPT Search.

Source :
- https://help.openai.com/fr-fr/articles/9237897-recherche-chatgpt

Conséquence retenue : la future validation devra inclure le contrôle robots.txt et, si possible, la configuration CDN / sécurité. Le plugin ne doit pas forcer automatiquement ces règles.

## Architecture prévue pour 1.20.1

### 1. Rendu serveur du contenu calendaire essentiel

Le calendrier doit disposer d’un contenu HTML initial produit côté PHP avant JavaScript.

Ce contenu doit pouvoir exposer au minimum, selon les données publiques de la saison :
- année et période concernée ;
- horaires réguliers utiles ;
- exceptions et fermetures avec dates ;
- événements avec leur titre public et leurs dates ;
- périodes repères avec leur titre public et leurs dates ;
- autres marqueurs publics pertinents avec un libellé explicite.

Les codes visuels internes (`E`, `P`, `D`, `★`, `!`, `×`) ne doivent jamais constituer la seule information disponible pour une machine.

### 2. Pas de contenu réservé aux robots

Pas de cloaking, pas de variante de page selon User-Agent, pas de texte `display:none`, pas de bloc injecté uniquement pour les bots.

L’information produite côté serveur doit être cohérente avec celle vue par l’utilisateur.

### 3. JavaScript en amélioration progressive

Le JavaScript public doit continuer à gérer l’expérience interactive actuelle, mais ne doit plus être l’unique source du contenu calendaire.

Approche souhaitée :
- PHP fournit une base sémantique exploitable ;
- JavaScript enrichit / remplace les zones interactives sans supprimer la disponibilité sémantique du contenu ;
- aucun doublon visuel gênant ne doit apparaître.

### 4. Événements

Ne pas ajouter automatiquement `Event` dans le JSON-LD de la page calendrier multi-événements.

Si un événement dispose d’une URL publique dédiée :
- vérifier qu’il s’agit bien d’un véritable événement ;
- produire `Event` uniquement sur cette URL ;
- renseigner au minimum `name`, `startDate`, `location` et les propriétés recommandées disponibles ;
- tester avec Rich Results Test.

### 5. Périodes repères

Les vacances, périodes de fréquentation ou repères internes/publics ne doivent pas être transformés artificiellement en événements.

Elles doivent être exposées comme contenu HTML sémantique avec nom public et dates, lorsque ces informations sont publiques.

### 6. Horaires et exceptions

Conserver `OpeningHoursSpecification` et `specialOpeningHoursSpecification` existants.

Éviter toute seconde source JSON-LD concurrente.

### 7. Langues

La sortie doit respecter FR / EN / DE selon la langue canonique de la page.

Aucune traduction ne doit être inventée si elle n’existe pas dans les données publiques du parc.

### 8. Robots et accès

Le plugin doit continuer à ne pas modifier automatiquement robots.txt.

La validation post-installation devra contrôler :
- WordPress `blog_public` ;
- robots.txt ;
- Googlebot ;
- OAI-SearchBot ;
- éventuel blocage LiteSpeed / CDN / pare-feu.

## Tests à ajouter avant release

1. Test contractuel dédié à la lisibilité calendrier IA.
2. Vérification que le HTML initial contient des libellés et dates exploitables sans exécuter JavaScript.
3. Vérification FR / EN / DE.
4. Vérification qu’aucun code visuel seul (`E`, `P`, `D`) n’est la seule représentation d’une donnée publique.
5. Vérification qu’aucun faux `Event` JSON-LD n’est produit sur la page calendrier multi-événements.
6. Vérification que `OpeningHoursSpecification` et `specialOpeningHoursSpecification` restent présents et non dupliqués.
7. Vérification du paquet de production après `tools/prepare-production.php`.
8. Test Rich Results Test / Schema Validator après installation sur un site réel.
9. Test externe réel : lecture de la page avec un outil n’exécutant pas le JavaScript et contrôle que les événements / périodes publics sont compris.

## Critère de réussite fonctionnel

Après installation de 1.20.1, une IA ou un crawler récupérant seulement le HTML initial doit pouvoir déterminer les informations calendaires publiques principales sans se limiter aux symboles `!`, `×`, `★`, `E`, `P` ou `D`.

## Protection avant développement

Un point de restauration a été créé à partir du tag `v1.20.0` avant toute modification fonctionnelle.

Aucun fichier PHP, JavaScript ou CSS fonctionnel n’est modifié par ce cadrage.

## Statut

Cadrage : validé par demande utilisateur le 04/10/2026.
Développement : non commencé dans ce document.
Release cible : 1.20.1, sous réserve de tests et CI verts.
