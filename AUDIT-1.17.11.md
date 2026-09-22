# Audit correctif 1.17.11 — sauvegardes et contexte d’année

## Objet

La 1.17.11 corrige la régression d’administration signalée après la refonte 1.17.x : une sauvegarde pouvait enregistrer puis afficher une erreur de sécurité, ou remplacer un bloc annuel complet par des données vides lors d’une simple modification.

Cette version ne réécrit pas les moteurs publics ni le schéma de données. Elle corrige la chaîne d’administration autour des moteurs et sanitizers canoniques existants.

## Causes racines vérifiées

### 1. Transport JSON global introduit en 1.17.10

La 1.17.10 avait ajouté `Parcs_HT_Admin_Save_Guard_11710` et `admin-save-guard-11710.js`.

Ce mécanisme :

- interceptait des formulaires d’administration hétérogènes ;
- reconstruisait leur hiérarchie de champs en JavaScript ;
- remplaçait le `FormData` natif par un snapshot JSON ;
- reconstruisait ensuite `$_POST` côté serveur.

Cette architecture contredisait le principe déjà documenté par `Parcs_HT_Save_Integrity` : ne pas réécrire globalement les valeurs du formulaire au moment du submit.

Le garde global ne connaissait pas non plus toutes les variantes de nonce des handlers spécialisés, notamment les nonces suffixés par l’année. Cela pouvait provoquer un écran WordPress de lien expiré/invalide alors que le handler métier avait sa propre règle de sécurité.

### 2. Collision entre l’ancien `admin.js` et le nouvel écran Périodes 1.17.4

Le moteur historique contient une optimisation utile à l’ancien grand formulaire : lorsqu’un bouton nommé `htp_save_active` est utilisé, `admin.js` recherche `[data-htp-active-tab-input]` puis désactive les champs des autres sections avant sérialisation.

Le formulaire Périodes 1.17.4 réutilise un bouton `htp_save_active` mais ne possède plus ce contexte d’onglet historique. L’ancien JavaScript peut donc considérer qu’aucune section n’est active et désactiver tous les contrôles contenus dans les `section.htp-card`.

En parallèle, les champs cachés `settings[_complete][holidays]`, `settings[_complete][exceptions]` et `settings[_complete][domain_rules]` restent envoyés. Le serveur reçoit donc une déclaration « section complète » avec les données de section absentes, ce qui peut être interprété comme une suppression volontaire.

Ce mécanisme correspond au symptôme observé : une première configuration existe, une modification est enregistrée, puis tout le bloc réapparaît vide.

### 3. Redirections de transition encore actives

Plusieurs nouveaux écrans 1.17.x utilisent encore le sanitizer historique `parcs_ht_save`. Après sauvegarde, celui-ci renvoie vers un ancien onglet, puis un routeur de compatibilité renvoie vers le nouvel écran.

Cette double navigation n’est pas la cause de la perte de données, mais elle rend le parcours fragile et explique le changement de page visible après un clic sur Enregistrer.

### 4. Contexte d’année ambigu

Chaque écran avait sa propre logique de repli vers `active_season_year` ou la première saison trouvée. Lorsqu’une URL perdait `season=2027`, l’administration pouvait donc revenir sur 2026.

Le champ historique `published` était aussi encore utilisé pour afficher `· brouillon` dans le sélecteur commun alors que la visibilité publique est désormais pilotée module par module.

## Correctifs 1.17.11

### Retour au POST WordPress natif

Les fichiers de reconstruction globale 1.17.10 sont retirés :

- `includes/class-parcs-ht-admin-save-guard-11710.php` ;
- `assets/admin-save-guard-11710.js`.

Ils sont remplacés par une couche limitée à l’intégrité et au routage :

- `includes/class-parcs-ht-admin-save-11711.php` ;
- `assets/admin-save-11711.js`.

Aucun `FormData` n’est effacé ou reconstruit, aucun snapshot JSON n’est décodé en `$_POST`.

### Protection contre les POST tronqués

Pour les écrans encore basés sur `parcs_ht_save`, le JavaScript ajoute un marqueur `parcs_ht_11711_complete=1` en toute fin du formulaire lors du submit.

Si PHP coupe les variables avant ce marqueur, le handler de garde s’arrête avant le writer canonique et affiche qu’aucune donnée n’a été modifiée.

Le mécanisme ne transforme aucune valeur et ne change pas le nonce métier.

### Neutralisation ciblée de l’ancien scoped-save

Sur un formulaire métier sans `[data-htp-active-tab-input]`, si le bouton submit s’appelle encore `htp_save_active`, la couche 1.17.11 :

- conserve le signal `htp_save_active=1` dans un champ caché pour le serveur ;
- neutralise uniquement le nom du bouton pour empêcher l’ancien `admin.js` de désactiver toutes les sections.

L’ancien grand formulaire conserve son comportement de sauvegarde d’onglet car il possède toujours son vrai champ `data-htp-active-tab-input`.

### Retour direct vers l’écran métier

Après succès du sanitizer historique, les écrans suivants sont renvoyés directement vers leur page canonique :

- Périodes / événements / exceptions / accès limité ;
- Tarifs visiteurs ;
- Groupes ;
- Pop-up.

Les handlers déjà dédiés — Horaires, réglages Groupes, Devis, Guides, Administration générale, Avent — restent inchangés et gardent leur redirection propre.

### Année administrée persistante

`Parcs_HT_Admin_Year_Context` mémorise désormais l’année choisie dans une préférence utilisateur `parcs_ht_admin_year`.

Cette valeur sert uniquement à la navigation d’administration. Elle ne modifie pas l’état public de l’année.

Ordre de repli :

1. année explicitement choisie / mémorisée ;
2. année civile courante si elle existe ;
3. ancien `active_season_year` pour compatibilité ;
4. dernière année disponible.

Les pages non annuelles (Pop-up, Avent, Contenus & traductions, Mises à jour) n’héritent pas de ce contexte.

### Suppression du faux état `brouillon`

Le sélecteur commun affiche désormais `Année administrée : 2026 / 2027` sans déduire un état global à partir de `published`.

Les clés historiques restent conservées pour compatibilité des moteurs qui les utilisent encore ; aucune migration destructive n’est effectuée dans cette corrective.

## Compatibilité conservée

- Aucun changement de schéma de `parcs_ht_settings`.
- Aucun moteur public calendrier/tarifs/devis réécrit.
- Les filtres d’isolation Tarifs visiteurs / Groupes existants restent canoniques.
- Les `_complete` restent utilisés par le sanitizer historique, mais un envoi incomplet est bloqué en amont.
- Les anciennes URLs restent disponibles comme compatibilité de navigation.
- Les sauvegardes de sécurité existantes restent actives.

## Tests 1.17.11

Ajouts :

- `tests/admin-save-11711-contract.php` ;
- `tests/admin-save-11711-runtime.php` ;
- `tests/admin-year-context-11711-runtime.php`.

Le contrat vérifie notamment :

- absence des anciens fichiers de reconstruction JSON 1.17.10 dans le paquet courant ;
- absence de reconstruction `FormData`/JSON dans le nouveau JS ;
- présence du marqueur de fin de formulaire ;
- blocage d’un POST incomplet avant writer ;
- retour direct vers l’écran Périodes 2027 ;
- retour Pop-up sans contexte saisonnier ;
- neutralisation de la collision `htp_save_active` sans casser l’ancien formulaire à onglets ;
- mémorisation de l’année administrée ;
- absence de fuite du contexte annuel vers Pop-up/Avent ;
- disparition de `· brouillon` dans la navigation annuelle commune.

Le contrat 1.17.10 reste historique et se désactive à partir de la 1.17.11, puisque son mécanisme de snapshot a précisément été remplacé.

## État de publication

Ce document décrit la branche corrective 1.17.11. Une branche ou une PR validée ne signifie pas que la version est publiée ni installée sur les sites WordPress. La publication nécessite la validation de la CI puis une fusion/release explicite.
