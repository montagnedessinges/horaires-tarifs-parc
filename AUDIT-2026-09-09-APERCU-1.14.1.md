# Refonte de l’onglet Aperçu — préparation 1.14.1

Date : 9 septembre 2026

## Objectif

Réduire fortement le coût et la longueur de l’onglet **Aperçu** avant le développement du Calendrier de l’Avent.

La version précédente calculait au chargement de la page tous les aperçus du registre et leurs trois variantes FR / EN / DE. Avec 12 modules, cela représentait 36 rendus préparés même si l’administrateur n’en consultait qu’un seul.

## Décisions validées

- conserver le champ global **Date à tester** déjà présent dans l’onglet Aperçu ;
- ajouter un champ global **Heure à tester** ;
- présenter les shortcodes dans une navigation compacte ;
- ne charger qu’un seul shortcode à la fois ;
- ne charger qu’une seule langue à la fois parmi FR / EN / DE ;
- le bouton **Mettre à jour l’aperçu** recharge uniquement l’aperçu sélectionné et non toute la page d’administration ;
- le bouton global **Afficher le résultat** réutilise la date et l’heure choisies pour recharger l’aperçu sélectionné ;
- conserver le choix de couleur de fond de l’aperçu ;
- utiliser le vrai moteur public dans un cadre d’aperçu isolé afin que les composants dynamiques se comportent comme sur le site ;
- ne pas modifier les réglages, horaires, tarifs, devis ou contenus des deux parcs.

## Architecture retenue

`Parcs_HT_Admin_Shortcode_Preview` ne génère plus tous les rendus dans `admin_footer`.

L’administration reçoit seulement la liste légère issue de `Parcs_HT_Shortcode_Registry::public_rows()`. Lorsqu’un module et une langue sont sélectionnés, une iframe d’administration protégée par nonce et capacité `manage_options` demande uniquement ce rendu.

Le cadre charge les vrais assets publics `frontend.css`, `frontend.js` et, lorsque nécessaire, `pedagogical-guides.css`.

La date et l’heure simulées sont converties dans le fuseau du parc côté serveur puis utilisées uniquement à l’intérieur de l’iframe. La date réelle du site et les données publiques ne sont jamais modifiées.

## Sécurité

- endpoint réservé aux administrateurs ;
- nonce obligatoire ;
- shortcode demandé validé contre le registre central ;
- langue limitée à `fr`, `en`, `de` ;
- date, heure, saison et couleur validées avant utilisation ;
- aucune donnée enregistrée par l’aperçu.

## Lien avec le Calendrier de l’Avent

Cette refonte est la base du futur aperçu du shortcode `[parc_calendrier_avent]`. Le module Avent devra réutiliser ce même contrôle global date + heure : par exemple, tester le 24 décembre à une heure donnée devra afficher exactement l’état du calendrier correspondant à cet instant, sans système de simulation parallèle.

Le Calendrier de l’Avent n’est pas développé dans la 1.14.1.
