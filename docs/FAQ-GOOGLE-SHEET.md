# FAQ 1.19.0 — liaison Google Sheet

La FAQ est globale au parc et n'est pas liée à une saison ni à une année.

## Principe

Le Google Sheet reste l'espace éditorial. WordPress ne le consulte jamais pour servir une page publique : le site affiche uniquement la dernière base FAQ importée et validée dans l'extension.

Le bouton **Vérifier le Google Sheet** réalise une lecture ponctuelle, construit un aperçu des différences et n'écrit rien dans WordPress tant que l'administrateur n'a pas confirmé l'import.

Sécurités :

- stockage FAQ séparé de `parcs_ht_settings` et donc des horaires, saisons, tarifs et devis ;
- aucune suppression automatique lorsqu'une ligne disparaît du Sheet ;
- seules les lignes au statut `Validé` et dont l'usage contient `FAQ publique` sont applicables ;
- les lignes `À valider`, `Conflit` ou réservées à l'IA interne restent bloquées ;
- révision automatique avant chaque import et avant chaque restauration ;
- 10 révisions conservées ;
- l'indisponibilité de Google n'affecte jamais la FAQ déjà publiée.

## Mise en place du pont Google

1. Ouvrir le Google Sheet FAQ.
2. Aller dans **Extensions > Apps Script**.
3. Dans WordPress, ouvrir **Gestion du parc > FAQ > Mise en place du pont Google**, afficher le script et utiliser **Copier le script**. Le coller dans `Code.gs`. La copie de référence reste dans `docs/FAQ-GOOGLE-SHEET-APPS-SCRIPT.gs`.
4. Dans les paramètres du projet Apps Script, afficher le manifeste `appsscript.json` et y coller le manifeste fourni dans le même écran WordPress. Il impose uniquement `https://www.googleapis.com/auth/spreadsheets.readonly`. Enregistrer.
5. Dans le menu des fonctions, choisir `installerConfiguration` puis cliquer sur **Exécuter**.
6. Autoriser le script à lire le tableur.
7. Dans **Paramètres du projet > Propriétés du script**, copier `FAQ_SHARED_SECRET`. La clé ne figure ni dans le code distribué ni dans les journaux.
8. Aller dans **Déployer > Nouveau déploiement > Application Web**.
9. Choisir **Exécuter en tant que : Moi** et **Qui a accès : Tout le monde**.
10. Déployer et copier l'URL terminée par `/exec`.
11. Dans WordPress, ouvrir **Gestion du parc > FAQ**.
12. Renseigner : le lien du Google Sheet, l'URL `/exec`, la clé secrète, le parc et le nom exact de l'onglet.
13. Cliquer sur **Tester et utiliser ce Google Sheet**, puis sur **Vérifier le Google Sheet** pour préparer un import.

Même si le Web App est joignable publiquement, aucune donnée n'est renvoyée sans la clé secrète. La clé est envoyée dans le corps HTTPS d'une requête POST et n'apparaît pas dans l'URL.

## Changer de fichier sans redéployer

Conserver le projet Apps Script et son déploiement initial. Donner au compte Google qui l’exécute un accès en lecture au nouveau Sheet, copier son URL dans la connexion WordPress puis cliquer sur **Tester et utiliser ce Google Sheet**. Le test contrôle le fichier, l’onglet, les colonnes requises, le parc et la présence de fiches valides avant de sauvegarder la connexion. Un échec conserve intégralement l’ancienne connexion et les réponses importées.

WordPress est l’unique propriétaire du choix de source : il transmet `spreadsheet_id` dans chaque POST authentifié. Le script n’enregistre pas une seconde source dans ses propriétés. Cela évite une désynchronisation entre Google et WordPress et permet de conserver le même déploiement.

Le changement de connexion n’importe aucune réponse et invalide les aperçus antérieurs (y compris ceux d’un autre administrateur au moment de leur application). L’affichage public dispose de son propre formulaire et reste indépendant du test Google.

Pour renouveler la clé, exécuter `regenererCleSecrete`, recopier la propriété dans WordPress et tester. Pour mettre à jour un ancien script, remplacer le code et le manifeste puis **Déployer > Gérer les déploiements > Modifier > Nouvelle version**. L’URL reste identique. Un script ancien qui ignore le fichier demandé est refusé si sa réponse ne correspond pas à la source.

## Sécurité du transport

Seules les URL HTTPS `script.google.com/macros/s/…/exec` sont acceptées. La redirection ContentService est suivie uniquement vers `script.googleusercontent.com/macros/echo` avec un GET sans clé, sans corps POST et sans redirection supplémentaire. Les réponses sont limitées à 2 Mio et la connexion est réservée aux administrateurs avec nonce valide. Les colonnes internes, dont `Notes / garde-fou`, ne sont pas exportées ; seules les colonnes FAQ autorisées sont transmises.

Le manifeste réduit les autorisations Google à la lecture des tableurs accessibles au compte qui déploie le script. Garder la clé secrète : elle autorise la lecture des colonnes FAQ des onglets autorisés dans les fichiers accessibles à ce compte. Le script n’écrit ni dans les cellules ni dans WordPress.

Références techniques : [ContentService et ses redirections](https://developers.google.com/apps-script/guides/content), [autorisations minimales](https://developers.google.com/apps-script/concepts/scopes).

## Onglets autorisés

Le script accepte uniquement :

- `Montagne des Singes` avec le préfixe `MDS-` ;
- `Forêt des Singes` avec le préfixe `FDS-`.

Chaque installation WordPress choisit son parc. Une installation Montagne ne peut donc pas importer par erreur les lignes Forêt, et inversement.

## Colonnes reconnues

Le format actuel est reconnu directement :

- `ID stable`
- `Priorité`
- `Catégorie`
- `Question canonique FR`
- `Variantes / formulations IA FR`
- `Réponse courte FR`
- `Usage / visibilité`
- `Donnée dynamique ?`
- `Source principale`
- `Vérifié le`
- `Statut`

Le moteur accepte en plus, dès qu'elles sont ajoutées au Sheet :

- `Question canonique EN`, `Question canonique DE`
- `Variantes / formulations IA EN`, `Variantes / formulations IA DE`
- `Réponse courte EN`, `Réponse courte DE`
- `Catégorie EN`, `Catégorie DE`
- `Mode FAQ`
- `Lien public`
- `Libellé du lien FR`, `Libellé du lien EN`, `Libellé du lien DE`

Les shortcodes EN/DE n'affichent pas une fiche dont la question ou la réponse n'est pas encore traduite ; ils ne remplacent pas silencieusement une traduction manquante par du français.

## Modes de réponse

La colonne facultative `Mode FAQ` peut contenir :

- `direct` : réponse affichée dans la FAQ ;
- `reponse-lien` : réponse courte + lien vers la page officielle ;
- `renvoi-canonique` : réponse générique stable + lien vers la source de vérité.

Sans colonne `Mode FAQ`, les données dynamiques disposant d'une URL officielle utilisent automatiquement une réponse avec lien. Les questions principales « horaires » et « tarifs » sont traitées comme des renvois canoniques afin de ne pas recopier des horaires ou prix saisonniers dans la FAQ.

## Publication

Le workflow normal est :

1. modifier le Google Sheet ;
2. WordPress > Gestion du parc > FAQ > **Vérifier le Google Sheet** ;
3. relire l'aperçu ;
4. cocher les fiches à appliquer ;
5. **Appliquer les modifications cochées**.

Aucune étape n'est automatique. Le bouton de vérification n'effectue aucune publication.

## Shortcodes

- `[parc_faq]` : langue détectée par le site ;
- `[parc_faq_fr]` ;
- `[parc_faq_en]` ;
- `[parc_faq_de]`.

Options :

- `[parc_faq_fr categorie="billets"]`
- `[parc_faq_fr recherche="0"]`
- `[parc_faq_fr categories="0"]`
- `[parc_faq_fr titre="0"]`

Le contenu des réponses est rendu côté serveur dans le HTML initial. La recherche et les filtres de catégories sont uniquement un confort JavaScript et ne chargent jamais les réponses par AJAX.
