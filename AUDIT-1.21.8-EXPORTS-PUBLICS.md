# 1.21.8 — retrait des commandes publiques d’export

Décision utilisateur du 17 novembre 2026, issue #134. Base : main 3c51cce23b2865aeae593160d571cf6739b2a7a5 / version 1.21.7.

## Périmètre
Retirer uniquement les commandes publiques du planning et des tarifs. Conserver les signatures des trois helpers de rendu, les appels existants et les moteurs/endpoints PDF/impression. Aucun nouvel écran back-office. Les anciennes URL restent utilisables : il ne s’agit pas d’une restriction d’accès. Aucun changement de données, CSS ou JavaScript.

## Incident 1.21.2
La méthode privée Parcs_HT_Tariff_Display::export_actions avait été supprimée alors que les renderers 1.16.7 et 1.16.8 continuaient de l’invoquer par ReflectionMethod. L’appel vers la méthode absente peut interrompre le shortcode complet. Absence confirmée dans les versions 1.21.2 à 1.21.4 ; fonction restaurée en 1.21.5. La version 1.21.2 incluait également une évolution scolaire distincte (#127). Ne pas réappliquer cette ancienne branche.

## Changement
Les trois helpers renvoient une chaîne vide et restent appelables. Les points d’accès et constructeurs PDF, paramètres, caches, validations et limites de débit restent inchangés.

## Validation prévue dans la PR
- Appels réels par réflexion via les deux renderers, FR/EN/DE, deux années.
- Squelette calendrier et existence des endpoints conservés.
- CI PHP 7.4/8.1/8.2/8.3 sur source et paquet nettoyé.
- WordPress/MySQL réel en CI : 15 shortcodes par scénario MDS/FDS/groupes seuls, comparaison HTML exacte avec la 1.21.7 après retrait des seuls blocs d’export du résultat de référence.
- Génération effective planning PDF et tarifs PDF dans WordPress, conservation des routes publiques et des données.
- Comparaison byte-à-byte des CSS/JS avec la base.

## Limites
Les fixtures WordPress sont synthétiques. Pas de connexion, installation ni test sur les sites de production, leur thème ou leurs caches. La comparaison HTML et des assets n’est pas une capture visuelle sur les sites des parcs. Les résultats effectifs sont à consulter dans la PR et ses workflows avant fusion/publication.
