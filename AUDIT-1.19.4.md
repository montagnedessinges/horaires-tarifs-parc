# Audit 1.19.4 — Guides pédagogiques et administration

Date de l’audit : 2 octobre 2026  
Branche auditée : `main` à partir du commit `bb9db0db39bdc4f75fe8ecbff49d87bad09f5963` (version 1.19.3)

## Demande

L’audit porte sur deux anomalies :

1. la bibliothèque des guides pédagogiques était dupliquée par année, alors que les mêmes documents doivent pouvoir rester en place d’une saison à l’autre ;
2. certains parcours d’administration pouvaient encore afficher l’ancienne interface à onglets.

Le correctif doit conserver les statistiques par année, les shortcodes publics et les données principales `parcs_ht_settings`.

## Constats factuels

### 1. Stockage des guides encore saisonnier

La version 1.19.3 stockait les guides dans `parcs_ht_pedagogical_guides` sous la forme `version=3 / seasons / année / guides`.

Lors d’une duplication de saison, `Parcs_HT_Guide_Stats::clone_library_with_new_ids()` attribuait de nouveaux identifiants aux copies. Un même document pouvait donc avoir plusieurs IDs selon les années, ce qui obligeait à recopier la bibliothèque et fragmentait la lecture des statistiques.

La table statistique elle-même possédait déjà `season_year`. Il n’était donc pas nécessaire de dupliquer les guides pour conserver des statistiques annuelles.

### 2. Deux couches d’administration Guides restaient chargées

L’écran métier Guides 1.17.8 existait déjà, mais le moteur historique `Parcs_HT_Pedagogical_Guides::init()` enregistrait encore en parallèle son ancien menu technique, ses assets admin et son panneau embarqué via `admin_footer`.

Cette coexistence maintenait deux chemins administratifs possibles vers la même fonctionnalité.

### 3. Aperçu et Shortcodes rouvraient volontairement l’ancien écran

Dans `Parcs_HT_Admin_Navigation`, les pages `parcs-ht-preview` et `parcs-ht-shortcodes` étaient encore déclarées comme ponts vers les onglets `htp-preview` et `htp-shortcodes` de l’ancien grand formulaire.

Le retour de l’ancienne barre d’onglets n’était donc pas seulement un problème de cache : deux entrées actuelles l’utilisaient encore directement.

### 4. D’autres échappatoires historiques existaient

- Devis groupes acceptait encore `legacy_quote=1` pour contourner la redirection vers l’écran actuel.
- Administration générale proposait encore un lien « Ouvrir les réglages détaillés historiques ».
- Groupes masquait encore un ancien sous-menu Guides par JavaScript au lieu de le retirer exclusivement côté serveur.
- Le routeur ne dédupliquait pas explicitement les slugs identiques avant de réordonner les sous-menus.

### 5. La couche de sauvegarde de sécurité réécrivait encore le format v3

`Parcs_HT_Save_Integrity` intercepte la sauvegarde des guides avant le handler principal. Son implémentation 1.19.3 reconstruisait explicitement `version=3 / seasons` et écrivait la bibliothèque de l’année sélectionnée.

Sans correction de cette couche, une migration du moteur seul vers une bibliothèque globale aurait été annulée au premier enregistrement.

## Correctif 1.19.4

### Bibliothèque permanente

Le stockage passe à la version 4 :

```text
parcs_ht_pedagogical_guides
├── version: 4
├── library
│   └── guides
└── years
    └── 2026 / 2027 / ...
        └── enabled[guide_id]
```

Le contenu du guide — ID, cycle, langues, statut, titres, descriptions, PDF, couverture et ordre — est stocké une seule fois.

L’année sélectionnée ne stocke que l’état d’affichage du guide.

Un guide ajouté après coup est masqué par défaut dans une année déjà configurée afin de ne pas modifier rétroactivement un ancien millésime.

### Migration de l’historique

Au premier passage administrateur sur la version 1.19.4, le stockage v3 est converti en mémoire puis persisté en v4.

Les anciennes copies saisonnières sont rapprochées d’abord par ID connu, puis par signature du document. Le PDF est utilisé comme signature prioritaire ; à défaut, une signature du contenu est calculée.

Un ID devient l’ID canonique permanent. Les autres IDs historiques du même document sont conservés dans `legacy_ids`.

### Statistiques

La table de clics n’est pas supprimée ni recréée. Elle continue d’enregistrer `season_year`.

Lors des agrégations, les anciens IDs présents dans l’historique sont remappés vers l’ID canonique permanent puis additionnés. Les alias ne créent pas de lignes supplémentaires dans l’écran des statistiques.

Les filtres restent :

- 7 jours ;
- 30 jours ;
- saison sélectionnée ;
- toutes saisons.

### Duplication d’année

Dupliquer une saison ne duplique plus la bibliothèque et n’attribue plus de nouveaux IDs.

Seule la configuration annuelle d’affichage est copiée vers la nouvelle année.

### Sauvegarde sécurisée

`Parcs_HT_Save_Integrity` délègue désormais la persistance des guides au writer v4 `Parcs_HT_Pedagogical_Guides::persist_admin_value()`.

Le handler relit les données enregistrées avant de confirmer la sauvegarde.

### Administration canonique

- l’ancien menu et l’ancien panneau Guides ne sont plus enregistrés ;
- Aperçu dispose d’un vrai écran dédié ;
- Shortcodes dispose d’un vrai écran dédié ;
- les anciennes URL Guides, Devis groupes, Pop-up et Calendrier de l’Avent redirigent vers leurs écrans actuels ;
- les paramètres utiles des anciennes URL Communication sont conservés pendant la redirection ;
- une ancienne URL d’onglet non reconnue revient vers la Vue d’ensemble au lieu d’afficher le grand écran historique ;
- le lien manuel vers les réglages historiques est supprimé ;
- les slugs de sous-menu sont dédupliqués avant réordonnancement.

Le code historique nécessaire à la compatibilité des données et des anciennes URL n’est pas supprimé aveuglément : il reste non exposé lorsque d’autres composants en dépendent encore.

## Version et cache d’assets

Le plugin et `PARCS_HT_VERSION` passent de 1.19.3 à 1.19.4.

Les assets administratifs sont déjà versionnés avec `PARCS_HT_VERSION`. Le changement de version renouvelle donc leurs URL de cache. Le mécanisme existant de purge LiteSpeed reste inchangé.

## Données explicitement préservées

- `parcs_ht_settings` : aucun changement de schéma ;
- shortcodes publics Guides : inchangés ;
- table de statistiques des guides : conservée ;
- statistiques historiques : conservées et remappées lors de l’affichage ;
- réglages d’apparence des guides : conservés ;
- architecture actuelle Groupes / Communication : conservée.

## Vérification

La branche ajoute et met à jour des contrats de régression pour contrôler :

- le stockage v4 bibliothèque + affichage annuel ;
- la stabilité des IDs ;
- la conservation des statistiques et des alias ;
- l’indépendance des états 2026 / 2027 ;
- l’absence d’enregistrement de l’ancien panneau Guides ;
- l’absence de pont Aperçu / Shortcodes vers le grand écran historique ;
- les redirections canoniques des anciennes URL.

La validation finale est assurée par la CI GitHub du dépôt : syntaxe PHP, matrice PHP 7.4 / 8.1 / 8.2 / 8.3, tests métier et de régression, tests JavaScript, paquet de production et WordPress Plugin Check.
