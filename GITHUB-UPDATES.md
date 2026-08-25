# Mises à jour GitHub privées

Cette extension utilise le dépôt privé canonique :

`montagnedessinges/horaires-tarifs-parc`

## Configuration d'un site WordPress

Le secret GitHub ne doit jamais être ajouté au plugin ni commité dans le dépôt.

Créer un **fine-grained personal access token** limité au dépôt `horaires-tarifs-parc`, avec uniquement la permission **Contents: Read**, puis ajouter dans `wp-config.php`, avant la ligne `/* That's all, stop editing! */` :

```php
define('PARCS_HT_GITHUB_TOKEN', 'github_pat_...');
```

Une variable d'environnement serveur nommée `PARCS_HT_GITHUB_TOKEN` peut être utilisée à la place.

## Fonctionnement

- WordPress interroge la dernière GitHub Release.
- Si sa version est supérieure à `PARCS_HT_VERSION`, WordPress affiche une mise à jour disponible.
- L'installation reste manuelle : l'administrateur clique sur « Mettre à jour maintenant ».
- Le ZIP de release contient toujours le dossier racine `horaires-tarifs-parc` afin de conserver le même plugin WordPress.
- Les options WordPress existantes ne sont jamais remplacées par le package de mise à jour.

## Publication d'une version

Le workflow `.github/workflows/release.yml` s'exécute à chaque push sur `main`.

S'il détecte une nouvelle version dans l'en-tête du plugin et qu'aucun tag `vX.Y.Z` n'existe encore, il :

1. contrôle la syntaxe PHP ;
2. construit `horaires-tarifs-parc.zip` ;
3. crée le tag `vX.Y.Z` ;
4. vérifie l’intégrité du ZIP, son unique dossier racine, son fichier principal et sa version ;
5. calcule et publie le SHA-256 ;
6. crée la GitHub Release correspondante ;
5. joint le ZIP comme asset de la release.

Un commit qui ne change pas le numéro de version ne crée donc pas une nouvelle release.


## Configuration depuis WordPress (depuis 1.7.4)

La clé peut être enregistrée directement dans **Horaires du parc > Mises à jour**. Cette méthode est prévue pour les sites où l’administrateur WordPress n’a pas accès à l’hébergement. La constante `PARCS_HT_GITHUB_TOKEN` dans `wp-config.php` reste prioritaire si elle est définie.

Le token doit rester limité au dépôt `montagnedessinges/horaires-tarifs-parc` avec la permission `Contents: Read-only`.
