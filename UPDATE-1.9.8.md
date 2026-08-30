# Mise à jour 1.9.8 — Correctif urgent devis groupes

Date : 30/08/2026

## Contexte

La version 1.9.7 a introduit un nouveau moteur de calcul des devis groupes, mais il était désactivé par défaut et dépendait d’un identifiant Contact Form 7 configuré à `806`.

Sur le site actuellement observé, le shortcode du formulaire français est :

`[contact-form-7 id="6c681fb" title="Formulaire devis"]`

Après retrait de l’ancien bloc de calcul 2026 dans le `assets/js/script.js` du thème, plus aucun moteur ne calculait donc les montants sur le formulaire public.

## Correction 1.9.8

Le moteur de devis est désormais chargé automatiquement sur le site public. Il ne dépend plus d’un interrupteur d’activation ni d’un ID CF7 figé.

Le JavaScript détecte uniquement les formulaires Contact Form 7 qui contiennent les champs fonctionnels attendus :

- `visite`
- `groupedevis`
- au moins un des champs de quantité de groupe (`nbrenfants`, `nbradultes`, `nbrpersohandicape`, `nbraccompa`)

Cette détection permet de fonctionner avec les anciens ID numériques comme avec les nouveaux ID alphanumériques Contact Form 7.

## Tarifs 2026 conservés

- Enfant : 6,00 €
- Adulte payant : 8,50 €
- Personne en situation de handicap : 6,00 €
- Accompagnateur : 6,00 €
- Gratuité : 1 adulte gratuit par tranche complète de 10 enfants, sans dépasser le nombre réel d’adultes.

## Champs PDF alimentés

Le moteur continue de renseigner :

- `devisannee`
- `tarifenfant`
- `tarifadulte`
- `tarifhandicap`
- `tarifaccompagnateur`
- `nbradultgratuit`
- `nbradultpayant`
- `nbrprixenfants`
- `nbrprixadultes`
- `totalprixscolaire`
- `totalprixhandicape`

## Procédure après installation

1. Installer la version 1.9.8.
2. Ne pas remettre l’ancien moteur de calcul 2026 dans le script du thème.
3. Purger les caches WordPress / cache serveur / cache navigateur si nécessaire.
4. Tester le formulaire français avec une date 2026.
5. Test de référence : 20 enfants + 3 adultes = 2 adultes gratuits, 1 adulte payant, 120,00 € enfants, 8,50 € adultes, total 128,50 €.
6. Vérifier ensuite l’e-mail et le PDF généré.

## Important

Le modèle PDF dynamique et les cinq champs techniques cachés du formulaire CF7 restent nécessaires pour que l’année et les prix unitaires soient reportés correctement dans le PDF.

La gestion 2027 ne doit être activée qu’après saisie et publication des vrais tarifs groupes 2027 et validation d’un test complet formulaire → e-mail → PDF.
