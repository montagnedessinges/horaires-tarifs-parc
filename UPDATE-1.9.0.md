# Mise à jour 1.9.0 — Vérification événementielle

## Objectif

Remplacer la supervision quotidienne historique par un contrôle utile uniquement lorsqu'il y a une raison de vérifier.

## Déclenchement

Le contrôle s'exécute :

1. après un enregistrement de configuration dans l'extension ;
2. une seule fois lorsqu'une nouvelle version de l'extension est détectée dans l'administration ;
3. à la demande via le bouton de test manuel.

Il n'existe plus de contrôle quotidien récurrent.

## Ce qui est vérifié

- cohérence globale de la saison ;
- chevauchements d'horaires classiques ;
- chevauchements et priorités des exceptions ;
- créneau 1 et créneau 2 complets et ordonnés ;
- cohérence des dernières entrées ;
- présence des composants utilisés sur l'accueil : horaire accueil, statut et horaire d'en-tête ;
- présence du bloc Aujourd'hui utilisé dans Horaires & Tarifs ;
- rendus FR, EN et DE ;
- présence d'un contenu FR, EN et DE pour chaque pop-up actif.

## Langue des pop-up

La langue du navigateur ne doit pas choisir le contenu du pop-up.

Ordre canonique :

1. qTranslate-XT via `qtranxf_getLanguage()` : langue de la page visitée ;
2. fallback sur la locale WordPress ;
3. fallback français si nécessaire.

Un test CI `tests/popup-language-contract.php` bloque une release si `navigator.language` / `navigator.languages` sont utilisés pour sélectionner la langue du pop-up ou si le lien avec le moteur canonique est cassé.

## Résultat

Le dernier résultat est mémorisé dans `parcs_ht_verification_result` et reste valable jusqu'au prochain contrôle. L'administration affiche un état OK ou une anomalie avec les erreurs principales et permet de relancer le test manuellement.

Santé du site reprend également le dernier résultat.

## Performance

Aucun timer, aucune requête quotidienne et aucun test n'est ajouté aux visites publiques. Le moteur de vérification est chargé uniquement dans les contextes d'administration / cron déjà réservés à la supervision.

## Ancien cron

Le hook historique `parcs_ht_daily_health_check` est automatiquement désinscrit. Il est conservé comme constante uniquement afin de nettoyer les tâches éventuellement encore présentes après mise à jour.
