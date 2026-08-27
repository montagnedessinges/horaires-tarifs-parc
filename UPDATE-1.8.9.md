# Mise à jour 1.8.9 — Simulateur d’aperçu

## Objectif

Permettre de vérifier visuellement dans WordPress ce que l’extension doit afficher pour une date et une heure choisies, sans attendre l’heure réelle ni tester directement sur le site public.

## Changements

- ajout de `assets/admin-preview-enhanced.js`, chargé uniquement dans l’administration de l’extension ;
- ajout d’un champ « Heure à tester » dans l’onglet Aperçu ;
- simulation du bloc de la page d’accueil ;
- simulation du bloc « Aujourd’hui » de la page Horaires & Tarifs ;
- affichage de la règle utilisée : horaire classique, horaire exceptionnel, fermeture exceptionnelle ou aucun horaire ;
- calcul de la phase réelle : avant ouverture, ouvert, coupure entre deux créneaux, après fermeture ;
- affichage de la dernière entrée correspondant au créneau actif ;
- détection du premier pop-up configuré et actif pour la date simulée ;
- retrait du bouton « Enregistrer tous les réglages » de l’interface pour conserver uniquement la sauvegarde de l’onglet actif.

## Règle de sauvegarde

La sauvegarde de l’onglet actif reste le mécanisme recommandé et sécurisé. Le simulateur travaille sur le moteur de données enregistré : lorsqu’un réglage horaire vient d’être modifié, enregistrer d’abord l’onglet concerné puis ouvrir l’onglet Aperçu.

## Performance

Le nouveau script n’est chargé que sur la page d’administration `Horaires du parc`. Il n’est pas chargé sur les pages publiques et n’ajoute donc aucune charge au site côté visiteurs.

## Tests / CI

Le workflow GitHub exécute `node --check assets/admin-preview-enhanced.js` avant la création de la release. Les contrôles PHP multi-versions, les tests métier existants, les tests de statut dynamique et Plugin Check restent actifs.

## Fichiers concernés

- `horaires-tarifs-parc.php`
- `assets/admin-preview-enhanced.js`
- `.github/workflows/release.yml`
- `CHANGELOG.md`

## Retour arrière

La version 1.8.8 reste conservée comme release distincte et ne doit pas être écrasée. En cas de régression de l’administration, revenir à la dernière release stable plutôt que modifier une release déjà publiée.
