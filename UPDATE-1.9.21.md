# Mise à jour 1.9.21

## Problème corrigé

Dans l’onglet « Devis groupe », la suppression d’un « Message important » pouvait sembler fonctionner à l’écran puis l’élément réapparaissait après enregistrement. Le même mécanisme pouvait concerner les listes « Liens rapides », « Blocs complémentaires » et « Accordéons » lorsqu’elles étaient entièrement vidées.

## Cause

Le formulaire signale avec `settings[_complete][quote_page]` que l’onglet Devis a été envoyé intégralement. Malgré ce marqueur, le sanitizer fusionnait encore les données envoyées avec l’ancienne valeur enregistrée via `array_replace_recursive`. Lorsqu’une liste était entièrement supprimée, aucun champ de cette liste n’était présent dans le POST ; la fusion réinjectait donc automatiquement l’ancienne liste.

La désactivation d’un message fonctionnait car la ligne restait présente dans le POST avec `enabled=0`, alors qu’une suppression complète faisait disparaître la clé de la requête.

## Correction

La version 1.9.21 ajoute une couche de sauvegarde ciblée et sécurisée :

- elle ne s’active que sur une sauvegarde authentifiée de l’administration `parcs_ht_save` ;
- elle exige le marqueur `settings[_complete][quote_page]=1`, afin de ne jamais interpréter comme une suppression l’absence de l’onglet Devis lors d’une sauvegarde d’un autre onglet ;
- si une liste du Devis est absente d’un POST complet, elle est explicitement enregistrée comme tableau vide ;
- les quatre listes concernées sont `important_messages`, `quick_links`, `info_blocks` et `accordions` ;
- aucun horaire, tarif, saison, événement, exception, formulaire CF7 ou autre réglage n’est modifié.

## Tests

Un test de non-régression vérifie que la couche est chargée, qu’elle est limitée à l’onglet Devis complet et que les quatre listes peuvent être réellement vidées.

## Contexte

La 1.9.20 reste la correction du choix préalable de date du devis et de la sauvegarde par onglet. La 1.9.21 corrige spécifiquement la suppression définitive des blocs administrables du module Devis groupe.
