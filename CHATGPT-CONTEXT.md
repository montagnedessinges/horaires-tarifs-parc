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
4. Vérifier que la modification reste compatible avec les deux parcs.
5. Ne jamais coder en dur un comportement « Forêt des Singes » ou « Montagne des Singes » si cela peut être un réglage WordPress.
6. Préserver les réglages existants et prévoir une compatibilité avec les anciennes données quand un champ évolue.
7. Toute évolution fonctionnelle destinée aux sites doit entraîner une nouvelle version de l’extension.
8. Les mises à jour se font sur GitHub, sur `main`, puis sont publiées par le workflow GitHub Actions.
9. Après un push de version, vérifier que le workflow `Build and publish WordPress release` s’est déclenché.

## Principe d’architecture

Le même code doit fonctionner sur les deux sites avec des données/configurations différentes.

Ne pas multiplier les correctifs spécifiques à un site. Privilégier :

- un moteur commun ;
- des réglages indépendants par installation WordPress ;
- une compatibilité descendante ;
- une seule source de vérité pour chaque donnée métier.

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
- utiliser un message de commit clair.

Le workflow GitHub crée automatiquement le package/release quand une nouvelle version est poussée sur `main`.

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
- `.github/workflows/release.yml` — construction et publication des releases ;
- `CHANGELOG.md` — historique fonctionnel.

## Pour démarrer une nouvelle conversation ChatGPT

Le message utilisateur peut simplement être :

> Accède au dépôt GitHub `montagnedessinges/horaires-tarifs-parc`, lis `CHATGPT-CONTEXT.md`, puis utilise GitHub comme source de vérité avant toute modification.

Une fois ce fichier lu, ne pas demander à l’utilisateur de renvoyer un ZIP si le dépôt GitHub est accessible.

## Important

Ce fichier décrit les règles de travail et la logique métier connues à la date de sa dernière modification. Si le code, le changelog ou une instruction explicite de l’utilisateur est plus récent, l’information la plus récente prévaut. Mettre à jour ce fichier lorsqu’une évolution structurelle importante change ces règles.
