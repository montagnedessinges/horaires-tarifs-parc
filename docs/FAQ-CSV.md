# FAQ 1.19.9 — import CSV du parc et boutons d’action

La FAQ reste globale au parc et indépendante de toute année ou saison.

## Source unique d’import

La source d’import WordPress est uniquement l’onglet du parc concerné :

- `Montagne des Singes`
- `Forêt des Singes`

L’onglet `Connaissances singes - IA` reste une base interne et **ne doit pas être exporté ni importé dans la FAQ**.

## Workflow recommandé

1. Ouvrir le Google Sheet éditorial.
2. Ouvrir l’onglet du parc concerné.
3. Télécharger uniquement cet onglet via **Fichier → Télécharger → Valeurs séparées par des virgules (.csv)**.
4. Dans WordPress, ouvrir **Gestion du parc → FAQ**.
5. Choisir le parc, sélectionner le fichier CSV puis cliquer sur **Analyser le CSV**.
6. Relire l’aperçu : nouvelles, modifiées, identiques, non publiables, invalides.
7. Cocher uniquement les fiches à appliquer puis cliquer sur **Appliquer les modifications cochées**.

L’analyse du CSV n’écrit rien. L’écriture ne commence qu’après validation de l’aperçu.

## Catégorie secondaire « Règles du parc »

Les règles discrètes sont créées directement dans l’onglet du parc avec :

- `Catégorie = Règles du parc`
- `Statut = Validé`
- `Usage / visibilité` contenant `FAQ publique`

La catégorie `Règles du parc` :

- n’apparaît pas parmi les filtres principaux de la FAQ ;
- est rendue côté serveur dans le HTML initial ;
- est présentée dans un accordéon discret fermé par défaut ;
- reste accessible à un visiteur qui choisit de l’ouvrir ;
- reste incluse dans la recherche interne ;
- s’ouvre automatiquement lorsqu’une recherche correspond à une règle ;
- peut afficher une source publique sûre sous la réponse.

Ce fonctionnement évite le texte techniquement invisible ou réservé aux robots : le contenu reste réellement accessible aux visiteurs tout en étant lisible par les moteurs et outils d’IA qui parcourent le HTML.

## Sécurités

- stockage FAQ séparé de `parcs_ht_settings` ;
- aucune suppression automatique lorsqu’une ligne disparaît du CSV ;
- IDs MDS/FDS contrôlés avant import ;
- IDs dupliqués refusés ;
- lignes `À valider`, `Conflit` ou non destinées à la FAQ publique non publiables ;
- révision de sécurité avant chaque import ;
- taille maximale 5 Mo et 2 000 lignes ;
- aucune dépendance réseau ou Apps Script pendant l’import ;
- aucune fonction d’import de l’onglet `Connaissances singes - IA`.

## Colonnes minimales

- `ID stable`
- `Question canonique FR`
- `Réponse courte FR`
- `Usage / visibilité`
- `Statut`

Les autres colonnes reconnues incluent la priorité, la catégorie, les synonymes, les sources, les données dynamiques, les liens publics et les traductions FR / EN / DE.

Pour la base actuelle, l’import reconnaît notamment :

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


## Boutons d’action et liens de redirection

Les colonnes multilingues de redirection sont désormais lues directement par l’import FAQ :

- `Lien de redirection FR`
- `Weiterleitungslink DE`
- `Redirect link EN`

Pour afficher volontairement un bouton sous une réponse, renseigner aussi le libellé correspondant :

- `Texte bouton FR`
- `Button-Text DE`
- `Button text EN`

Règle de rendu :

- URL + texte de bouton : affiche un vrai bouton cliquable sous la réponse ;
- URL sans texte de bouton : n’ajoute pas automatiquement un nouveau bouton sur une réponse directe ;
- les liens dynamiques déjà prévus par la FAQ (horaires, tarifs, etc.) conservent leur comportement et sont également rendus comme boutons ;
- le lien utilisé dépend de la langue de la FAQ ;
- les URL Google Docs, Google Drive et Gmail internes restent refusées comme liens publics ;
- une URL OneDrive ou un autre lien HTTPS public peut être utilisée si elle est volontairement placée dans la colonne de redirection.

Les URL destinées à un bouton ne doivent plus être recopiées en clair dans le texte de la réponse.
