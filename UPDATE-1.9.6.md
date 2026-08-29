# Mise à jour 1.9.6 — affichage de la prochaine ouverture

## Incident observé

Le 29 août 2026 à 20:09, après la fermeture du parc, la page d’accueil de La Montagne des Singes affichait correctement le statut « À demain ! », mais la ligne secondaire contenait une date technique brute :

`2026-08-30 · 10h`

La capture utilisateur a confirmé que le problème était visible sur mobile en production.

## Cause confirmée

La source du problème était `assets/display-state.js`, dans le moteur d’état partagé introduit pour synchroniser la page d’accueil et le bloc « Aujourd’hui ».

Dans la phase `after`, le moteur construisait volontairement `hoursText` avec :

`next.date + ' · ' + heure`

`next.date` est une date ISO interne (`YYYY-MM-DD`) destinée au calcul, pas à l’affichage public. Le thème n’était donc pas responsable de cette date brute.

## Correction

À partir de la 1.9.6 :

- si la prochaine ouverture est le lendemain, le statut reste « À demain ! » et la ligne secondaire devient « Ouverture à 10h » ;
- si la prochaine ouverture est plus éloignée, la date est formatée dans la langue de la page avec `Intl.DateTimeFormat`, par exemple « 5 septembre · 10h » ;
- aucune date ISO brute ne doit être rendue dans les composants publics pour la prochaine ouverture ;
- la logique reste centralisée dans `assets/display-state.js` afin de conserver la parité entre l’accueil et le bloc « Aujourd’hui ».

Les équivalents EN/DE utilisent le même mécanisme de date localisée et les libellés déjà définis par le moteur.

## Non-régression

`tests/display-state-engine.js` couvre désormais :

- l’ouverture normale ;
- la coupure entre deux créneaux ;
- les dernières entrées par créneau ;
- le cas après fermeture avec ouverture le lendemain ;
- l’absence de date ISO pour le lendemain ;
- le cas d’une prochaine ouverture plusieurs jours plus tard avec date lisible.

## Règle durable

Les dates ISO (`YYYY-MM-DD`) peuvent rester dans les structures internes et les calculs, mais ne doivent jamais être affichées directement aux visiteurs. Toute date publique doit passer par un formatage adapté à la langue et au contexte.
