# Audit complet — Devis groupe / shortcode / déplacement de page

Date : 30/08/2026
Version auditée : 1.9.13
Correctif préparé : 1.9.14

## Symptômes signalés

- Le shortcode Devis groupe ne fonctionne plus correctement après les derniers regroupements d’administration.
- La page du site se décale / bouge au chargement du formulaire de devis.

## Cause principale 1 — formulaire FR / EN / DE non réellement transmis au moteur de rendu

`Parcs_HT_Quote_Languages` remplaçait les shortcodes localisés puis construisait une copie de `$settings` contenant le shortcode Contact Form 7 choisi pour la langue.

Cette copie était passée en quatrième argument à `Parcs_HT_Shortcodes::render()` alors que `render()` n’accepte et n’utilise que trois arguments. Le moteur rechargeait donc `Parcs_HT_Defaults::settings()` et utilisait à nouveau le formulaire général.

Conséquence : les réglages FR / EN / DE pouvaient être enregistrés dans l’administration sans être réellement appliqués au rendu public.

### Correction 1.9.14

- Suppression du remplacement tardif des shortcodes par `Parcs_HT_Quote_Languages`.
- Les shortcodes restent enregistrés par le moteur principal `Parcs_HT_Shortcodes`.
- `Parcs_HT_Quote_Languages` applique désormais le formulaire CF7 de la langue directement aux réglages de la requête avant que le cache de `Parcs_HT_Defaults::settings()` ne soit construit.
- Détection prioritaire des shortcodes explicites `_fr`, `_en`, `_de` présents dans la page ; sinon utilisation de la langue courante du site.
- Un formulaire de langue vide conserve le formulaire général comme secours.

## Cause principale 2 — déplacement visuel du formulaire au chargement

`assets/quote-gate.js` attendait l’exécution JavaScript pour masquer les champs autres que la date.

Le navigateur pouvait donc afficher brièvement le formulaire complet, puis JavaScript masquait la majorité des champs. La hauteur de la page diminuait après le premier rendu, ce qui provoquait un déplacement visuel perceptible (layout shift).

### Correction 1.9.14

- Ajout de `assets/quote-gate.css` pour masquer immédiatement les éléments autres que le champ date tant que le gate n’est pas initialisé.
- Le script ajoute `parcs-ht-gate-ready` seulement après avoir appliqué son état initial.
- Le formulaire complet ne doit plus apparaître puis disparaître au chargement.
- Le changement de hauteur qui survient après que le visiteur choisit volontairement une date reste normal : il correspond à l’ouverture du formulaire complet.

## Cause secondaire 3 — scripts appliqués trop largement aux formulaires CF7

`group-quotes.js` et `quote-gate.js` scannaient tous les formulaires Contact Form 7 de la page (`.wpcf7 form`).

Même si des contrôles de champs existaient, cette portée était trop large et pouvait affecter un autre formulaire partageant des noms de champs similaires.

### Correction 1.9.14

Les deux scripts ne travaillent plus que dans le module rendu par l’extension :

`.parcs-ht-quote .wpcf7 form`

Ils ne doivent plus modifier un formulaire Contact Form 7 extérieur au module Devis groupe.

## Cause secondaire 4 — champs du gate codés en dur

Le moteur de calcul permet de configurer les noms des champs `visit_field` et `group_field`, mais `quote-gate.js` cherchait toujours `visite` et `groupedevis` en dur.

### Correction 1.9.14

`Parcs_HT_Quote_Gate` transmet maintenant `visitField` et `groupField` au JavaScript à partir de `Parcs_HT_Group_Quotes::settings()`.

Le contrôle de date suit donc la même configuration technique que le moteur de calcul.

## Sécurité et calculs

L’audit confirme que le calcul serveur reste prioritaire :

- l’année est recalculée depuis la date de visite ;
- les tarifs publiés de l’année sont relus côté serveur ;
- les champs cachés et totaux envoyés par le navigateur sont réécrits côté serveur ;
- aucune année précédente n’est utilisée en secours si la grille demandée n’est pas publiée.

Ces protections ne sont pas supprimées par le correctif 1.9.14.

## Administration

Les changements d’organisation précédents restent conservés :

- Devis groupe regroupe le contenu, les tarifs de la saison sélectionnée et l’accès au devis ;
- les formulaires FR / EN / DE sont réglés dans le contenu via le sélecteur de langue ;
- les anciennes pages techniques séparées sont retirées du menu visible, sans suppression de leurs données.

## Point à vérifier sur le site réel

Le dépôt permet d’identifier et corriger les causes dans le code, mais il ne donne pas accès au rendu réel WordPress, au thème actif, à Contact Form 7, à son plugin de champs conditionnels ni au générateur PDF en production.

Avant validation définitive, test E2E nécessaire :

1. charger la page FR avec `[parc_devis_groupe_fr]` ;
2. vérifier que le formulaire CF7 français configuré est rendu ;
3. répéter EN et DE ;
4. vérifier qu’au premier affichage seule la date est visible sans flash du formulaire complet ;
5. choisir une date 2026 avec grille publiée et vérifier l’ouverture du formulaire complet ;
6. tester une date fermée ;
7. tester une année sans grille publiée ;
8. vérifier le calcul scolaire et handicap ;
9. vérifier que les autres formulaires CF7 du site ne sont pas modifiés ;
10. vérifier l’envoi, le mail et le PDF.

## Limite technique connue

Le CSS anti-décalage utilise `:has()` afin d’identifier le bloc contenant le champ date avant l’initialisation JavaScript. Les navigateurs modernes le prennent en charge. Le JavaScript reste le repli fonctionnel si ce sélecteur CSS n’est pas disponible, mais un navigateur très ancien peut encore voir un léger flash initial.
