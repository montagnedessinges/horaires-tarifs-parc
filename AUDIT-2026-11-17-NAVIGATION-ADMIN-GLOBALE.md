# Audit UX — navigation globale de l'administration « Gestion du parc »

**Date :** 17 novembre 2026  
**Statut :** audit statique et propositions ouvertes à discussion ; **aucune décision de développement ni mise en production**  
**Source analysée :** `main` (commit `6f12ae8940a92227c9ac056d59b021f773bdb4b6`), en-tête du plugin `1.21.8`  
**Périmètre :** interface d'administration de l'extension commune à La Montagne des Singes et La Forêt des Singes. Aucun site WordPress de production n'a été ouvert ; les parcours ci-dessous sont établis à partir du code, pas mesurés dans un navigateur.

## 1. Besoin utilisateur clarifié pendant l'échange

Le problème n'est pas de « mélanger » ou de retirer des fonctions de **Groupes**. Au contraire : **Groupes doit rester le point d'entrée cohérent pour tout ce qui touche aux groupes** (tarifs groupes, devis, guides pédagogiques, bons découverte, etc.).

La difficulté est la **navigation entre les fonctions liées**, même lorsqu'elles ont leur place principale dans des rubriques différentes :

- Un clic sur **Tarifs** doit ouvrir **directement Tarifs visiteurs**, sans écran de choix intermédiaire.
- Sur chacune des pages tarifaires, une navigation d'apparence **onglets** doit permettre **Visiteurs | Groupes | CSE**. Changer d'onglet peut recharger une page WordPress distincte, ce qui évite de charger toutes les fonctions simultanément.
- **Tarifs groupes** reste aussi accessible depuis **Groupes**, avec une navigation vers **Devis, Guides pédagogiques, Bons découverte**, etc. Depuis cet écran tarifaire, les autres fonctions de Groupes doivent rester accessibles ; et le contexte Tarifs doit aussi pouvoir être retrouvé sans détour.
- Pour le reste de l'extension, étudier le même principe : **une page canonique par fonction, plusieurs liens contextuels possibles, sans duplication des réglages**.
- Réduire la densité visuelle et le nombre de boutons de navigation ; ne pas rendre les actions dangereuses proéminentes.

Ces besoins remplacent, sur le point de l'ergonomie des tarifs, les hypothèses d'« écran d'atterrissage avec cartes » figurant dans l'issue historique [#111](https://github.com/montagnedessinges/horaires-tarifs-parc/issues/111). Les autres sujets de cette issue, notamment la portée linguistique du CSE, sont **hors périmètre du présent audit**.

## 2. État du code — constats vérifiés

| Famille / écran | Situation observée dans le code | Lecture UX |
| --- | --- | --- |
| **Vue d'ensemble** | `class-parcs-ht-admin-overview.php` : cartes vers plusieurs pages, statut annuel, commandes de mise à jour et sauvegardes, édition repliée des activations | Utile comme tableau de bord, mais ne devrait pas être un passage obligé pour modifier un réglage |
| **Administration générale** | `class-parcs-ht-admin-general.php` : parc, années, publication et apparence | Regroupement existant à préserver, mais certains réglages sont avancés |
| **Horaires & calendrier** | `class-parcs-ht-admin-schedule.php` : écran métier séparé | Bon candidat au lien direct depuis le sous-menu |
| **Périodes, événements, exceptions, accès limité** | `class-parcs-ht-admin-periods.php` : **un seul écran annuel**, avec ancres internes et panneaux. `class-parcs-ht-admin-periods-routing.php` et `class-parcs-ht-admin-cleanup-11710.php` rebranchent l'URL `parcs-ht-periods` sur cet écran | Ne pas recréer quatre pages ni quatre sources de données ; travailler les ancres / sous-sections existantes |
| **Tarifs visiteurs** | `class-parcs-ht-admin-retail-tariffs.php` : écran `parcs-ht-tariffs`, individuels et réduits réunis ; lien « Retour à Tarifs » | Lien de retour et page intermédiaire à remplacer par les onglets directs |
| **Tarifs CSE** | `class-parcs-ht-cse.php` : écran `parcs-ht-cse`, réglages stockés séparément dans `parcs_ht_cse_settings`, lien « Retour à Tarifs » | Peut être lié depuis la barre Tarifs sans fusion de formulaires |
| **Tarifs groupes** | `class-parcs-ht-admin-group-tariffs.php` : écran `parcs-ht-groups`, grille et réglages d'affichage ; barre locale « Tarifs groupes / Devis groupes / Guides » | C'est un **écran à double contexte** : Tarifs **et** Groupes. Les deux chemins doivent rester fluides |
| **Devis groupes** | `class-parcs-ht-admin-group-quotes-1177.php` : écran spécialisé et barre locale à trois liens ; modules liés `class-parcs-ht-group-quotes.php`, `class-parcs-ht-quote-gate.php`, `class-parcs-ht-quote-languages.php` enregistrent également des entrées de menu | Préserver le moteur et les sous-réglages, réduire les entrées dispersées ; pointer directement sur la bonne section |
| **Guides pédagogiques** | `class-parcs-ht-admin-guides-1178.php` : écran spécialisé, barre locale à trois liens ; ancienne page `class-parcs-ht-pedagogical-guides.php` redirigée | Lien dans Groupes à conserver, harmoniser la navigation et les URLs canoniques |
| **Bons découverte** | `class-parcs-ht-discovery-voucher.php` : écran existant accessible depuis une carte Groupes | Inclure dans la navigation Groupes, pas seulement dans une carte éloignée |
| **Communication** | `class-parcs-ht-admin-communication-1179.php` et `class-parcs-ht-popup-admin-1200.php` : pages **Pop-up** et **Calendrier de l'Avent** avec liens mutuels ; `class-parcs-ht-admin-cleanup-11710.php` redirige l'ancienne entrée `parcs-ht-communication` vers Pop-up | Bon précédent d'une rubrique qui ouvre directement le premier écran ; harmoniser l'apparence des onglets |
| **Contenus & traductions / FAQ** | `class-parcs-ht-public-content.php` et `class-parcs-ht-faq.php` : sous-menus dédiés | Étudier s'ils sont accessibles depuis un ensemble « Contenus » ou « Communication », sans les fusionner |
| **Google Maps / IA & Google** | `class-parcs-ht-gbp.php`, `class-parcs-ht-ai-google.php` : sous-menus dédiés | Regrouper visuellement comme intégrations / outils selon l'usage réel, sans fusionner les écrans |
| **Aperçu / Shortcodes / Mises à jour** | `class-parcs-ht-admin-navigation.php` : trois sous-menus séparés ; commandes également présentes sur la vue d'ensemble | Raccourcis utiles mais entrées de menu potentiellement secondaires |

### Points techniques particuliers

1. `class-parcs-ht-admin-navigation.php::tariffs_page()` rend actuellement **une page de deux cartes** (Visiteurs et CSE). Elle ne propose pas l'onglet Groupes et ajoute un clic. C'est le premier ajustement UX évident.
2. `class-parcs-ht-admin-navigation.php::groups_page()` contient des cartes Groupes, mais son slug `parcs-ht-groups` est **réenregistré** par `Parcs_HT_Admin_Group_Tariffs::menu()` après suppression du sous-menu antérieur. Le menu pointe ainsi, d'après l'ordre de registration, vers la **page réelle Tarifs groupes**. Ne pas partir de l'hypothèse que toutes les pages de cartes codées sont encore visibles.
3. La fonction `periods_page()` du module de navigation utilise pareillement un slug dont le callback est ensuite remplacé par l'écran métier Périodes. Une refonte doit distinguer **route encore active**, **pont de compatibilité** et **code d'ancienne page d'accueil**.
4. Les barres Groupes sont **reproduites dans plusieurs fichiers** (`admin-group-tariffs`, `admin-group-quotes-1177`, `admin-guides-1178`), avec des liens parfois dirigés vers des URLs historiques qui redirigent. Un seul générateur de navigation partagé serait plus simple à maintenir.
5. Le menu latéral est assemblé depuis **plusieurs classes** : navigation, FAQ, contenus, IA & Google, Google Maps, devis par langue, accès au devis, etc. La méthode `order_menu()` donne un ordre à certaines rubriques mais classe les autres après. Le résultat visuel exact doit être vérifié en WordPress ; ne pas déduire de l'inventaire PHP une capture réelle.
6. La couche historique `class-parcs-ht-admin.php` conserve les anciens onglets et routes de compatibilité. Les supprimer brutalement risquerait de casser les liens enregistrés ou les retours après sauvegarde.
7. L'année administrée doit être conservée dans les liens (`season`), sans confondre année d'édition et publication publique ; plusieurs moteurs ont des options et des actions de sauvegarde distinctes.

## 3. Benchmark externe — sources officielles

| Référence | Principe vérifiable | Piste applicable ici |
| --- | --- | --- |
| [WooCommerce — réglages](https://woocommerce.com/document/configuring-woocommerce-settings/) | Les familles de réglages sont accessibles par **onglets** dans WooCommerce > Settings | Onglets simples, catégories explicites, pas de mosaïque de boutons intermédiaire |
| [Gravity Forms — réglages](https://docs.gravityforms.com/gform_settings_menu/) et [aide](https://docs.gravityforms.com/troubleshooting-missing-settings-page/) | Les réglages d'extensions sont accessibles par une navigation d'onglets ; API dédiée pour ajouter/retirer des onglets | Navigation commune produite par un seul endroit plutôt que dupliquée dans chaque page |
| [The Events Calendar — dates/affichage](https://dev.theeventscalendar.com/knowledgebase/setting-dates-and-times/) | Organisation des réglages par famille et sous-section, dont l'onglet « Display » | Les réglages avancés restent dans leur contexte, sans encombrer le menu principal |
| [WordPress Developer Blog — menus cohérents](https://developer.wordpress.org/news/2023/09/how-wordpress-developers-can-keep-their-users-satisfied/) | Préconise des menus, styles et commandes cohérents avec l'administration native pour limiter confusion et problèmes d'accessibilité | Employer composants/classes WordPress et hiérarchie d'informations stable |
| [WordPress — admin-ui](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-admin-ui/) | Abstractions de pages avec en-tête, navigation et actions standardisées | Source d'inspiration ; **pas d'obligation** d'introduire React / une nouvelle dépendance dans cette extension PHP existante |

**Attention :** ce benchmark documente les principes publics des produits cités. Il ne prouve pas leurs performances ni la satisfaction des utilisateurs. Il ne signifie pas que leurs interfaces doivent être copiées à l'identique.

## 4. Proposition d'architecture — à débattre

### 4.1 Garder les familles métier, remplacer les pages de choix

Proposition de parcours **sans clic intermédiaire** :

| Entrée WordPress | Écran ouvert au premier clic | Navigation disponible dans l'écran |
| --- | --- | --- |
| Vue d'ensemble | Statut et raccourcis actuels | Accès direct aux actions fréquentes, sans obligation de passer par le tableau de bord |
| Horaires & calendrier | Horaires habituels | Sous-sections vers Périodes repères, Événements, Exceptions, Accès limité |
| Tarifs | Tarifs visiteurs | **Visiteurs · Groupes · CSE** (liens vers pages canoniques) |
| Groupes | **Tarifs groupes** (par défaut, à confirmer) | **Tarifs · Devis · Guides · Bons découverte** (et liens contextuels vers sous-réglages du devis) |
| Communication | Pop-up | **Pop-up · Calendrier de l'Avent** |
| Contenus (option) | Contenus & traductions | Contenus · FAQ ; autres intégrations selon usage |
| Outils / Réglages (option) | Liste compacte ou première page courante | Aperçu, shortcodes, mises à jour, sauvegardes, Google Maps, IA & Google selon usage |

Cette proposition **ne retire pas** les modules, ni les données, ni les URLs de compatibilité. Elle remet en cause la pertinence de certaines entrées dans le menu **visible**, pas l'existence des pages techniques.

### 4.2 Écran « Tarifs groupes » : le vrai choix à arbitrer

Cet écran appartient à deux chemins de consultation. Deux options crédibles :

**Option A — deux niveaux visuels bien distincts** (exhaustivité, mais risque de densité) :

- Niveau **Tarifs** : Visiteurs · **Groupes** · CSE.
- Juste au-dessous, barre discrète **Espace Groupes** : **Tarifs groupes** · Devis · Guides · Bons découverte.
- Les barres ne sont pas deux formulaires : ce sont seulement des liens. Un onglet actif est identifié et seul l'écran visé est chargé.

**Option B — navigation principale contextuelle** (plus légère, mais demande un mécanisme de retour clair) :

- Toujours montrer la barre **Espace Groupes** sur Tarifs groupes.
- Ajouter, en haut, un accès discret mais visible **« Autres tarifs : Visiteurs / CSE »** (bouton de menu ou liens simples), au lieu d'une deuxième rangée complète.
- Depuis l'entrée Tarifs, le lien Groupes arrive quand même **directement** sur le même écran ; le retour vers les autres tarifs doit rester à un clic.

**Avis initial pour le débat :** A convient mieux si l'utilisateur passe constamment de visiteurs à groupes à CSE ; B convient si le travail quotidien se déroule surtout dans l'espace Groupes. Il faut trancher sur la base des parcours fréquents, pas par préférence esthétique.

### 4.3 Lisibilité dans les écrans

- Des **onglets de navigation sont des liens HTML entre pages** (chargement ciblé par écran), avec état actif accessible (`aria-current="page"`) ; ne pas transformer toute l'administration en une grosse interface JavaScript.
- Une seule zone dominante de navigation par contexte, des titres courts, des intitulés cohérents et une indication visible de l'année administrée.
- Replier les options rares : apparence fine, intégrations techniques, options avancées ; laisser visibles les champs quotidiens et les actions importantes.
- Rendre les contrôles d'édition visuellement distincts des liens de navigation : **« Enregistrer » ne doit pas ressembler à « Changer de rubrique »**.
- Avertir en cas de changements non enregistrés lorsque l'utilisateur quitte un formulaire, y compris vers un autre onglet ou une autre année.
- Éviter de dupliquer les boutons « retour » en présence d'onglets et de fil d'Ariane ; conserver des liens d'aide réellement utiles.
- Ne pas déplacer au premier niveau les suppressions ou actions dangereuses ; confirmations, révisions et nonces restent indispensables.

## 5. Priorités et plan de validation proposé

1. **P0 — Cartographie finale du menu réellement rendu** sous WordPress, sur ordinateur et sur mobile, en comparant les deux parcs et les rôles concernés. Audit actuel = statique ; cette étape reste **non vérifiée**.
2. **P1 — Navigation Tarifs directe** : clic Tarifs ⇒ Visiteurs ; barre Visiteurs / Groupes / CSE avec conservation de l'année ; suppression de la page de sélection inutile en façade, sans casser son URL.
3. **P1 — Navigation Groupes complète** : depuis Tarifs groupes, rejoindre Devis, Guides, Bons découverte et les fonctions liées ; trancher l'option A/B pour la navigation croisée.
4. **P2 — Harmonisation Calendrier / Communication / Contenus** : reprendre les écrans existants et les ancres ; pas de doublon de sauvegarde.
5. **P2 — Menu latéral plus court** : arbitrer le statut des entrées techniques, FAQ, Google Maps, IA & Google, Aperçu, Shortcodes, Mises à jour.
6. **P3 — Uniformisation visuelle** : apparence des liens actifs, titres, sélecteur d'année, messages de sauvegarde, sections avancées, accès clavier/mobile.

### Garde-fous si le projet est développé ensuite

- Conserver exactement les moteurs, options, enregistrements et permissions actuels (`parcs_ht_settings`, CSE séparé, modules devis, etc.).
- Conserver les redirections et routes canoniques, mais éviter les chaînes de plusieurs redirections ou des pages-ponts superflues.
- Tester navigation, rafraîchissement et retour après sauvegarde **sur les deux parcs**, pour plusieurs années (dont année future), mobile, clavier, changement d'onglet avec modifications non enregistrées.
- Contrôler les écrans qui réutilisent les mêmes tarifs, la disponibilité des devis, les PDF, les shortcodes et les permissions de publication.
- Préserver la performance actuelle : scripts/styles administratifs **uniquement sur la page ouverte**, pas de chargement global de tous les modules.
- Une **nouvelle version et une nouvelle release** seront nécessaires pour toute modification fonctionnelle de l'extension. **Ce document seul n'en nécessite pas.**

## 6. Questions ouvertes pour le débat avec Tanguy

1. Sur **Tarifs groupes**, préfère-t-il la **double barre compacte (A)** ou **une barre Groupes + liens rapides vers Visiteurs/CSE (B)** ?
2. La rubrique **Groupes** doit-elle ouvrir **Tarifs groupes** en premier, ou le dernier sous-onglet visité ? La première option est explicite et prévisible ; la deuxième ajoute de l'état à gérer.
3. Quels modules sont utilisés chaque semaine et lesquels seulement quelques fois par an ? Cela détermine les rubriques à garder visibles dans le menu WordPress.
4. **Contenus & traductions**, **FAQ**, **IA & Google** et **Google Maps** doivent-ils rester des entrées visibles, ou rejoindre une rubrique secondaire ? Aucun regroupement n'est considéré comme validé sans décision.

**Conclusion :** le besoin n'est pas une fusion de pages, mais une **navigation transversale cohérente**, des **onglets-liens directs** et une meilleure hiérarchie visuelle. Le premier chantier doit se limiter à la navigation, sans changement des règles métier ni des données.
