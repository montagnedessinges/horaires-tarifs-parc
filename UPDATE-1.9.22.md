# Mise à jour 1.9.22 — sauvegarde des formulaires de devis par langue

Date : 31 août 2026

## Problème constaté

Dans l’écran « Devis par langue », une modification du shortcode Contact Form 7 français, anglais ou allemand pouvait donner l’impression de ne pas être enregistrée : l’ancien formulaire ou le formulaire général de secours réapparaissait. Le circuit de sauvegarde de cette page est indépendant du grand formulaire principal de l’extension et n’était donc pas couvert par les protections ajoutées en 1.9.20 et 1.9.21.

## Correction

- Les trois valeurs FR / EN / DE sont de nouveau récupérées directement depuis le POST puis nettoyées et validées individuellement.
- Un shortcode CF7 invalide provoque une erreur explicite au lieu d’être transformé silencieusement en valeur vide, ce qui pouvait faire réapparaître le formulaire général et donner l’impression que l’ancienne valeur avait été restaurée.
- Après `update_option`, la valeur stockée est relue et comparée à la valeur demandée. La confirmation de sauvegarde n’est affichée que si l’écriture est réellement confirmée.
- Le cache LiteSpeed est purgé après modification afin que le nouveau formulaire soit visible immédiatement sur le site public.

## Non-régression

Le workflow exécute désormais `tests/quote-language-save-contract.php` sur PHP 7.4, 8.1, 8.2 et 8.3. Le test vérifie le circuit de sauvegarde dédié, le refus explicite d’un shortcode invalide, la relecture de l’option enregistrée et la purge du cache public.

## Périmètre

Aucun tarif, horaire, saison, événement, exception, formulaire CF7 lui-même ni autre réglage du parc n’est remplacé. La modification concerne uniquement la façon dont les shortcodes CF7 FR / EN / DE sont enregistrés dans l’extension.
