# Préparation 1.14.0 — Bascule tarifaire 2027 + Calendrier de l’Avent

Date de préparation : 7 septembre 2026
Base vérifiée : version publiée `1.13.8`
Statut : cahier des charges / préparation uniquement. Aucune fonctionnalité décrite ci-dessous n’est considérée comme livrée tant qu’une nouvelle release GitHub n’est pas réellement publiée avec son ZIP de production.

## Objectif général

La prochaine évolution est suffisamment importante pour viser une version fonctionnelle `1.14.0` plutôt qu’un correctif `1.13.9`.

Deux chantiers doivent être menés dans la même préparation, tout en restant techniquement indépendants :

1. fiabiliser la sélection annuelle des tarifs, en particulier les tarifs groupes et le devis en ligne ;
2. créer un module complet de Calendrier de l’Avent réutilisable d’une année à l’autre et sur les deux parcs.

Aucune logique du Calendrier de l’Avent ne doit dépendre d’une date de bascule tarifaire, d’une fin de saison ou d’un changement de tarifs.

---

# 1. Audit confirmé — Tarifs groupes et devis en ligne

## 1.1 Source de vérité actuelle

Le devis en ligne utilise la grille canonique `tariffs['groups']` de la saison correspondant à l’année de la date de visite.

Les correspondances métier ne reposent plus sur une position de ligne fragile mais sur les identifiants permanents :

- colonne de prix ;
- ligne enfant / scolaire ;
- ligne adulte ;
- ligne personne en situation de handicap ;
- ligne accompagnateur.

La liaison est enregistrée par année dans `Parcs_HT_Group_Quotes::tariff_bindings`.

Le moteur refuse une liaison vers une ligne ou une colonne qui n’existe plus.

## 1.2 Calcul du devis

Le navigateur sert à l’aperçu instantané du calcul, mais le serveur recalcule et réécrit les montants au moment de l’envoi CF7.

À préserver impérativement :

- l’année tarifaire vient de la date de visite ;
- aucun tarif d’une année précédente ne doit servir de secours si l’année demandée n’est pas disponible ;
- les champs du PDF sont remplis à partir de la grille de l’année retenue ;
- le calcul scolaire applique la règle de gratuité configurée ;
- le calcul handicap applique le tarif de la personne et de l’accompagnateur ;
- les montants postés par le navigateur ne sont jamais considérés comme fiables sans recalcul serveur.

## 1.3 Décision validée pour 2027

Il faut distinguer deux notions :

### Publication tarifaire

Une grille 2027 doit être publiée pour pouvoir être utilisée par le devis 2027.

Exemple :

- les tarifs groupes 2027 sont préparés ;
- ils sont explicitement publiés ;
- une date de visite en 2027 peut alors générer un devis avec les tarifs 2027.

### Date de bascule publique

La publication des tarifs 2027 ne doit pas les afficher immédiatement sur le site public.

Une date de switch / bascule publique doit être configurable.

Avant cette date :

- `[parc_tableau_tarifs]` continue d’afficher la grille publique précédente ;
- `[parc_tarifs_groupes]` continue d’afficher la même année publique ;
- le devis peut déjà utiliser 2027 si la date de visite est en 2027 et que la grille 2027 est publiée.

À partir de la date de bascule :

- `[parc_tableau_tarifs]` passe sur 2027 ;
- `[parc_tarifs_groupes]` passe en même temps sur 2027 ;
- le titre, l’année affichée et les données doivent être cohérents.

La date de bascule publique est donc indépendante de la date de publication de la grille.

## 1.4 Point à corriger dans le gate du devis

Le contrôle d’accès préalable au formulaire doit utiliser la même logique canonique que le moteur de calcul du devis.

Il ne doit pas dépendre d’un ancien miroir ou d’une structure secondaire qui pourrait considérer une année comme indisponible alors que sa grille groupes est correctement publiée et liée.

Objectif : une seule vérité pour la disponibilité d’un devis :

1. la saison / grille de l’année existe ;
2. les tarifs groupes nécessaires sont publiés ;
3. la colonne et toutes les lignes liées existent ;
4. les prix sont numériques et valides ;
5. la liaison des gratuités est valide.

## 1.5 Contrôle d’état à ajouter dans l’administration

Pour chaque année, le bloc Groupes → Tarifs doit pouvoir afficher un diagnostic très lisible :

- grille groupes présente ;
- publication groupes active ;
- colonne de prix liée ;
- enfant / scolaire lié ;
- adulte lié ;
- handicap lié ;
- accompagnateur lié ;
- règle de gratuité valide ;
- devis pour cette année : PRÊT / BLOQUÉ ;
- raison précise si bloqué.

Ce diagnostic doit éviter les incohérences avant ouverture des devis d’une nouvelle année.

## 1.6 Tests obligatoires pour cette partie

Créer un parcours automatisé couvrant au minimum :

- date 2026 → prix 2026 ;
- date 2027 → prix 2027 ;
- année non publiée → devis bloqué ;
- année publiée mais liaison invalide → devis bloqué ;
- Q scolaires : calcul enfant + adultes payants + gratuités ;
- handicap : personne + accompagnateur ;
- JavaScript et PHP produisent les mêmes résultats ;
- tentative de falsification des champs tarifaires postés → valeurs serveur rétablies ;
- publication 2027 avant la date de switch public → devis 2027 autorisé mais shortcodes publics encore sur l’année précédente ;
- passage de la date de switch → les deux shortcodes tarifaires publics basculent ensemble.

---

# 2. Nouveau module Calendrier de l’Avent

## 2.1 Principe

Le Calendrier de l’Avent doit être un module natif de l’extension, configurable dans WordPress et réutilisable :

- La Montagne des Singes ;
- La Forêt des Singes ;
- 2026 ;
- 2027 ;
- années suivantes.

Aucune donnée propre à un parc, un partenaire, un mot mystère ou une année ne doit être codée en dur dans le moteur.

Les campagnes s’accumulent dans l’administration :

- Avent 2026 ;
- Avent 2027 ;
- Avent 2028 ;
- etc.

Les anciennes campagnes restent conservées comme archive interne.

## 2.2 Shortcode

Base prévue :

`[parc_calendrier_avent]`

Le shortcode est fixe : on ne crée pas un shortcode différent contenant l’année.

L’année affichée est déterminée par la campagne active / sélectionnée selon la logique du module Avent, jamais par la bascule tarifaire.

Le module doit suivre les conventions générales de l’extension et rester compatible avec l’architecture multilingue FR / EN / DE si des contenus traduits sont renseignés, sans obliger à remplir toutes les langues.

## 2.3 Onglet Shortcodes — exigence obligatoire

Le nouveau shortcode doit être ajouté au référentiel central `Parcs_HT_Shortcode_Registry`.

Conséquences obligatoires :

- il apparaît dans l’onglet Shortcodes ;
- le shortcode automatique est copiable ;
- les variantes de langue suivent le fonctionnement du registre si elles sont activées ;
- les tests du registre sont mis à jour ;
- aucun shortcode Avent parallèle ou caché ne doit être créé hors registre.

## 2.4 Aperçu administrateur — exigence obligatoire

Le Calendrier de l’Avent doit apparaître dans l’onglet Aperçu et être réellement utilisable sans être publié sur une page du site.

L’aperçu doit utiliser le même moteur de rendu que le frontend, pas un faux visuel séparé.

Il doit permettre de tester une campagne encore en brouillon.

Le calendrier doit être interactif dans l’aperçu :

- cases cliquables ;
- détail d’une journée ;
- visuel ;
- question ;
- partenaire ;
- lot ;
- boutons sociaux ;
- réponse / gagnants si leur date de publication est simulée ;
- comportement des indices ;
- grand jeu final.

Le système actuel d’aperçu retire les scripts embarqués des shortcodes. Le module Avent devra donc charger proprement ses propres assets administratifs / frontend nécessaires dans l’aperçu plutôt que dépendre d’un `<script>` injecté dans le HTML.

Prévoir idéalement une simulation de date/heure dans l’aperçu pour tester :

- avant lancement ;
- jour 1 ;
- jour 15 ;
- 24 décembre ;
- période du grand jeu ;
- après clôture / mode archive.

Cela doit permettre de construire et contrôler tout le calendrier avant de créer ou modifier la page publique.

---

# 3. Configuration générale d’une campagne Avent

Chaque campagne doit pouvoir définir au minimum :

- année / nom interne ;
- statut brouillon / publiée ;
- dates et heures de début / fin ;
- fuseau horaire du parc ;
- titre public ;
- introduction ;
- visuel / identité éventuelle ;
- compte Instagram du parc sous forme de `@` / identifiant ;
- page / identifiant Facebook du parc ;
- URL personnalisée prioritaire si un réseau ne peut pas être reconstruit proprement depuis l’identifiant ;
- hashtags par défaut du parc ;
- lien / texte du règlement complet ;
- modèle de règles quotidiennes ;
- texte expliquant le grand jeu du mot mystère ;
- pictogramme du grand jeu / loupe ;
- phrase de fin ;
- modèles de texte Facebook / Instagram ;
- mot mystère final ;
- dates et heures d’ouverture et fermeture du grand jeu ;
- date / heure de révélation publique des indices et du mot final après clôture.

Pour MDS 2026, le mot actuellement validé dans la préparation est `KINTZHEIM`, mais cette valeur doit rester une donnée WordPress modifiable, pas une constante du code.

---

# 4. Teasing avant le 1er décembre

La campagne doit pouvoir comporter des éléments de teasing configurables avec :

- date / heure d’activation ;
- visuel ;
- titre ;
- texte ;
- bouton / lien facultatif.

Ne pas coder un nombre fixe de teasers.

Le shortcode doit afficher automatiquement le teaser pertinent avant l’ouverture du calendrier.

---

# 5. Les 24 journées

Chaque journée doit avoir sa propre fiche.

## 5.1 Données principales

- jour / date ;
- heure d’ouverture ;
- activation oui / non ;
- visuel principal, affiché dans un cadre vertical cohérent proche du format Instagram 4:5 ;
- texte alternatif ;
- titre court facultatif ;
- question / consigne écrite ;
- texte complémentaire facultatif ;
- type de jeu : QCM, vrai / faux, observation / à deviner, autre ;
- choix de réponses si nécessaire ;
- bonne réponse ;
- explication de la réponse ;
- partenaire du jour facultatif ;
- lot du jour ;
- URL exacte de la publication Instagram du jour, une fois publiée ;
- URL exacte de la publication Facebook du jour, une fois publiée ;
- date / heure de publication des résultats ;
- gagnant Instagram ;
- gagnant Facebook.

Le visuel ne doit pas être considéré comme un simple décor : certaines questions reposent sur un zoom, un détail caché ou une observation du visuel. La question complète reste néanmoins écrite hors de l’image pour éviter de surcharger le visuel et préserver l’accessibilité.

## 5.2 Résultats

Une ancienne journée doit conserver son contenu initial puis afficher, lorsque le résultat est publié :

- bonne réponse ;
- explication ;
- gagnant Instagram ;
- gagnant Facebook ;
- rappel / remerciement du partenaire si nécessaire.

Le résultat ne doit pas remplacer la question : le calendrier devient une archive compréhensible après la campagne.

---

# 6. Partenaires

Créer une bibliothèque de partenaires réutilisables.

Chaque partenaire peut avoir :

- nom ;
- logo ;
- courte présentation ;
- `@` Instagram facultatif ;
- identifiant / page Facebook facultatif ;
- URL Instagram personnalisée facultative ;
- URL Facebook personnalisée facultative ;
- site web facultatif ;
- hashtags facultatifs.

Une journée peut :

- avoir un partenaire externe ;
- n’avoir aucun partenaire ;
- utiliser le parc lui-même comme porteur du lot.

Les liens de profil doivent être générés automatiquement quand l’identifiant permet de le faire, avec possibilité d’URL personnalisée prioritaire.

Ne pas confondre :

- lien du profil du partenaire, générable depuis le `@` / identifiant ;
- lien exact du post concours du jour, qui doit être enregistré après publication du post.

---

# 7. Règles quotidiennes du concours

Le règlement de base ne doit pas être ressaisi 24 fois.

Créer une configuration globale modifiable une seule fois pour la campagne.

Règle métier actuelle à prendre comme modèle configurable :

- répondre à la question en commentaire de la publication Facebook ou Instagram du jour ;
- être abonné au compte / à la page du parc sur le réseau utilisé ;
- lorsqu’un partenaire est associé à la journée et qu’un compte pertinent est renseigné, être aussi abonné au partenaire ;
- possibilité d’inclure l’identification d’une personne si la campagne le souhaite ;
- gagnants annoncés le lendemain selon le fonctionnement de la campagne.

Le texte final doit être modifiable : ne pas figer ces règles dans le code.

La condition liée au partenaire doit disparaître automatiquement lorsqu’aucun partenaire n’est associé à la journée.

---

# 8. Générateur de descriptions Facebook / Instagram

Le module doit faire gagner du temps en générant le texte des publications depuis les données déjà renseignées.

## 8.1 Boutons attendus

Pour chaque journée :

- `Copier le post Instagram` ;
- `Copier le post Facebook` ;
- après saisie du résultat : `Copier le résultat Instagram` ;
- après saisie du résultat : `Copier le résultat Facebook`.

## 8.2 Structure du post du jour

Le générateur doit pouvoir assembler automatiquement :

- `JOUR X/24` ;
- lot ;
- partenaire ;
- nombre de gagnants ;
- présentation du partenaire ;
- question ;
- réponses QCM / vrai-faux si présentes ;
- règles quotidiennes ;
- `@` du parc ;
- `@` du partenaire s’il existe ;
- appel à identifier quelqu’un si activé ;
- lien / mention du règlement ;
- bloc du grand jeu du mot mystère ;
- pictogramme / symbole de la loupe ;
- hashtags du parc + hashtags partenaire + hashtags spécifiques du jour.

Tous ces blocs doivent rester modifiables ou désactivables.

## 8.3 Structure du post résultat

Le texte de résultat doit pouvoir reprendre :

- bonne réponse ;
- lettre de réponse + libellé exact pour un QCM ;
- explication ;
- gagnant Instagram ;
- gagnant Facebook ;
- remerciement partenaire ;
- rappel éventuel des conditions d’abonnement ;
- annonce de la nouvelle question.

Le moteur doit produire la lettre et le libellé depuis la même donnée de réponse afin d’éviter une incohérence du type « A » alors que le bon libellé correspond à « C ».

## 8.4 Hashtags et identifiants

Les hashtags et `@` ne doivent jamais être codés pour un parc dans le code.

Ils viennent :

- de la configuration du parc / campagne ;
- de la fiche partenaire ;
- éventuellement de la journée.

La même fonctionnalité doit fonctionner sans modification du code sur MDS et FDS.

---

# 9. Indices du mot mystère

Chaque journée possède une case :

`Cette journée contient un indice du mot mystère`.

Si elle est cochée, afficher les champs :

- lettre ;
- position / chiffre ;
- note interne facultative ;
- pictogramme / loupe activé automatiquement.

Exemple : lettre `H`, position `6` → `H6`.

Pendant le concours :

- le code de l’indice ne doit pas être affiché explicitement dans le HTML public si le principe est de le trouver dans le visuel ;
- la loupe peut signaler qu’un indice existe ;
- le texte généré pour les réseaux peut expliquer qu’un indice se cache dans le visuel.

Après la clôture du grand jeu :

- la journée peut révéler automatiquement l’indice ;
- exemple : `Indice caché : H6` ;
- le visiteur peut comprendre rétrospectivement où était chaque lettre.

Le bloc final d’archive peut reconstituer toutes les positions du mot mystère.

Pour MDS 2026, la préparation actuelle correspond à 9 positions pour `KINTZHEIM` :

`K1 · I2 · N3 · T4 · Z5 · H6 · E7 · I8 · M9`

Cette liste est une donnée de campagne et doit être dérivée des indices configurés, pas codée en dur.

---

# 10. Grand jeu final / mot mystère

Le grand jeu final est différent du concours quotidien.

Principe actuel :

- le visiteur retrouve les indices ;
- il reconstitue le mot mystère ;
- il saisit le mot sur le site ;
- si le mot est correct, le formulaire final natif se déverrouille ;
- les règles quotidiennes d’abonnement / commentaire ne doivent pas être automatiquement imposées au grand jeu final sauf décision ultérieure explicite.

Exigences :

- mot correct conservé côté serveur ;
- ne jamais envoyer le mot attendu dans le JavaScript public ;
- validation côté serveur ;
- fenêtre d’ouverture / fermeture paramétrable ;
- message erreur paramétrable ;
- message succès paramétrable ;
- message après clôture paramétrable ;
- possibilité d’afficher le gagnant du gros lot après tirage ;
- révélation des indices et du mot final seulement après la date choisie.

Le formulaire final doit être natif au module et ne pas dépendre obligatoirement de Contact Form 7.

Les champs exacts du formulaire final, la règle de doublon et la durée de conservation des participations doivent encore être confirmés avant codage définitif.

---

# 11. Affichage public et chronologie

## Avant le lancement

Afficher les teasers configurés.

## Du 1er au 24 décembre

- futures cases verrouillées ;
- case du jour ouverte automatiquement selon la date / heure du serveur ;
- anciennes cases toujours accessibles ;
- la case du jour est visuellement identifiable ;
- les journées contenant un indice peuvent afficher la loupe ;
- cliquer ouvre le détail de la journée.

## Après publication des résultats

Ajouter réponse et gagnants dans la journée correspondante.

## Grand jeu

Afficher le bloc final uniquement selon ses dates d’ouverture.

## Après clôture

Conserver le calendrier en archive, révéler les indices / mot lorsque la date de révélation est atteinte et permettre de comprendre toute la campagne après coup.

---

# 12. Sécurité et anti-spoiler

Le serveur doit être la source de vérité pour les dates.

À préserver :

- ne pas utiliser uniquement l’horloge du navigateur ;
- ne pas envoyer dans le HTML / JSON les contenus futurs simplement masqués en CSS ;
- vérifier côté serveur qu’une journée est ouverte avant de livrer son détail ;
- protéger le mot mystère côté serveur ;
- nonces et permissions pour l’administration ;
- sanitation / échappement de tous les contenus ;
- protection anti-spam du formulaire final ;
- ne pas collecter des données personnelles inutiles.

Point à décider pendant le développement : les médias WordPress sont normalement accessibles par URL directe. Si les visuels des jours futurs doivent être réellement impossibles à consulter avant leur date, prévoir un stockage / service protégé ou documenter clairement le niveau d’anti-spoiler retenu.

---

# 13. Performance / architecture

Le module Avent ne doit pas alourdir toutes les pages de l’extension.

Exigences :

- chargement paresseux / conditionnel ;
- scripts et styles Avent chargés uniquement lorsqu’un shortcode / aperçu Avent est utilisé ;
- données de campagne séparées du gros réglage central `parcs_ht_settings` si leur volume devient important ;
- participants du grand jeu stockés séparément des réglages de configuration ;
- anciennes campagnes conservées sans être chargées intégralement sur chaque page publique ;
- médias et détails de journée chargés à la demande si utile.

L’architecture doit rester commune MDS / FDS avec uniquement des réglages différents par installation WordPress.

---

# 14. Administration proposée

Créer un espace dédié `Calendrier de l’Avent` avec :

- liste des campagnes ;
- créer / dupliquer une campagne ;
- réglages généraux ;
- réseaux et hashtags ;
- règlement / modèles de posts ;
- bibliothèque partenaires ;
- teasers ;
- jours 1 à 24 ;
- grand jeu final ;
- résultats / gagnants ;
- aperçu ;
- participations finales / export si le formulaire final est activé.

Chaque année doit pouvoir être préparée en brouillon sans effet public.

---

# 15. Points à confirmer avant le codage final du grand jeu

Les éléments suivants ne sont pas encore figés :

- champs exacts du formulaire final ;
- règle d’unicité des participations ;
- durée exacte d’ouverture du grand jeu 2026 ;
- date exacte de révélation du mot et des indices ;
- politique de conservation / suppression des participations ;
- détail exact des modèles de publication FR / EN / DE ;
- niveau de protection souhaité pour les fichiers médias des jours futurs.

Ces points ne bloquent pas la préparation de l’architecture mais doivent être validés avant la release finale.

---

# 16. Intégration au système d’aperçu existant

Le registre actuel contient 12 bases de shortcodes et expose leurs aperçus depuis `Parcs_HT_Admin_Shortcode_Preview`.

La 1.14.0 devra :

- ajouter `parc_calendrier_avent` au registre central ;
- augmenter les tests de comptage / contrat du registre ;
- fournir un `kind` / renderer dédié au module Avent plutôt que le mélanger au gros moteur des horaires ;
- rendre l’aperçu Avent avec le moteur public réel ;
- charger les styles et scripts Avent dans l’admin uniquement lorsque nécessaire ;
- permettre l’interaction dans l’aperçu ;
- afficher une campagne brouillon dans l’aperçu sans la rendre publique.

C’est une exigence fonctionnelle : le Calendrier de l’Avent doit pouvoir être construit, parcouru et vérifié dans WordPress avant de mettre son shortcode sur une page publique.

---

# 17. Ordre de développement recommandé

1. Re-vérifier la dernière release et repartir du code réellement publié le plus récent.
2. Ajouter les tests d’audit du devis groupes multi-années.
3. Corriger la disponibilité canonique du devis / gate.
4. Ajouter la date de bascule publique des tarifs et les tests 2026 → 2027.
5. Vérifier les shortcodes tarifaires et les devis sur les deux parcs.
6. Créer le stockage et l’administration des campagnes Avent.
7. Créer le registre / shortcode Avent.
8. Créer le frontend du calendrier.
9. Créer l’aperçu administratif réellement interactif.
10. Ajouter partenaires / réseaux / hashtags / générateur de posts.
11. Ajouter indices / archive / révélations.
12. Ajouter grand jeu final et gestion des participations.
13. Tests sécurité, dates, cache, performances, responsive et accessibilité.
14. Mettre à jour `CHANGELOG.md`, documentation et version.
15. Publier via GitHub Actions.
16. Ne considérer la 1.14.0 terminée qu’après release GitHub réussie et ZIP de production disponible.

---

# 18. Fichiers techniques actuellement concernés / à relire avant développement

Au minimum :

- `CHATGPT-CONTEXT.md`
- `ROADMAP.md`
- `horaires-tarifs-parc.php`
- `includes/class-parcs-ht-defaults.php`
- `includes/class-parcs-ht-tariff-seasons.php`
- `includes/class-parcs-ht-group-tariff-settings.php`
- `includes/class-parcs-ht-group-tariffs.php`
- `includes/class-parcs-ht-group-quotes.php`
- `includes/class-parcs-ht-quote-gate.php`
- `includes/class-parcs-ht-admin-groups.php`
- `includes/class-parcs-ht-shortcode-registry.php`
- `includes/class-parcs-ht-admin-shortcode-preview.php`
- `includes/class-parcs-ht-bootstrap.php`
- `assets/group-quotes.js`
- `assets/admin-groups.js`
- tests de tarifs / devis / shortcodes / aperçu / release.

Références d’audit historiques utiles :

- `AUDIT-2026-08-30-TARIFS-2027.md`
- `AUDIT-2026-08-30-DEVIS-GROUPE-SHORTCODE-CLS.md`
- audits 1.13.x relatifs aux groupes, shortcodes et aperçus.

Ce fichier devient le référentiel de préparation de la 1.14.0 jusqu’à remplacement explicite par une version plus récente de la préparation.