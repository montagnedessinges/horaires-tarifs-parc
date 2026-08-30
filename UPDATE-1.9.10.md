# Mise à jour 1.9.10 — devis groupes pilotés par la date

## Décision fonctionnelle
- Une seule grille de tarifs groupes par année.
- Aucun tarif groupe spécial par période.
- Le visiteur choisit sa date de visite, jamais une année tarifaire.
- La date détermine automatiquement la grille 2026, 2027, etc.
- Le choix du type de groupe reste ensuite inchangé : `Groupe` ou `Groupe en situation de handicap`.
- Si l’année n’a pas de grille disponible, aucun devis chiffré ne peut être envoyé et aucun tarif d’une autre année n’est utilisé.

## Sécurité et source de vérité
Le JavaScript conserve le calcul instantané pour l’affichage. Lors de l’envoi Contact Form 7, la version 1.9.10 revérifie côté serveur la date et la disponibilité de la grille, puis recalcule les tarifs et totaux à partir des valeurs enregistrées dans l’extension.

Les champs canoniques réécrits pour le PDF sont :
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

## PDF
Ne pas modifier la mise en page stable validée. Le titre doit rester :

```html
<h1>DEVIS [devisannee]</h1>
```

Ainsi une date 2026 produit `DEVIS 2026` et une date 2027 produit `DEVIS 2027`. Les prix unitaires continuent d’utiliser les mail-tags dynamiques déjà validés.

## Contact Form 7
Pour rendre le parcours logique visuellement, placer le champ `[date* visite]` avant le champ `groupedevis`. Le reste de l’affichage conditionnel existant reste inchangé. Le moteur 1.9.10 rend le choix du type de groupe indisponible tant que la date ne correspond pas à une grille publiée.

Les cinq champs cachés nécessaires au PDF restent :

```text
[hidden devisannee]
[hidden tarifenfant]
[hidden tarifadulte]
[hidden tarifhandicap]
[hidden tarifaccompagnateur]
```

## Test de validation
1. Conserver 2026 disponible : une date 2026 doit calculer avec la grille 2026 et générer `DEVIS 2026`.
2. Créer des tarifs 2027 de test très reconnaissables et cocher 2027 disponible.
3. Choisir une date 2027 : les prix et totaux doivent utiliser exclusivement la grille 2027 et le PDF doit afficher `DEVIS 2027`.
4. Décocher 2027 disponible puis retester une date 2027 : le formulaire doit afficher l’indisponibilité et CF7 doit refuser l’envoi.
5. Vérifier les deux types de groupe séparément.

## Base stable
Les modèles PDF et CF7 antérieurs archivés dans GitHub restent des points de restauration et ne doivent pas être écrasés. Cette version ne demande aucun redesign du PDF.
