# Mise à jour 1.9.9 — formulaires de devis par langue

Date : 30/08/2026

## Objectif
Permettre de choisir un shortcode Contact Form 7 différent pour le français, l’anglais et l’allemand sans casser le formulaire français actuellement en production.

## Fonctionnement
- Nouvelle page d’administration : `Horaires du parc > Devis par langue`.
- Trois champs indépendants : Français, Anglais, Allemand.
- `[parc_devis_fr]` et `[parc_devis_groupe_fr]` utilisent le formulaire FR.
- `[parc_devis_en]` et `[parc_devis_groupe_en]` utilisent le formulaire EN.
- `[parc_devis_de]` et `[parc_devis_groupe_de]` utilisent le formulaire DE.
- Si le shortcode d’une langue est vide, le shortcode général déjà enregistré dans `Devis groupe` est utilisé en secours. Cela protège le fonctionnement FR existant pendant la transition.
- Les shortcodes génériques `[parc_devis]` et `[parc_devis_groupe]` conservent leur comportement actuel.

## Contexte
La 1.9.8 a rétabli en urgence le moteur de calcul des devis après retrait de l’ancien moteur 2026 du `script.js` du thème. La 1.9.9 ajoute uniquement la sélection du formulaire CF7 par langue et ne remplace pas la logique tarifaire 1.9.8.

## Test après installation
1. Mettre à jour vers 1.9.9.
2. Ouvrir `Horaires du parc > Devis par langue`.
3. Renseigner au minimum le shortcode CF7 français si souhaité. Un champ FR vide conserve le formulaire général actuel en secours.
4. Tester `[parc_devis_groupe_fr]`.
5. Renseigner ensuite les formulaires EN et DE lorsqu’ils sont prêts et tester leurs shortcodes dédiés.
