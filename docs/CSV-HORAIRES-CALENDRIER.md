# CSV — horaires & calendrier

Version introduisant l’export et l’accès limité : 1.15.14.

L’outil CSV est commun à la saison active et se trouve dans l’administration **Horaires & calendrier**. Il sert à importer, exporter ou générer un modèle.

## Règles générales

- séparateur recommandé : `;` ;
- encodage : UTF-8 ;
- une ligne = une période, une exception, un événement, un jour férié ou une règle d’accès ;
- `type` et `start` sont obligatoires ;
- les dates sont au format `AAAA-MM-JJ` ;
- les heures sont au format `HH:MM` ;
- les jours de semaine utilisent `1,2,3,4,5,6,7` pour lundi à dimanche ;
- les booléens acceptent notamment `1/0`, `oui/non`, `yes/no`, `true/false` ;
- l’import valide l’intégralité du fichier avant toute écriture ;
- une révision de sécurité est créée avant import ;
- seules les catégories réellement présentes dans le fichier sont remplacées. Une catégorie absente du CSV reste inchangée.

## Types canoniques

- `regular` : période d’ouverture habituelle ;
- `exception_hours` : horaires exceptionnels ;
- `exception_closed` : fermeture exceptionnelle ;
- `holiday` : jour férié / journée particulière ;
- `period` : période repère générique ;
- `school_holiday` : vacances scolaires ;
- `event` : événement ;
- `limited_access` : accès temporairement limité.

`limited_access` est l’unique identifiant CSV retenu pour le module **Accès temporairement limité**. Ne pas introduire un alias concurrent comme `domain_rule`.

## Colonnes principales

Le modèle téléchargeable depuis l’administration reste la référence exacte de l’ordre des colonnes. Les colonnes principales sont :

`type`, `enabled`, `label`, `start`, `end`, `weekdays`, `open`, `close`, `open2`, `close2`, `last_entry_minutes`, `color`, `priority`, les traductions `*_fr`, `*_en`, `*_de`, puis les paramètres d’affichage, de pop-up et d’accès limité.

### Accès temporairement limité

Les colonnes prises en charge comprennent notamment :

- `weekdays` ;
- `pause_start` ;
- `resume` ;
- `last_entry` ;
- `exclude_weekends` ;
- `exclude_school_holidays` ;
- `exclude_public_holidays` ;
- `auto_details` ;
- `color` ;
- `public_title_fr/en/de` ;
- `access_message_fr/en/de` ;
- `details_message_fr/en/de` ;
- `show_tooltip` ;
- `tooltip_text_fr/en/de` ;
- `info_fr/en/de`.

## Export / réimport

Le bouton **Exporter les données [année]** produit un CSV utilisant exactement le même en-tête canonique que le modèle et l’importeur. Le fichier peut donc être réimporté dans l’outil CSV.

L’export couvre les données prises en charge pour : horaires habituels, exceptions, jours fériés, périodes repères, vacances scolaires, événements et accès temporairement limité.

## Recherche dans l’administration

La version 1.15.14 ajoute aussi un filtre instantané dans :

- Périodes repères ;
- Événements ;
- Exceptions ;
- Accès temporairement limité.

Ce filtre agit uniquement sur l’affichage des lignes déjà présentes dans le formulaire. Il ne supprime, ne réordonne et ne modifie aucune donnée enregistrée. L’ajout d’une nouvelle ligne réinitialise le filtre actif pour garantir que la nouvelle ligne reste visible et éditable.
