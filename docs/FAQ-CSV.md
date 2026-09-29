# FAQ 1.19.1 — import CSV

La FAQ reste globale au parc et indépendante de toute année ou saison.

## Workflow recommandé

1. Ouvrir le Google Sheet éditorial.
2. Ouvrir l’onglet du parc concerné : `Montagne des Singes` ou `Forêt des Singes`.
3. Télécharger uniquement cet onglet via **Fichier → Télécharger → Valeurs séparées par des virgules (.csv)**.
4. Dans WordPress, ouvrir **Gestion du parc → FAQ**.
5. Choisir le parc, sélectionner le fichier CSV puis cliquer sur **Analyser le CSV**.
6. Relire l’aperçu : nouvelles, modifiées, identiques, non publiables, invalides.
7. Cocher uniquement les fiches à appliquer puis cliquer sur **Appliquer les modifications cochées**.

L’analyse du CSV n’écrit rien. L’écriture ne commence qu’après validation de l’aperçu.

## Sécurités

- stockage FAQ séparé de `parcs_ht_settings` ;
- aucune suppression automatique lorsqu’une ligne disparaît du CSV ;
- IDs MDS/FDS contrôlés avant import ;
- IDs dupliqués refusés ;
- lignes `À valider`, `Conflit` ou non destinées à la FAQ publique non publiables ;
- révision de sécurité avant chaque import ;
- taille maximale 5 Mo et 2 000 lignes ;
- aucune dépendance réseau ou Apps Script pendant l’import ;
- la FAQ publique continue d’utiliser uniquement la dernière base enregistrée dans WordPress.

## Colonnes minimales

- `ID stable`
- `Question canonique FR`
- `Réponse courte FR`
- `Usage / visibilité`
- `Statut`

Les autres colonnes reconnues incluent la priorité, la catégorie, les synonymes, les sources, les données dynamiques, les liens publics et les traductions FR / EN / DE.

Pour la base actuelle, l’import reconnaît notamment les intitulés :

- `Frage DE`
- `Varianten / KI-Formulierungen DE`
- `Kurzantwort DE`
- `Question EN`
- `AI variants / phrasings EN`
- `Short answer EN`

## Publication

L’import des fiches et l’activation publique de la FAQ restent deux opérations séparées. Les shortcodes restent :

- `[parc_faq]`
- `[parc_faq_fr]`
- `[parc_faq_en]`
- `[parc_faq_de]`
