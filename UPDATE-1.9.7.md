# Mise à jour 1.9.7 — Devis groupes français par année

Date : 30/08/2026

## Objectif

Centraliser dans l’extension le calcul du formulaire de devis groupes français, tout en conservant un seul formulaire Contact Form 7. L’année du tarif est déterminée par le champ `visite`.

## Périmètre validé

- Pour le moment, seul le formulaire français est géré par ce nouveau module.
- Formulaire CF7 français actuel par défaut : ID `806`.
- Aucun formulaire EN/DE n’est activé par l’extension dans cette version.
- Les horaires, calendriers, exceptions, pop-up et tarifs individuels existants ne sont pas modifiés.
- Le moteur de devis est désactivé par défaut afin d’éviter un conflit tant que l’ancien calcul reste dans `assets/js/script.js` du thème.

## Administration

Un sous-menu `Horaires du parc > Devis groupes` permet de régler :

- activation du moteur ;
- ID du formulaire CF7 ;
- nom technique du champ date de visite ;
- nom technique du champ type de groupe ;
- valeurs françaises des deux types de groupe ;
- tarifs groupes par année ;
- publication indépendante de chaque année de tarifs groupes ;
- règle du nombre d’enfants donnant droit à un adulte gratuit.

## Valeurs initiales 2026

- Enfant : 6,00 €
- Adulte payant : 8,50 €
- Personne en situation de handicap : 6,00 €
- Accompagnateur : 6,00 €
- 1 adulte gratuit par tranche complète de 10 enfants, sans jamais dépasser le nombre réel d’adultes.

## Sécurité fonctionnelle entre années

Le moteur lit l’année de la date de visite au format CF7 `YYYY-MM-DD`. Il n’utilise jamais automatiquement les tarifs d’une autre année.

Si une année n’existe pas ou n’est pas publiée, les totaux sont vidés et le formulaire affiche : `Les tarifs groupes ne sont pas encore disponibles pour cette année.`

Cela permet de préparer 2027 sans modifier les calculs 2026.

## Migration depuis le thème

Le code de calcul 2026 ajouté au thème reste la base stable externe et est conservé dans GitHub. Pour éviter deux moteurs simultanés :

1. installer la 1.9.7 ;
2. configurer et vérifier les tarifs dans `Devis groupes` ;
3. retirer uniquement le bloc de calcul des devis du `assets/js/script.js` du thème ;
4. activer ensuite le moteur de devis de l’extension ;
5. tester le formulaire FR avec une date 2026 ;
6. ne publier 2027 qu’après saisie et validation des tarifs groupes 2027.

## Test de référence 2026

Avec une date de visite 2026, 20 enfants et 3 adultes :

- 2 adultes gratuits ;
- 1 adulte payant ;
- enfants : 120,00 € ;
- adultes : 8,50 € ;
- total : 128,50 €.

Avec 10 personnes en situation de handicap et 2 accompagnateurs : total 72,00 €.

## Limite volontaire de 1.9.7

Le calcul est centralisé côté navigateur dans l’extension. Une future version pourra ajouter une validation/recalcul serveur Contact Form 7 avant génération des e-mails/PDF. Cette évolution ne doit pas être ajoutée sans vérifier précisément le générateur PDF et les dépendances CF7 existantes.
