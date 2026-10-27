## Incident de production 27/10/2026 — priorité aux enregistrements
- Suite à installation de la version 1.20.12, l’utilisateur a signalé une erreur critique WordPress lors d’un clic sur « Enregistrer » ; origine exacte non établie faute de journal PHP.
- La version **1.20.13 est un retour conservateur à la logique 1.20.11**, en conservant les nouveautés du calendrier, mais en retirant le nouveau code d’autorisation des devis groupes livré en 1.20.12. Ne pas affirmer que cette fonctionnalité est disponible en 1.20.13.
- Avant de réintroduire les règles d’accueil de groupes sur jours fermés : tests d’enregistrement WordPress réel, sauvegardes et rollback testé. Ne jamais toucher aux données des saisons, devis ou tarifs.

## Décision livrée en 1.20.12 — Devis groupes pendant les fermetures
- Gestion du parc → Devis groupes → Accès au devis : règle globale par parc « Autoriser la création de devis groupes pour les journées entièrement fermées au public ? » Oui ou Non, avec une liste de journées/périodes inversant la règle par défaut sur **les seuls jours totalement fermés**.
- Une journée partiellement ouverte (après-midi seulement) reste toujours autorisée, y compris si le groupe souhaite venir le matin. Les règles ne doivent pas être confondues avec les horaires d'ouverture au public ni avec la disponibilité des grilles tarifaires par année.
- Le formulaire CF7 doit être masqué pour les dates interdites, avec message distinct FR/EN/DE. Le hook de validation `wpcf7_validate` interdit l'envoi même en cas de contournement JS. L'activation ou non du sélecteur de date ne doit pas désactiver la protection serveur.
- Option `parcs_ht_quote_gate` : `allow_closed_group_quotes` (défaut 1 pour compatibilité), `closed_date_exceptions` (tableau de plages `start/end` inclusives), et messages `blocked_message_fr/en/de`. L'option est locale à chaque installation WordPress, indépendante de la saison affichée en administration.
- Les sauvegardes restent scopées au module ; n'altèrent pas les tarifs, les saisons ou les paramètres de l'autre parc.

## Décision 26/10/2026 — deux formes de fermeture
- Dans « Périodes → Exceptions », trois choix publics : Horaires exceptionnels, Fermeture exceptionnelle, Fermé. « Fermé » ferme réellement le jour, comme une fermeture exceptionnelle, conserve la croix, mais affiche simplement « Fermé » et non « Fermeture exceptionnelle ».
- Le moteur métier reste `type=closed` pour les deux fermetures ; seul le champ d'affichage `closure_display=plain` les distingue. Anciennes exceptions sans ce champ = fermeture exceptionnelle. En administration, « Fermé » est une option de sélection alias `closed_plain`, transformée en `type=closed` + `closure_display=plain` à l'enregistrement. Ne jamais confondre l'alias UI et le type stocké.
- Une date fermée sélectionnée dans le calendrier affiche la prochaine ouverture trouvée dans les saisons **publiées**, avec jour de semaine, date, année et heure. Si aucune prochaine ouverture n'est connue, ne pas en inventer. Le calcul intersaisons existant doit être réutilisé.
- Enregistrer et présenter de la même manière sur les deux sites, sans remplacer ni effacer les anciennes exceptions, saisons, tarifs ou configurations.
- Le chantier distinct des autorisations de devis groupes aux dates fermées est détaillé dans ROADMAP.md ; il n'est pas livré par 1.20.11.

# Contexte ChatGPT — Horaires & Tarifs Parc

Ce fichier sert de point d’entrée pour toute nouvelle conversation ChatGPT qui doit travailler sur cette extension WordPress.

## Dépôt canonique

- Dépôt GitHub : `montagnedessinges/horaires-tarifs-parc`
- Branche principale : `main`
- Le code GitHub est la source de vérité pour le code de l’extension.
- Les données propres à chaque parc restent enregistrées dans WordPress et ne doivent jamais être écrasées par une mise à jour du code.
- La même extension est utilisée par La Montagne des Singes et La Forêt des Singes.

## Règle de mémoire impérative

GitHub doit être la mémoire complète et durable de ce projet.

Toute information nouvelle apprise au cours du travail et susceptible d’aider une future reprise doit être consignée dans le dépôt, même si elle ne provoque pas immédiatement une modification du code. Cela comprend notamment :

- décisions fonctionnelles et règles métier validées par l’utilisateur ;
- préférences d’interface et de fonctionnement ;
- comportements attendus sur les deux sites ;
- bugs observés, symptômes, captures décrites et causes identifiées ;
- résultats d’audits et de vérifications ;
- décisions d’architecture et raisons de ces décisions ;
- contraintes WordPress, PHP, sécurité, performance, SEO et distribution ;
- éléments à faire dans une future version ;
- solutions envisagées puis abandonnées lorsqu’il est utile de savoir pourquoi ;
- dépendances avec le thème ou du code extérieur au dépôt lorsqu’elles sont connues ;
- état d’une mise à jour en cours et points restant à vérifier avant publication.

Choisir le fichier adapté : `CHATGPT-CONTEXT.md` pour les règles et connaissances durables, `ROADMAP.md` pour les évolutions futures, `CHANGELOG.md` pour ce qui est effectivement livré, et un fichier `AUDIT-*.md` pour les investigations/incidents importants. Si aucune rubrique existante ne convient, créer une documentation dédiée plutôt que laisser l’information uniquement dans une conversation.

Une nouvelle conversation doit pouvoir reprendre le projet depuis GitHub sans dépendre de la mémoire d’un ancien chat. Ne jamais considérer une information importante comme suffisamment conservée parce qu’elle existe seulement dans la conversation.

## Règle de travail impérative

Avant toute modification :

1. Lire ce fichier.
2. Vérifier la version actuelle dans `horaires-tarifs-parc.php`.
3. Lire `CHANGELOG.md`, `ROADMAP.md` lorsqu’il concerne le sujet, et les fichiers directement concernés.
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
14. Toute information importante apprise pendant le travail doit être consignée dans GitHub avant de considérer le sujet terminé.
15. Lorsqu’une mise à jour de l’extension est demandée, ne revenir vers l’utilisateur avec le résultat final que lorsque la nouvelle version est réellement publiée dans les Releases GitHub et que le ZIP de production est disponible. Un commit poussé, un workflow en cours, une version seulement présente dans le code ou des tests encore en cours ne constituent pas une mise à jour publiée. En cas d’échec ou de contrôle encore en cours, poursuivre les corrections et vérifications sans annoncer la mise à jour comme terminée ni demander à l’utilisateur de l’installer.

### Développement sans intervention sur les sites

Lorsqu’une demande porte uniquement sur le développement et la publication de l’extension :

- travailler exclusivement depuis le dépôt GitHub canonique ;
- effectuer les contrôles, augmenter la version et publier la release depuis GitHub ;
- ne pas ouvrir, consulter ni tester les sites WordPress de la Montagne des Singes ou de la Forêt des Singes ;
- n’intervenir sur un site que si l’utilisateur demande explicitement une installation, une mise à jour ou une vérification sur ce site.


### Compatibilité du shortcode Tarifs groupes

Le shortcode public `[parc_tarifs_groupes]` et ses variantes de langue doivent rester capables d’afficher des tarifs groupes déjà enregistrés dans les anciens formats de données (`price`, `cells['price']`, prix au niveau de la ligne), même si les identifiants permanents `tariff_row_...` / `tariff_col_...` n’ont pas encore été persistés par une migration d’administration. Les identifiants permanents restent la référence pour les liaisons métier du devis, mais ils ne doivent pas être une condition préalable au simple affichage public d’un tarif existant. Ne jamais réécrire silencieusement les tarifs du parc uniquement pour rendre ce shortcode affichable. Le test `tests/group-tariff-shortcode-runtime.php` protège ce comportement.
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

## Distribution de production et hygiène des sources

Les sources complètes et commentées restent sur GitHub pour la maintenance. Le ZIP WordPress doit être une distribution minimale de production.

Règles actuelles :

- le package est construit avec une liste blanche : fichier principal du plugin, `uninstall.php`, `includes/` et `assets/` ;
- les documents Markdown, audits, tests, outils de build, dépendances de développement et métadonnées GitHub ne sont pas distribués ;
- les commentaires PHP/JS/CSS sont retirés de la copie de production, sauf l’en-tête officiel du plugin nécessaire à WordPress ;
- les références de source maps et autres traces de développement inutiles doivent être absentes du package ;
- la syntaxe PHP et JavaScript du package nettoyé est revalidée avant publication ;
- le nettoyage concerne la distribution, pas les sources GitHub : ne pas rendre le dépôt illisible pour tenter de masquer l’origine d’un code ;
- l’objectif est un package propre et professionnel, pas une obfuscation du code.

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
8. mettre à jour la documentation GitHub avec toutes les connaissances nouvelles utiles à une future reprise ;
9. mettre à jour la version et le changelog ;
10. pousser sur `main` ;
11. vérifier le workflow GitHub Actions et la release ;
12. ne pas supprimer la release précédente.

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
- `assets/display-state.js` et `assets/status-sync.js` — état partagé et rafraîchissement des blocs accueil/en-tête/Aujourd’hui, consolidés en 1.9.19 ;
- `.github/workflows/release.yml` — construction et publication des releases ;
- `CHANGELOG.md` — historique fonctionnel ;
- `ROADMAP.md` — décisions et évolutions futures déjà discutées ;
- `AUDIT-2026-08-27.md` — audit de référence sur les divergences Forêt/Montagne et les couches d’affichage.

## Audit du 31 août 2026 et correctifs 1.9.19

- Lire `AUDIT-2026-08-31.md` pour les huit défauts reproduits sur 1.9.18 et leurs corrections.
- Publication des devis : saison publiée ET autorisation de devis non refusée. Préserver un indicateur explicite `published=0` dans la grille ; si cet ancien indicateur est absent, suivre la publication de la saison. `Group_Quotes::settings(false)` fournit les réglages bruts à l’administration ; `settings()` calcule l’état public sans enregistrer cette projection.
- Les tarifs non disponibles ne sont pas sérialisés dans les scripts publics. Les prix restent toujours recalculés et validés côté serveur.
- `is_admin()` ne signifie pas que le visiteur est administrateur : les exports publics passent par `admin-post.php`. Les aperçus brouillon sont réservés à la page d’administration autorisée ou à une sauvegarde munie de capacité/nonce valides. Le rendu public impose sa propre sélection publiée et filtre les colonnes masquées sans les effacer des réglages.
- Un seul rafraîchissement périodique pilote les blocs horaires dynamiques via le moteur partagé. Le script calendrier observe les changements du calendrier mais se déconnecte pendant ses propres écritures, qui sont aussi idempotentes.
- Les erreurs des contrôles de publication ne sont plus tolérées. Le contrôle officiel porte sur les sources de distribution avant retrait des commentaires afin de conserver les justifications PHPCS ponctuelles ; le nettoyeur vérifie ensuite que les tokens exécutables PHP sont inchangés. Les tests de régression tournent aussi sur les sources nettoyées.
- Les essais locaux utilisent PHP WebAssembly 7.4/8.3 avec fonctions WordPress simulées et jsdom ; les essais PHP natifs et le contrôle WordPress sont exécutés par GitHub Actions. Cela ne prouve pas l’installation ni le bon fonctionnement complet des thèmes, CF7, envois mail et PDF des deux sites en production.

## Pour démarrer une nouvelle conversation ChatGPT

Le message utilisateur peut simplement être :

> Accède au dépôt GitHub `montagnedessinges/horaires-tarifs-parc`, lis `CHATGPT-CONTEXT.md`, le dernier `AUDIT-*.md` pertinent, `ROADMAP.md` et `CHANGELOG.md`, puis utilise GitHub comme source de vérité avant toute modification.

Une fois ces fichiers lus, ne pas demander à l’utilisateur de renvoyer un ZIP si le dépôt GitHub est accessible.

## Important

Ce fichier décrit les règles de travail et la logique métier connues à la date de sa dernière modification. Si le code, le changelog, un audit plus récent ou une instruction explicite de l’utilisateur est plus récent, l’information la plus récente prévaut. Mettre à jour ce fichier dès qu’une évolution, une décision ou une information importante change ou complète ces règles.

## Événements WordPress — 1.20.3

Décision utilisateur du 12/10/2026 : supprimer réellement l’ancien déclenchement de pop-ups par événements/périodes/exceptions. Les pop-ups visuels autonomes restent indépendants. Les anciennes données de saisons restent conservées comme données inertes ; aucun moteur public ne les utilise pour produire un pop-up.

Le fichier du thème confirmé par l’utilisateur est `single-evenement.php`. Le modèle fourni dans le chat lit `date_debut`, `date_fin`, le titre et le permalien. L’adaptateur lit les métadonnées brutes ACF Ymd (ou ISO), sans dépendre du format de retour configuré dans ACF et sans importer le contenu. La source est configurable dans Périodes/événements, séparément sur chaque site. Aucun changement du thème n’est nécessaire pour cette lecture. Une éventuelle correction de présentation de single-evenement.php est une étape séparée, avec sauvegarde de sa version originale avant modification.

Voir UPDATE-1.20.3.md.

### Organisation des événements — 1.20.3

Les événements sont désormais gérés uniquement dans WordPress (`evenement`, dates ACF `date_debut`/`date_fin`). La saisie manuelle dans l’extension et son import CSV sont retirés. Les anciennes données restent dans les saisons comme historique inactif ; ne pas les réactiver. Les périodes repères, horaires exceptionnels et pop-ups autonomes sont conservés. Voir UPDATE-1.20.3.md.

## Audit du thème 12/10/2026 / événements — 1.20.4

Archive du thème Montagne des Singes fournie par l'utilisateur et analysée sans accès à l'hébergement.
Le fichier `functions.php` déclare le type `evenement`. Les deux blocs du thème `sc-3col-txt-sld` et `block_evenements_liste` dans `templates/centre.php` lisent directement les articles WordPress et les champs ACF `date_debut`/`date_fin`.
Ils ne dépendent pas du shortcode calendrier ni de l'adaptateur `Parcs_HT_WordPress_Events`. Ne jamais écraser ces champs ni modifier le thème dans une évolution de marqueurs.
Le carrousel applique dans son affichage un `+1 days` historique ; défaut possible à investiguer séparément, sans correctif automatique.
Le fichier `single-evenement.php` fourni dans le ZIP est encore la version historique. La nouvelle présentation proposée en conversation n'est pas installée.
Voir `AUDIT-2026-10-12-THEME-EVENEMENTS.md` et `UPDATE-1.20.4.md`.

## Bons découverte scolaires — version 1.20.6 (16 octobre 2026)

- Module indépendant des devis groupes, associé à CF7 : `[parc_bon_decouverte]` et variantes FR/EN/DE ; une première étape vérifie un code postal puis révèle CF7 sans changer de page.
- Administration via Gestion du parc → Groupes → Bons découverte ; titres, messages et traductions sont éditables ; liste des départements laissée vide à installer manuellement et activation désactivée à l’installation.
- Une configuration différente est enregistrée dans les options WordPress de chaque parc ; ne jamais préremplir les départements de la Forêt des Singes à partir de ceux de la Montagne des Singes.
- Réception CF7 et copie de confirmation uniquement : aucune attribution, génération ou livraison de bon automatique. L’équipe contrôle les bons papier déjà utilisés et les éventuels doublons.
- Une analyse séparée dans Google Sheets pourra ensuite rapprocher établissements (UAI), bons papier et numériques, campagnes Brevo, devis et réservations 2027+, sans modifier le classeur existant de réservations.
- Le formulaire CF7 doit contenir `[text* bon_code_postal readonly]`, et ne peut être envoyé pour l’offre si le serveur refuse le code postal. Le filtrage navigateur n’est pas une règle de sécurité suffisante à lui seul.
- Mode d’emploi et modèle CF7 : `docs/BONS-DECOUVERTE-SCOLAIRES.md`.

## Simplification Bons découverte — 1.20.7 (16 octobre 2026)

- L'offre Bons découverte est réservée à la communication française. Aucune interface de traduction ni message public indiquant que d'autres langues ne sont pas disponibles.
- Seul le jeu de champs éditoriaux FR est présenté à l'administrateur et lu au rendu. Les anciens réglages EN/DE ne sont pas supprimés de la base, pour compatibilité, mais ignorés par ce module.
- Le shortcode à publier est `[parc_bon_decouverte]`. Les aliases existants FR/EN/DE restent enregistrés comme précaution technique mais rendent désormais tous le bloc français. Le registre n'annonce que le shortcode standard pour ce module.

## Décision — Affichage des fermetures exceptionnelles (octobre 2026)

- La fermeture exceptionnelle active doit être clairement signalée dans les textes du shortcode d'accueil `[parc_horaire_accueil]` et du bloc `Aujourd'hui` au-dessus du calendrier.
- Ne pas modifier le calendrier, ses détails par jour, le HTML des shortcodes, les pictogrammes ou le thème.
- Le shortcode d'accueil produit trois champs textuels ; l'horloge et l'encadré graphique ne figurent pas dans son HTML et leur origine exacte dans le thème n'est pas confirmée.
- Les horaires exceptionnels ouverts sont affichés comme des horaires normaux ; seule la fermeture exceptionnelle déclenche le traitement particulier.
- Préserver tous les autres cas, les deux parcs et FR/EN/DE. Tester sur mobile et vérifier le thème avant publication.
