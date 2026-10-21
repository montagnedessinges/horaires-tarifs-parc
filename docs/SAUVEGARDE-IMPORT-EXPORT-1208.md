# Sauvegarde et import/export de l’extension — 1.20.8

## Accès
**Gestion du parc > Vue d’ensemble > Import / export et sauvegarde**. Sélectionnez d’abord l’année (p. ex. 2027).

Cet outil complète le **CSV historique Horaires & calendrier** (dont le modèle n’est pas modifié).

## Export global
Choisissez **Tout le site — ZIP** pour télécharger :
- manifest.json : format, parc, domaine, date, schéma et version ;
- configuration.json : copie réimportable des options de configuration de l’extension ;
- tableaux/AAAA-schedule.csv, tableaux/AAAA-visitors.csv, tableaux/AAAA-groups.csv pour chaque année présente (copies **éditables et réimportables séparément**).

L’archive globale est strictement liée au domaine et au type de parc ; sa restauration sur un autre site est refusée.

### Périmètre du ZIP global
Options WordPress couvertes (lorsqu’elles existent) :

- parcs_ht_settings (saisons, horaires, tarifs, affichage général) ;
- parcs_ht_group_tariff_settings (présentation groupes par année) ;
- parcs_ht_advent (configuration Avent) ;
- parcs_ht_pedagogical_guides (paramètres des guides) ;
- parcs_ht_faq (configuration FAQ) ;
- parcs_ht_wordpress_events (paramètres d’événements) ;
- parcs_ht_guide_appearance ;
- parcs_ht_public_content ;
- parcs_ht_popups_1200.

**Non inclus** : demandes de devis et contacts, données personnelles, fichiers médias, configuration du thème, formulaires CF7, base WordPress hors options ci-dessus, statistiques, connexions externes, jetons et journaux d’erreur. **Le ZIP n’est pas une sauvegarde complète du site WordPress.** Conservez une sauvegarde WordPress de la base et des fichiers avant une restauration majeure.

## CSV ciblé (import et export)
Trois rubriques :
1. **Horaires et calendrier** — règles régulières, exceptions, vacances, jours fériés, événements/périodes et accès limité ;
2. **Tarifs visiteurs** — lignes individuelles/réduites, prix, colonnes, textes FR/EN/DE et moyens de paiement ;
3. **Tarifs groupes** — lignes/colonnes tarifaires groupes **et** réglages d’affichage groupes de la saison.

Les CSV sont enregistrés en UTF-8 avec séparateur point-virgule.

| scope | park | year | path | json_value |
|---|---|---|---|---|
| visitors | fds | 2027 | /tariffs/individual/0/id | "adult" |
| visitors | fds | 2027 | /tariffs/individual/0/cells/price/online | "12.00" |

*Exemples de structure uniquement : ces exemples ne représentent pas les clés obligatoires ou des tarifs officiels.*

- **Ne changez pas** scope, park, year, path ou les identifiants métier. Modifiez seulement json_value ; les chaînes de caractères doivent rester entre guillemets JSON ("12.50"), les nombres sont sans guillemets (12.5), true et false sont des booléens, et une liste vide est [].
- Les lignes sont des champs techniques, pas un catalogue simplifié. Les supprimer peut effacer des réglages de la rubrique.
- Pour éditer uniquement les prix sans risque, commencez par **exporter les tarifs visiteurs 2027** ou les tarifs groupes 2027, modifiez les valeurs, puis **importez le même fichier**.
- Un import CSV n’affecte **aucune autre année**. Les imports visiteurs préservent les groupes ; les imports groupes préservent les visiteurs.
- Le format CSV historique (78 colonnes) reste utilisable dans la page **Horaires & calendrier**. Le nouveau CSV à cinq colonnes doit être importé depuis la **Vue d’ensemble**.

## Importation
1. Sélectionner l’année cible sur la vue d’ensemble.
2. Choisir le ZIP (global) ou le CSV (section d’une seule année).
3. Cliquer **Analyser avant import**.
4. Vérifier la portée, l’année, le nombre de chemins différents et la liste de chemins ciblés.
5. Cocher la confirmation puis **Confirmer l’import**.

Une prévisualisation expire après 15 minutes. Si les données ont été modifiées entre l’aperçu et la validation, l’import est annulé. Avant la modification, l’extension crée une révision interne de sécurité (quatre dernières révisions maximum) dans l’option parcs_ht_backup_import_revisions.

## Points de vigilance
- L’import global peut remplacer les statuts de publication de toutes les saisons. **Ne jamais importer directement sur la production sans sauvegarde indépendante de la base WordPress.**
- Les exports globaux peuvent contenir des textes internes et des adresses de contact administratives : ne pas les distribuer publiquement.
- L’importation de médias et d’extensions tierces (CF7, thème, etc.) n’est pas assurée.
- L’utilisation de Google Sheets est possible pour examiner les CSV, mais l’éditeur peut reformater les chaînes JSON. Vérifiez les guillemets et l’encodage lors de la réexportation.
- En cas de problème, utiliser la sauvegarde indépendante et l'archive précédemment exportée. Une révision interne est également conservée avant import, mais sa restauration n’est pas proposée par ce premier écran.

## Tests
Le fichier tests/backup-import-1208-contract.php teste la fidélité aller-retour des lignes JSON, la détection de doublons/valeurs incorrectes et la préservation d’autres années/rubriques lors d’une fusion. La CI vérifie les syntaxes PHP et les régressions sur plusieurs versions de PHP.

## Évolutions futures envisageables
Une grille CSV « une ligne = un tarif » pourrait compléter le format fidèle pour rendre la modification dans Sheets encore plus simple. Elle nécessiterait une logique de correspondance explicite pour les identifiants, périodes, colonnes et règles de publication.
