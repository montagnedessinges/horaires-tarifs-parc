# Mise à jour 1.9.5 — nettoyage du paquet de production

## Objectif

Conserver les sources GitHub lisibles et documentées tout en livrant à WordPress un paquet de production minimal, sans commentaires de développement ni documentation interne.

## Principe retenu

- Les commentaires techniques restent dans le dépôt GitHub pour faciliter la maintenance et les audits.
- Le ZIP de production retire les commentaires PHP, JavaScript et CSS au moment du build.
- L’en-tête du fichier principal contenant `Plugin Name` est conservé : WordPress en a besoin pour identifier l’extension.
- Le nettoyage ne modifie jamais les fichiers sources du dépôt ; il agit uniquement dans le répertoire temporaire `build/`.

## Fichiers exclus du ZIP

Le build exclut notamment :

- tous les fichiers Markdown ;
- `.github/` et `.pkg/` ;
- `tests/` et `tools/` ;
- `vendor/` ;
- Composer et les fichiers de configuration de qualité ;
- les audits, roadmaps, historiques de travail et documents de contexte.

## Contrôles avant publication

Après nettoyage, GitHub Actions :

1. revérifie la syntaxe de tous les fichiers PHP du paquet ;
2. revérifie la syntaxe de tous les fichiers JavaScript du paquet ;
3. confirme que l’en-tête WordPress du plugin existe toujours ;
4. vérifie qu’aucun document ou outil de développement n’est présent dans le ZIP ;
5. bloque la release si une attribution explicite de développement telle que ChatGPT, OpenAI, GitHub Copilot ou une mention « généré par IA » est présente dans les sources livrées ;
6. exécute ensuite les contrôles WordPress habituels et calcule le SHA-256 du ZIP.

## Portée

Cette version ne modifie pas la logique métier des horaires, tarifs, saisons, offres ou pop-up. Le changement porte uniquement sur la construction et l’hygiène du paquet installé sur les sites.

## Page d’accueil

Le fichier de thème serveur utilisé pour la page d’accueil n’est pas stocké dans ce dépôt. Son nettoyage doit donc être vérifié séparément à partir du fichier réellement installé sur le site. Ne pas inventer ou reconstruire ce fichier depuis un ancien extrait : utiliser la version de production actuelle comme source.
