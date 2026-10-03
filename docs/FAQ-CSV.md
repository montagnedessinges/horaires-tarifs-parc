# FAQ 1.19.6 — imports CSV FAQ et connaissances IA

La FAQ reste globale au parc et indépendante de toute année ou saison.

## Workflow recommandé

1. Ouvrir le Google Sheet éditorial.
2. Ouvrir l’onglet du parc concerné : `Montagne des Singes` ou `Forêt des Singes`.
3. Télécharger uniquement cet onglet via **Fichier → Télécharger → Valeurs séparées par des virgules (.csv)**.
4. Dans WordPress, ouvrir **Gestion du parc → FAQ**.
5. Choisir le parc, sélectionner le fichier CSV puis cliquer sur **Analyser le CSV**.
6. Relire l’aperçu : nouvelles, modifiées, identiques, non publiables, invalides.
7. Cocher uniquement les fiches à appliquer puis cliquer sur **Appliquer les modifications cochées**.

### Connaissances singes / Règles du parc

Un second import est disponible dans **Gestion du parc → FAQ** pour l’onglet `Connaissances singes - IA`.

1. Exporter uniquement cet onglet en CSV.
2. Dans le bloc **Connaissances singes → Règles du parc**, choisir le parc destinataire.
3. Analyser le CSV puis relire l’aperçu.
4. Appliquer uniquement les lignes souhaitées.

Les IDs attendus commencent par `SIN-COM-`. Une ligne est importable si son statut est validé et si sa colonne `Usage` indique une utilisation IA. Elle est fusionnée dans le stockage FAQ existant avec la catégorie `Règles du parc`.

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


## Affichage secondaire « Règles du parc »

La catégorie `Règles du parc` est volontairement secondaire :

- elle n’apparaît pas parmi les filtres principaux de la FAQ ;
- elle est rendue côté serveur dans le HTML initial ;
- elle est présentée sous forme d’un accordéon discret et fermé par défaut ;
- elle reste accessible à un visiteur qui choisit de l’ouvrir ;
- ses questions restent incluses dans la recherche interne ;
- une recherche correspondante ouvre automatiquement le bloc ;
- une source publique sûre peut être affichée sous la réponse.

Ce comportement évite le texte techniquement invisible ou réservé aux robots : le contenu reste réellement accessible aux visiteurs tout en étant disponible pour les moteurs et outils d’IA qui lisent le HTML.
