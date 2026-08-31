# Horaires & Tarifs Parc

Extension WordPress commune pour La Montagne des Singes et La Forêt des Singes.

Le dépôt privé contient le code source canonique. Les releases publient un asset `horaires-tarifs-parc.zip` destiné au système natif de mise à jour WordPress.

Les données propres à chaque site restent stockées dans WordPress et ne sont jamais remplacées lors d’une mise à jour du code.

## Travailler avec ChatGPT

Pour reprendre le développement dans une nouvelle conversation, commencer par lire `CHATGPT-CONTEXT.md`. Ce fichier contient les règles de travail, la logique métier des horaires, les précautions de compatibilité entre les deux parcs et le processus de publication des mises à jour GitHub.

## Vérification locale

`composer install` puis `composer lint` pour la sécurité WordPress et la compatibilité PHP.
`pnpm install --frozen-lockfile --ignore-scripts` puis `pnpm test` pour les moteurs JavaScript et les régressions DOM.
`php tests/public-data-regressions.php` pour les protections des devis, colonnes masquées et saisons brouillon.
Le workflow complète ces commandes avec les contrats PHP, la génération PDF et les tests du paquet nettoyé sous PHP 7.4, 8.1, 8.2 et 8.3.

L’audit initial et le détail des correctifs 1.9.19 sont conservés dans `AUDIT-2026-08-31.md`.
