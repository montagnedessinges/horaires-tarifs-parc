# Correctif préparé — bascule du calendrier public

## Constat

Le 13 novembre 2026, les deux sites publics chargent les assets 1.21.5. La Forêt transmet uniquement la saison 2026 dans `ParcsHTPData.settings.seasons`, tandis que la Montagne transmet uniquement 2027. Une requête de diagnostic distincte, avec demande de non-cache, reproduit le résultat de la Forêt. Les captures d’administration montrent 2026 désactivé et 2027 activé depuis le 12 novembre.

La configuration brute de production n’a pas été exportée. Le correctif traite un défaut vérifié du pipeline de visibilité, mais son rôle exact dans cet incident reste à confirmer sur une copie du WordPress de la Forêt.

## Défaut et correction

`Defaults::all_settings()` mémorise une projection annuelle après les filtres de lecture des options. L’ancien `Display_Policy::apply_season_visibility()` remplaçait `published` par le seul interrupteur manuel `calendar_visible` et effaçait la date de retrait. Il pouvait donc annuler le résultat du moteur automatique, notamment lorsque les interrupteurs conservaient leurs valeurs de la saison précédente.

Cette projection appelle maintenant `Public_Visibility::module_visible_for_season()`, la même règle que l’administration et les autres modules. La méthode est rendue publique pour permettre cet appel sur la saison déjà disponible, sans nouvelle lecture d’options ni récursion. Les dates sont conservées. Aucun changement de stockage, de sauvegarde, de tarif ou de réglage des parcs.

## Validation

Le test `tests/calendar-year-rollover-runtime.php` utilise les classes réelles Defaults, Display_Policy, Public_Seasons, Public_Visibility et Schedule. Les fonctions WordPress périphériques sont simulées ; ce test ne remplace pas une validation sur un WordPress réel.

69 cas couvrent les deux domaines, les interrupteurs conformes ou opposés à la bascule automatique, la veille/le jour/le lendemain de l’activation, le jour du retrait, les deux ordres de première lecture, le cache de requête, le JSON public, la priorité du retrait et les anciens drapeaux. Un contrôle supplémentaire vérifie que la lecture d’administration reste brute. Les données sauvegardées doivent rester strictement identiques.

Le test est exécuté dans la CI PHP 7.4, 8.1, 8.2 et 8.3, ainsi que sur les sources nettoyées du paquet de production.

## Livraison

Version de distribution : 1.21.6. La publication sur main est autorisée par l’utilisateur ; la release est produite par le workflow après validation. Après validation, vérifier sur une copie de la Forêt que le calendrier, ses détails et le PDF ne proposent que 2027, puis vérifier la Montagne et les pages groupes. Cette préparation ne modifie aucun site en production.
