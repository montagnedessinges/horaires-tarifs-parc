# Mise à jour 1.9.18 — multilingue devis

Date : 30/08/2026

## Périmètre fonctionnel validé

Cette version corrige les éléments multilingues discutés après vérification de l’onglet « Devis groupe ».

### Accès au devis

Les deux messages publics suivants ne doivent plus être uniquement français :

- date où le parc est fermé ;
- tarifs indisponibles.

Chaque message dispose désormais de trois variantes administrables : FR, EN et DE. Le champ « Contact ou lien » reste commun aux trois langues.

Compatibilité : lorsqu’une installation possède encore les anciennes clés monolingues `closed_message` et `unavailable_message`, leur contenu est repris comme version française. Les traductions EN/DE disposent de valeurs par défaut. Lorsqu’une traduction est vide, le rendu public se rabat sur le français.

La langue publique est déterminée à partir du shortcode de devis FR/EN/DE présent sur la page, puis du moteur de langue existant du plugin.

### Accessibilité du calendrier

Les boutons de navigation du calendrier avaient encore des `aria-label` français codés dans le rendu HTML. Un petit script chargé uniquement lorsqu’un module public du plugin est déjà présent adapte maintenant :

- FR : « Mois précédent » / « Mois suivant » ;
- EN : « Previous month » / « Next month » ;
- DE : « Vorheriger Monat » / « Nächster Monat ».

Aucun script supplémentaire n’est chargé sur une page ne chargeant pas déjà le frontend du plugin.

## Éléments volontairement inchangés

Cette version ne modifie pas :

- le moteur de calcul des devis groupes ;
- la logique annuelle des tarifs de devis ;
- la grille 2026/2027 ;
- le choix de date avant Contact Form 7 ;
- le préremplissage du champ `visite` ;
- les champs techniques `devisannee`, `tarifenfant`, `tarifadulte`, `tarifhandicap`, `tarifaccompagnateur` ;
- le modèle PDF dynamique ;
- les formulaires CF7 eux-mêmes ;
- les e-mails/PDF EN et DE, qui restent des configurations externes à WordPress/CF7 et doivent conserver exactement les mêmes noms techniques de champs que le FR ;
- la gestion des archives d’années, encore à concevoir ;
- la simplification éditoriale de la page de demande de devis, encore à finaliser avant modification.

## Tests

Un contrat `tests/quote-gate-language-contract.php` vérifie la présence des six clés de message FR/EN/DE et leur sélection publique par langue.

Le workflow contrôle désormais explicitement la syntaxe de `assets/admin-groups.js` et `assets/frontend-i18n.js` en plus du contrôle global du paquet de production.

## Base de version

- Version précédente publiée et stable : 1.9.17.
- Nouvelle version fonctionnelle : 1.9.18.
- Les assets de la release 1.9.17 restent immuables.
