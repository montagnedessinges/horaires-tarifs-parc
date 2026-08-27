# Mise à jour 1.9.1 — moteur partagé, langues et validation

## Objectif
Éviter toute divergence entre l’aperçu WordPress et l’affichage public, fiabiliser les dernières entrées des doubles créneaux et rendre les diagnostics directement exploitables.

## Source de vérité d’affichage
`assets/display-state.js` est le moteur d’état partagé pour les couches de synchronisation publique et l’aperçu d’administration. Il calcule : avant ouverture, ouvert, coupure entre créneaux, après fermeture, texte d’état, plages restantes et dernière entrée du créneau actif.

Ne pas recréer une logique séparée dans l’aperçu. Toute évolution de la logique horaire doit d’abord être intégrée au moteur partagé, puis testée.

## Dernières entrées
Les champs canoniques sont :
- `last_entry_minutes_slot1` pour le créneau 1 ;
- `last_entry_minutes_slot2` pour le créneau 2.

Les anciens champs `last_entry_minutes` / `last_entry_minutes2` ne servent que de fallback de compatibilité.

Cas de référence : 10:00–12:00 / 13:00–17:30 avec 30 minutes sur les deux créneaux => 11:30 puis 17:00.

## Langues publiques
Le français est toujours actif et obligatoire. L’anglais et l’allemand sont activables indépendamment dans « Parc & apparence » via le bloc « Langues publiques actives ».

Les validations de contenu public et de pop-up ne doivent exiger que les langues actives. Exemple Forêt : FR + EN peut être utilisé sans obligation DE. Exemple Montagne : FR + EN + DE.

## Titres obligatoires
Toute période/événement et toute exception activée doit posséder un titre FR pour être identifiable dans l’administration, même si elle n’est pas visible par le public.

Lorsqu’un élément est public (calendrier, marqueur ou pop-up), les titres des autres langues publiques actives deviennent également obligatoires.

## Validation avant enregistrement
`assets/admin-validation.js` bloque l’enregistrement en cas d’erreur structurante détectable immédiatement : titre obligatoire manquant, créneau 1 invalide, créneau 2 incomplet, chevauchement entre créneaux. Les champs concernés sont visuellement signalés.

Le contrôle événementiel serveur reste exécuté après sauvegarde et relit les valeurs réellement persistées.

## Tests
Le workflow doit vérifier `assets/display-state.js`, `assets/admin-validation.js` et exécuter `tests/display-state-engine.js` en plus des tests existants.

## Performance et sécurité
Le moteur partagé est très petit et n’est chargé publiquement que lorsqu’un composant horaire de l’extension a déjà chargé `frontend.js`. La validation supplémentaire est administration uniquement. Aucun contrôle quotidien n’est réintroduit.
