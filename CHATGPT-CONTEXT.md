# Contexte ChatGPT — Horaires & Tarifs Parc

Ce fichier sert de point d’entrée pour toute nouvelle conversation ChatGPT qui doit travailler sur cette extension WordPress.

## Dépôt canonique

- Dépôt GitHub : `montagnedessinges/horaires-tarifs-parc`
- Branche principale : `main`
- Le code GitHub est la source de vérité pour le code de l’extension.
- Les données propres à chaque parc restent enregistrées dans WordPress et ne doivent jamais être écrasées par une mise à jour du code.
- La même extension est utilisée par La Montagne des Singes et La Forêt des Singes.

## Règle de travail impérative

Avant toute modification :

1. Lire ce fichier.
2. Vérifier la version actuelle dans `horaires-tarifs-parc.php`.
3. Lire `CHANGELOG.md` et les fichiers directement concernés.
4. Lire le dernier fichier `AUDIT-*.md` pertinent lorsqu’il existe, en particulier après un incident ou une régression récente.
5. Vérifier que la modification reste compatible avec les deux parcs.
6. Ne jamais coder en dur un comportement « Forêt des Singes » ou « Montagne des Singes » si cela peut être un réglage WordPress.
7. Préserver les réglages existants et prévoir une compatibilité avec les anciennes données quand un champ évolue.
8. Toute évolution fonctionnelle destinée aux sites doit entraîner une nouvelle version de l’extension.
9. Les mises à jour se font sur GitHub, sur `main`, puis sont publiées par le workflow GitHub Actions.
10. Après un push de version, vérifier que le workflow `Build and publish WordPress release` s’est déclenché et s’est terminé correctement.
11. Avant toute release, vérifier la compatibilité avec les versions WordPress et PHP déclarées par l’extension, ainsi que les API WordPress utilisées.
12. Avant toute release, vérifier que la modification n’ajoute pas de charge inutile côté serveur ou navigateur.
13. Avant toute release, vérifier les implications de sécurité et ne jamais désactiver une protection simplement pour contourner une erreur.
14. Tout audit important, incident de production, cause identifiée, contournement temporaire ou décision d’architecture doit être consigné dans GitHub afin qu’un nouveau chat puisse reprendre le projet sans perte de contexte.

## Principe d’architecture

Le même code doit fonctionner sur les deux sites avec des données/configurations différentes.

Ne pas multiplier les correctifs spécifiques à un site. Privilégier :

- un moteur commun ;
- des réglages indépendants par installation WordPress ;
- une compatibilité descendante ;
- une seule source de vérité pour chaque donnée métier ;
- le moins de couches correctives possible ;
- une logique simple, lisible et maintenable.

Lorsqu’un problème révèle deux moteurs ou plusieurs scripts qui calculent la même information, privilégier la suppression de la duplication et la consolidation dans une seule logique commune plutôt que l’ajout d’une nouvelle rustine.

## Documentation des audits et incidents

Le dépôt GitHub doit conserver la mémoire technique du projet, pas seulement son dernier état.

Règles permanentes :

- créer ou mettre à jour un fichier d’audit lorsqu’un problème important nécessite plusieurs vérifications ou touche la production ;
- distinguer clairement dans les audits ce qui est confirmé, ce qui est probable et ce qui ne peut pas être vérifié avec les accès disponibles ;
- noter les sites/pages observés, les fichiers concernés, la cause identifiée ou probable et le plan recommandé ;
- lorsqu’un correctif est finalement publié, faire le lien avec l’incident dans le changelog si cela aide à comprendre la version ;
- ne pas supprimer les anciens audits simplement parce que le problème est corrigé ;
- lors d’un nouveau chat, lire le dernier audit pertinent avant de reprendre un problème déjà rencontré.

Audit de référence actuel : `AUDIT-2026-08-27.md`.

## Conservation des versions et retour arrière

Ne jamais supprimer une ancienne release, un ancien tag ou l’historique Git simplement parce qu’une nouvelle version est publiée.

Les anciennes versions constituent des points de restauration indispensables en cas de régression importante.

Règles :

- chaque version publiée doit rester identifiable par son tag Git (`vX.Y.Z`) ;
- conserver les releases GitHub et leurs ZIP lorsqu’ils ont été publiés correctement ;
- ne pas réutiliser un numéro de version déjà publié pour un code différent ;
- ne pas réécrire ou supprimer l’historique Git pour masquer une mauvaise mise à jour ;
- en cas de gros défaut, identifier la dernière version stable et pouvoir revenir proprement à son code ;
- documenter dans le changelog les corrections apportées après une régression ;
- avant un changement important, consulter si besoin le diff avec la dernière version stable afin de limiter les régressions.

Une nouvelle version remplace la précédente sur les sites seulement après installation, mais elle ne doit jamais faire disparaître la possibilité technique de retrouver une version antérieure dans GitHub.

## Compatibilité WordPress et PHP

La compatibilité doit être vérifiée avant chaque évolution significative.

Toujours contrôler :

- la version minimale WordPress déclarée dans l’en-tête du plugin ;
- la version minimale PHP déclarée ;
- que les fonctions, hooks et API WordPress utilisés existent dans les versions supportées ;
- l’absence de fonctions PHP incompatibles avec la version minimale annoncée ;
- la compatibilité avec les mécanismes standards WordPress : options, transients, cron, HTTP API, shortcodes, enqueue scripts/styles, mises à jour de plugins et sécurité des formulaires ;
- les éventuels avertissements de dépréciation lorsque WordPress évolue.

Si une modification impose de relever la version minimale de WordPress ou PHP, ne pas le faire silencieusement : le signaler clairement avant publication et documenter le changement.

## Performance et charge du site

L’extension doit rester légère. Une fonctionnalité ne doit pas alourdir inutilement les pages publiques ou l’administration.

Principes permanents :

- ne charger les scripts, styles et modules que lorsqu’ils sont réellement nécessaires ;
- éviter les requêtes réseau sur chaque affichage public ;
- éviter les lectures lourdes de grosses options WordPress à répétition si elles peuvent être mises en cache ou chargées conditionnellement ;
- éviter les boucles, observers, timers ou traitements JavaScript trop fréquents sans nécessité ;
- limiter le nombre de fichiers et de couches JavaScript qui recalculent la même information ;
- privilégier les calculs simples et les données déjà disponibles ;
- utiliser les caches/transients de manière raisonnable lorsque cela réduit la charge sans créer de données périmées dangereuses ;
- ne pas charger le moteur GitHub/updater sur les pages publiques si ce n’est pas nécessaire ;
- vérifier qu’une évolution n’augmente pas fortement le poids des assets ou le temps de rendu.

Objectif : une extension simple, efficace et fluide, avec le minimum de travail nécessaire côté serveur et navigateur.

## Sécurité

La sécurité ne doit jamais être sacrifiée pour faire fonctionner plus vite une mise à jour.

Toujours préserver notamment :

- vérification SSL pour les connexions HTTPS ;
- validation et sanitation des données enregistrées ;
- échappement des données affichées ;
- nonces et contrôle des capacités pour les actions d’administration ;
- absence de secrets, tokens ou clés privées en clair dans le dépôt ;
- restriction des tokens GitHub au minimum nécessaire ;
- vérification d’intégrité des packages de mise à jour lorsqu’elle est disponible ;
- absence d’exécution ou d’inclusion de données non fiables.

Ne jamais utiliser `sslverify=false` comme solution permanente à une erreur de certificat.

## Logique actuelle des horaires

Les horaires utilisent la structure existante :

- Créneau 1 : ouverture + fermeture ;
- Créneau 2 : ouverture + fermeture facultatives.

Ne pas remplacer cette structure par « matin / après-midi » dans le modèle de données.

Chaque créneau peut avoir sa propre dernière entrée :

- dernière entrée du créneau 1 = X minutes avant la fermeture du créneau 1 ;
- dernière entrée du créneau 2 = X minutes avant la fermeture du créneau 2.

Les anciens réglages doivent rester utilisables comme valeur de secours.

Exemple La Forêt des Singes :

- créneau 1 : 10h–12h ; dernière entrée 30 minutes avant → 11h30 ;
- créneau 2 : 13h–17h30 ; dernière entrée 45 minutes avant → 16h45.

## Affichage dynamique dans la journée

Les blocs publics doivent raisonner selon l’heure réelle dans le fuseau du parc, et non seulement selon le fait que « la journée est ouverte ».

États attendus :

- avant le premier créneau : afficher la prochaine ouverture du jour ;
- pendant le premier créneau : statut OUVERT ;
- entre deux créneaux : ne pas afficher OUVERT ; afficher la réouverture du jour ;
- pendant le deuxième créneau : statut OUVERT ;
- après le dernier créneau : ne jamais afficher OUVERT ; afficher fermé pour aujourd’hui puis la prochaine ouverture ;
- l’affichage doit pouvoir se réactualiser sans rechargement de page lorsque l’heure change.

Ne jamais produire une combinaison incohérente comme :

`OUVERT — Ouverture à 9h`

## Page d’accueil

L’affichage doit rester compact et adaptatif.

Pour une journée à deux créneaux :

- le matin : afficher la journée complète, ex. `10h–12h / 13h–17h30` ;
- après la fin du premier créneau : ne plus afficher les horaires déjà passés ;
- entre les deux créneaux : afficher la réouverture et le créneau restant ;
- pendant le deuxième créneau : afficher uniquement le créneau restant ;
- après fermeture : afficher la prochaine ouverture.

La dernière entrée affichée doit être celle du créneau actuellement utile.

## Page « Horaires & Tarifs »

Le bloc « Aujourd’hui » doit suivre la même logique dynamique que la page d’accueil.

Le calendrier, lui, reste un outil de consultation complète et ne doit pas masquer les informations historiques d’une date sélectionnée.

## Détail d’une date du calendrier

Pour une journée à deux créneaux, afficher :

- tous les créneaux de la journée ;
- les dernières entrées correspondantes à chaque créneau ;
- les éventuelles informations exceptionnelles.

## Résumé « Horaires du mois »

Le résumé mensuel doit permettre de comprendre rapidement les horaires du mois sans cliquer sur toutes les dates.

Il doit :

- analyser toutes les journées du mois ;
- conserver toutes les combinaisons horaires réellement présentes ;
- regrouper les dates qui partagent la même combinaison horaire ;
- indiquer les jours/périodes d’application ;
- reconnaître des cas simples comme « tout le mois », « week-ends », « du lundi au vendredi » uniquement lorsque cela correspond exactement aux dates ;
- sinon afficher des dates ou plages de dates précises.

Exemples possibles dans un même mois :

- `10h–12h / 13h–17h30` ;
- `13h–17h30` seulement ;
- `10h–12h` seulement.

Toutes doivent apparaître si elles existent réellement dans le mois.

Le résumé mensuel ne doit pas être adapté à l’heure actuelle : il décrit le mois complet.

## Accès temporairement limité / Domaine des Singes

La logique d’accès temporairement limité est distincte de la dernière entrée générale du parc.

Ne pas mélanger :

- fermeture d’un créneau du parc ;
- dernière entrée dans le parc ;
- interruption ou dernière entrée spécifique d’une zone comme le Domaine des Singes.

## Compatibilité des deux parcs

Une modification du moteur commun peut affecter les deux sites.

Toujours vérifier :

- journées à un seul créneau ;
- journées à deux créneaux ;
- créneau du matin seulement ;
- créneau de l’après-midi seulement ;
- horaires exceptionnels ;
- fermeture exceptionnelle ;
- passage automatique vers la prochaine ouverture ;
- dernière entrée du créneau 1 ;
- dernière entrée du créneau 2 ;
- résumé mensuel avec plusieurs régimes horaires.

## Versioning

Avant publication :

- augmenter la version dans l’en-tête du plugin ;
- augmenter `PARCS_HT_VERSION` ;
- mettre à jour `CHANGELOG.md` ;
- utiliser un message de commit clair ;
- vérifier que le numéro de version n’a jamais déjà été publié ;
- conserver la précédente release disponible comme point de retour.

Le workflow GitHub crée automatiquement le package/release quand une nouvelle version est poussée sur `main`.

Une modification uniquement documentaire (`README`, `CHATGPT-CONTEXT.md`, documentation interne) ne nécessite pas de changer la version du plugin, sauf si elle accompagne une évolution fonctionnelle.

## Checklist avant toute release fonctionnelle

Avant de considérer une version comme prête :

1. vérifier le comportement fonctionnel demandé ;
2. vérifier les deux parcs et les cas à un/deux créneaux ;
3. vérifier les anciennes données et migrations ;
4. vérifier WordPress/PHP supportés ;
5. vérifier la charge/performance ;
6. vérifier la sécurité ;
7. vérifier qu’aucune duplication inutile de logique n’a été ajoutée ;
8. mettre à jour la version et le changelog ;
9. pousser sur `main` ;
10. vérifier le workflow GitHub Actions et la release ;
11. ne pas supprimer la release précédente.

## Fichiers à consulter en priorité

Selon la modification :

- `horaires-tarifs-parc.php` — version et chargement principal ;
- `includes/class-parcs-ht-schedule.php` — résolution des journées et logique métier ;
- `includes/class-parcs-ht-shortcodes.php` — rendu des blocs publics ;
- `includes/class-parcs-ht-admin.php` — interface et sauvegarde des réglages ;
- `includes/class-parcs-ht-defaults.php` — schéma, valeurs par défaut et migrations ;
- `assets/frontend.js` — moteur d’affichage côté navigateur ;
- `assets/slot-last-entry-frontend.js` — comportement des créneaux / dernières entrées ;
- `assets/slot-last-entry-admin.js` — champs associés dans l’administration ;
- `assets/status-sync.js` — couche actuelle de synchronisation du statut, à considérer comme une rustine à auditer avant de la conserver ;
- `.github/workflows/release.yml` — construction et publication des releases ;
- `CHANGELOG.md` — historique fonctionnel ;
- `AUDIT-2026-08-27.md` — audit de référence sur les divergences Forêt/Montagne et les couches d’affichage.

## Pour démarrer une nouvelle conversation ChatGPT

Le message utilisateur peut simplement être :

> Accède au dépôt GitHub `montagnedessinges/horaires-tarifs-parc`, lis `CHATGPT-CONTEXT.md`, le dernier `AUDIT-*.md` pertinent et `CHANGELOG.md`, puis utilise GitHub comme source de vérité avant toute modification.

Une fois ces fichiers lus, ne pas demander à l’utilisateur de renvoyer un ZIP si le dépôt GitHub est accessible.

## Important

Ce fichier décrit les règles de travail et la logique métier connues à la date de sa dernière modification. Si le code, le changelog, un audit plus récent ou une instruction explicite de l’utilisateur est plus récent, l’information la plus récente prévaut. Mettre à jour ce fichier lorsqu’une évolution structurelle importante change ces règles.
