# Mise à jour 1.9.23 — sauvegarde unifiée du module Devis

Date : 31 août 2026.

## Problème corrigé

Après les correctifs 1.9.20 à 1.9.22, certains champs pouvaient encore sembler revenir à leur ancienne valeur. Deux causes restaient distinctes :

- les formulaires Contact Form 7 FR / EN / DE étaient enregistrés dans une option WordPress séparée du réglage principal de l’extension ;
- lorsqu’une liste du module Devis était entièrement supprimée (message important, lien rapide, bloc complémentaire ou accordéon), le formulaire HTML n’envoyait plus aucune ligne pour cette liste et la fusion de sécurité pouvait alors réinjecter l’ancienne liste.

## Nouvelle règle

Le réglage principal `parcs_ht_settings` devient la source active de vérité pour le module Devis, y compris pour les shortcodes CF7 par langue.

L’ancienne option `parcs_ht_quote_language_shortcodes` n’est plus utilisée comme stockage actif. Elle reste uniquement comme source de migration et de retour arrière tant que les données n’ont pas encore été copiées dans le réglage principal.

## Suppressions

Avant le sanitizer principal, une liste du module Devis absente d’un POST pourtant marqué comme complet est maintenant transformée en suppression explicite. Cela empêche `array_replace_recursive()` de restaurer silencieusement la valeur précédente.

Les suppressions complètes sont couvertes pour :

- messages importants ;
- liens rapides ;
- blocs complémentaires ;
- accordéons.

## Formulaires par langue

Les shortcodes FR / EN / DE sont stockés sous `quote_page.form_shortcodes` dans `parcs_ht_settings`.

Une migration non destructive copie les anciennes valeurs lors du premier passage dans l’administration si cette nouvelle clé n’existe pas encore. Un champ volontairement vide reste vide et utilise le formulaire général en secours.

Lors d’une sauvegarde du formulaire principal de l’extension, les shortcodes par langue sont conservés explicitement tant que ces champs ne sont pas présents dans le formulaire principal, afin qu’aucune modification d’un autre réglage ne puisse les effacer.

## Tests

Le contrat automatique du devis vérifie désormais :

- que les formulaires par langue utilisent `Parcs_HT_Defaults::OPTION` ;
- qu’ils n’écrivent plus dans l’ancienne option séparée ;
- que la migration de l’ancienne option existe ;
- que les suppressions complètes sont transformées en suppression explicite avant la sauvegarde ;
- que les shortcodes par langue sont préservés lors des autres sauvegardes du module Devis.
