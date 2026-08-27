# Correction issue de l’audit — version 1.8.8

## Cause confirmée

Le problème d’affichage observé avec les horaires exceptionnels venait notamment du fait qu’un composant d’en-tête pouvait conserver la classe `is-open` dès lors que la journée possédait un horaire, même lorsque l’heure réelle était située avant l’ouverture ou après la fermeture.

Avec un horaire exceptionnel, cela pouvait produire un état visuel incohérent : une prochaine ouverture était déjà affichée mais le bloc restait marqué « ouvert ».

## Correction appliquée

La couche de synchronisation applique désormais les classes d’état à partir de la phase réelle de la journée :

- `before` : avant ouverture, fermé à l’instant T ;
- `open` : pendant un créneau, ouvert ;
- `gap` : entre deux créneaux, fermé à l’instant T avec réouverture ;
- `after` : après la dernière fermeture, fermé à l’instant T.

Cette logique est identique pour les horaires normaux et exceptionnels.

## Test ajouté

`tests/status-sync-engine.js` couvre un horaire exceptionnel `09:00–17:30` :

- 08:30 → `Ouverture à 9h`, jamais ouvert ;
- 10:00 → `OUVERT` ;
- 17:42 → `À demain !`, jamais ouvert ;
- journée exceptionnelle à deux créneaux → `Réouverture à 13h` entre les deux créneaux.

Le workflow exécute désormais ce test et vérifie aussi la syntaxe de `assets/status-sync.js`.

## Sécurité des releases

Le workflow ne remplace plus les fichiers d’une release existante avec `--clobber`. Une release déjà publiée reste immuable et disponible comme point de retour arrière.

## À surveiller après installation

Après installation de la 1.8.8 sur les deux sites, vérifier les quatre états dans les blocs d’accueil et d’en-tête, en particulier lorsqu’un horaire exceptionnel est actif. Si un texte « Ouvert » reste visible alors que les classes et le shortcode sont corrects, inspecter alors le bloc thème/Elementor correspondant : ce serait un second affichage extérieur au moteur de l’extension.
