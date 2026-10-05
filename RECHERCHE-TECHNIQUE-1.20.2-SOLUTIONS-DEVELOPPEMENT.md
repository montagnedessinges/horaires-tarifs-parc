# Recherche technique — solutions de développement pour la suite de 1.20.1

Date : 05/10/2026  
Base : `main` 1.20.1 (`bbbb663c54218a4ef26cd8e04c0a9d459998a1d6`)  
Issue : #94  
Statut : **recherche / cadrage uniquement — aucun code fonctionnel modifié**

## Objectif

Trouver des solutions de développement simples aux problèmes révélés par l’audit, sans refaire visuellement le site et sans ajouter une « couche IA » parallèle inutile.

Principe retenu :

> une information publique doit être produite correctement par le serveur et être consommable par le navigateur, les moteurs et les assistants ; une information non publique ne doit jamais quitter le serveur dans une surface publique.

La priorité n’est donc pas de « coder pour les robots », mais de rendre le comportement public réel robuste avec ou sans JavaScript.

---

## 1. Portail groupes : année visible pour l’humain mais dépendante du JavaScript

### Problème constaté

Le portail groupes utilise des boutons d’année et le JavaScript change le contenu du calendrier. Les données 2027 peuvent être publiques et visibles dans un navigateur, tout en étant difficiles à récupérer sans exécution JS.

Le fallback « Les horaires d’ouverture ne sont pas disponibles pour cette année » existe dans le DOM puis est masqué avec `hidden`. Un extracteur de texte peut donc récupérer le mauvais message.

### Solution recommandée : Progressive Enhancement sans changement visuel

Transformer les sélecteurs d’année en **vrais liens** vers un état serveur :

```php
$url = add_query_arg('htp_group_year', $year, $base_url);
echo '<a href="' . esc_url($url) . '" class="parcs-ht-group-portal-year">' . esc_html($year) . '</a>';
```

Le CSS peut conserver exactement l’apparence actuelle des boutons.

Au chargement de `?htp_group_year=2027`, PHP doit déjà rendre le calendrier et les tarifs 2027 correspondants. Ensuite seulement, JavaScript peut intercepter le clic pour garder une navigation instantanée si souhaité.

Pseudo-JS :

```js
link.addEventListener('click', function (event) {
  if (!canEnhanceInPlace(link)) return; // le lien reste le secours natif
  event.preventDefault();
  selectYear(link.dataset.year);
  history.replaceState(null, '', link.href);
});
```

Avantages :

- aucun changement visuel obligatoire ;
- fonctionne sans JavaScript ;
- les années deviennent de vraies ressources HTTP ;
- les robots savent découvrir un `<a href>` ;
- le bouton 2027 ne dépend plus d’un état uniquement présent dans la mémoire JS.

La page principale possède déjà une logique similaire avec `htp_year`; le portail groupes devrait réutiliser ce principe plutôt qu’entretenir un second modèle de navigation.

Sources :
- Google — liens explorables : https://developers.google.com/search/docs/crawling-indexing/links-crawlable
- Google — JavaScript SEO / SSR : https://developers.google.com/search/docs/crawling-indexing/javascript/javascript-seo-basics
- WordPress `add_query_arg()` : https://developer.wordpress.org/reference/functions/add_query_arg/

---

## 2. Calendrier mois / années : rendre l’état important adressable par URL

### Piste de correction

Le même principe peut être utilisé pour les changements d’année et, si nécessaire, de mois du calendrier :

```text
?htp_year=2027&htp_month=08
```

Le serveur sélectionne cet état dès le premier HTML. JavaScript peut ensuite enrichir la navigation.

Il n’est pas nécessaire de créer une nouvelle page WordPress par mois. Le but est uniquement que l’état public sélectionnable dans l’interface ait un équivalent HTTP réel.

À décider après tests :

- année uniquement si elle suffit à exposer correctement les périodes horaires ;
- année + mois seulement si le calendrier jour par jour reste réellement nécessaire à la découvrabilité.

Ne pas multiplier les URL sans besoin réel.

Source : Google recommande que les écrans/contenus significatifs d’une application JS disposent d’URL accessibles et que les liens soient des ancres `href` réelles.

---

## 3. Ne plus sérialiser les objets internes complets dans `window.ParcsHTPData`

### Problème

`Parcs_HT_Schedule::public_settings()` sélectionne des lignes `enabled`, puis transmet encore trop de propriétés au navigateur. Le JS finit le filtrage (`show_on_calendar`, jours fériés, tooltips, etc.).

Cela expose potentiellement :

- `internal_label` ;
- contenus non affichés ;
- métadonnées administratives ;
- objets qui ne devraient pas être publics dans le contexte courant.

### Solution recommandée : allowlist côté PHP

Ne jamais faire :

```php
$payload[] = $row;
```

Construire explicitement l’objet public nécessaire :

```php
$payload[] = array(
    'start' => (string) $row['start'],
    'end'   => (string) $row['end'],
    'open'  => (string) $row['open'],
    'close' => (string) $row['close'],
);
```

Pour les événements/périodes, appliquer les règles de visibilité **avant** la sérialisation :

```php
if ((string)($row['enabled'] ?? '0') !== '1') continue;
if ((string)($row['show_on_calendar'] ?? '1') !== '1') continue;
```

Même principe pour :

- `show_public_holidays` ;
- tooltip activé/désactivé ;
- alertes publiées et dans leur fenêtre ;
- exceptions ;
- règles de domaine.

La bonne architecture 1.20.0 des pop-up REST peut servir de modèle : filtrer complètement côté serveur, puis renvoyer un petit objet public.

Sources :
- OWASP API3:2023 recommande de sélectionner explicitement les propriétés retournées et de garder les réponses au strict minimum : https://api-security.owasp.org/editions/2023/en/0xa3-broken-object-property-level-authorization/
- OWASP WSTG — Excessive Data Exposure : https://wstg.owasp.org/latest/4-Web_Application_Security_Testing/12-API_Testing/03-Excessive_Data_Exposure/
- WordPress : pour des données génériques PHP → JS, `wp_add_inline_script()` est l’API recommandée plutôt que détourner la localisation : https://developer.wordpress.org/reference/functions/wp_localize_script/

### Important

Ne pas créer un troisième moteur de visibilité. `Parcs_HT_Public_Visibility` doit décider **si** la donnée est publique ; le renderer/projection existant ne fait ensuite que sélectionner les champs nécessaires.

---

## 4. `internal_label` : interdiction totale comme fallback public

### Problème

Deux chemins peuvent encore utiliser `internal_label` comme titre public de secours.

### Correction simple

Créer une seule règle de titre public :

```php
$title = Parcs_HT_Schedule::translation_exact($row['title'] ?? array(), $language);
if ($title === '') {
    $title = $kind === 'event'
        ? $labels['generic_event'][$language]
        : $labels['generic_period'][$language];
}
```

Ou ne rien rendre lorsqu’un titre n’est pas indispensable.

Règle :

```text
internal_label = administration uniquement
```

Ne pas envoyer `internal_label` au navigateur si le front n’en a pas besoin.

Ajouter un test qui recherche littéralement la valeur d’un faux libellé interne (« SECRET ADMIN TEST ») dans :

- HTML public ;
- payload JS ;
- PDF public ;
- JSON-LD ;
- REST/AJAX public.

Résultat attendu : zéro occurrence.

---

## 5. Fallbacks masqués avec `hidden` : ne pas générer un message faux puis le cacher

### Problème

Le serveur peut générer :

```html
<p hidden>Les horaires d’ouverture ne sont pas disponibles pour cette année.</p>
```

puis JavaScript enlève/ajoute `hidden` selon l’année.

Le navigateur respecte `hidden`, mais le texte existe toujours dans le document source/DOM et peut être extrait par des outils qui ne reproduisent pas exactement le rendu du navigateur.

### Correction

Le serveur doit choisir la bonne branche :

```php
if ($schedule_available) {
    echo $calendar;
} else {
    echo '<p class="parcs-ht-group-empty">...</p>';
}
```

Pour des années disponibles dans le même portail, éviter d’émettre simultanément les messages contradictoires. Si le JS change d’année, il peut créer le message à la demande ou utiliser un modèle neutre sans texte faux préinjecté.

Source MDN : `hidden` signifie seulement que le navigateur ne présente pas l’élément ; le contenu reste dans le document : https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Global_attributes/hidden

---

## 6. Bascule automatique 2026 → 2027 : une seule autorité

### Problème

Le code possède plusieurs chemins historiques :

- `Parcs_HT_Public_Visibility` ;
- `Parcs_HT_Display_Policy` ;
- `Parcs_HT_Group_Tariff_Settings::public_year()` ;
- `Parcs_HT_Tariff_Seasons::requested_year()`.

La visibilité annuelle centralisée existe déjà, mais certains renderers continuent de sélectionner leur année via les anciens chemins.

### Solution recommandée

Faire de `Parcs_HT_Public_Visibility` l’autorité unique pour les listes d’années :

```php
Parcs_HT_Public_Visibility::calendar_years();
Parcs_HT_Public_Visibility::retail_years();
Parcs_HT_Public_Visibility::group_schedule_years();
Parcs_HT_Public_Visibility::group_tariff_years();
Parcs_HT_Public_Visibility::quote_years();
```

Les anciennes classes peuvent rester responsables de leurs données / présentation, mais plus de décider seules si une année est publiquement sélectionnable.

Exemple pour les tarifs groupes :

```php
$years = Parcs_HT_Public_Visibility::group_tariff_years();
$year  = Parcs_HT_Public_Visibility::default_year($years, $requested);
```

puis lire les réglages visuels groupes de cette année.

Ne pas copier 2026 vers 2027 en dur. Chaque saison doit rester une entrée générique `seasons[year]` traitée par le même code.

---

## 7. Nouveau risque confirmé : bascule automatique + cache de page

### Constat de code

`Parcs_HT_Group_Tariff_Switch_Admin` possède déjà un mécanisme spécifique :

- `wp_schedule_single_event()` à la date de bascule ;
- hook cron ;
- `do_action('litespeed_purge_all')`.

En revanche, la fenêtre annuelle générale `public_display_from` / `public_display_until` n’a pas le même scheduler centralisé.

### Risque

Même si PHP calcule correctement que 2027 doit devenir visible à minuit, une page HTML mise en cache avant minuit peut continuer à servir l’ancien résultat jusqu’à expiration/purge.

### Solution recommandée

Remplacer le cron spécial « tarifs groupes » par un scheduler commun aux frontières de publication :

```text
public_display_from 2027
public_display_until 2026
```

Lors d’un enregistrement :

1. supprimer les anciens événements programmés pour la saison ;
2. programmer un événement juste après chaque frontière future ;
3. à l’exécution, purger le cache des pages concernées ;
4. éventuellement préchauffer les pages importantes ;
5. journaliser la bascule.

Pseudo-code :

```php
wp_clear_scheduled_hook('parcs_ht_public_visibility_boundary', array($year, 'from'));
wp_schedule_single_event($timestamp, 'parcs_ht_public_visibility_boundary', array($year, 'from'));
```

Puis :

```php
add_action('parcs_ht_public_visibility_boundary', function ($year, $boundary) {
    do_action('litespeed_purge_all', 'Horaires & Tarifs : bascule publique ' . $year);
});
```

À terme, préférer une purge ciblée d’URL si les pages concernées sont connues, plutôt qu’un `purge_all` systématique.

Attention : WP-Cron n’est pas un cron système exact ; l’événement est déclenché lors d’une visite après l’heure prévue. Pour une bascule à la minute près, un vrai cron serveur appelant WP-Cron est préférable. Pour ce plugin, il faut au minimum tester le comportement réel du site et documenter cette limite.

Sources :
- WordPress `wp_schedule_single_event()` : https://developer.wordpress.org/reference/functions/wp_schedule_single_event/
- LiteSpeed API, dont `litespeed_purge_all` et `litespeed_purge_url` : https://docs.litespeedtech.com/lscache/lscwp/api/

---

## 8. Alertes historiques : filtrer avant sortie, puis supprimer le code mort si possible

### Correction de projection

Une alerte ne doit quitter PHP que si toutes les règles publiques sont remplies :

```php
if ((string)($alert['enabled'] ?? '0') !== '1') continue;
if ((string)($alert['published'] ?? '0') !== '1') continue;
if (!is_inside_public_window($alert, $now)) continue;
```

Le navigateur ne doit pas recevoir une alerte future/non publiée pour ensuite décider de l’ignorer.

### Simplification

`class-parcs-ht-alerts.php` semble être un ancien moteur non chargé par le bootstrap 1.20.1. Avant suppression :

1. recherche globale des références ;
2. vérifier migrations / tests / anciens shortcodes ;
3. ajouter un test de contrat garantissant que le moteur actif est bien `Parcs_HT_Popup_1200` ;
4. supprimer le fichier et les tests réellement morts dans la même release de simplification, pas avant.

Objectif : moins de chemins historiques = moins de risques de divergence.

---

## 9. PDF public : `noindex` n’est pas une autorisation

### Problème

Un PDF peut être `noindex` tout en restant publiquement téléchargeable par son URL. Il faut donc contrôler l’année avant de générer le document.

### Correction

Dans l’endpoint public :

```php
$year = sanitize_text_field(...);
$allowed = Parcs_HT_Public_Visibility::calendar_years();
if (!in_array($year, $allowed, true)) {
    wp_die('Document indisponible.', '', array('response' => 404));
}
```

Ensuite seulement générer le PDF à partir d’une projection publique assainie, pas depuis `all_settings()` brut.

Conserver `X-Robots-Tag: noindex` si on ne veut pas indexer les PDF, mais ne pas le considérer comme une barrière d’accès.

Source Google : `noindex` empêche l’indexation mais la ressource reste accessible : https://developers.google.com/search/docs/crawling-indexing/control-what-you-share

---

## 10. AJAX devis : le nonce protège le CSRF, pas la confidentialité

### Problème

Le endpoint public peut actuellement calculer un statut ouvert/fermé d’une année dont le devis n’est pas public.

### Correction recommandée

Première décision du callback :

```php
if (!Parcs_HT_Public_Visibility::module_visible($year, 'group_quotes_enabled', false)) {
    wp_send_json_error(array('status' => 'unavailable'), 404);
}
```

Seulement après cette garde : calculer horaires/tarifs nécessaires au formulaire.

Réponse minimale : ne renvoyer que ce dont le formulaire a besoin.

WordPress précise explicitement qu’un nonce ne doit jamais servir d’authentification, d’autorisation ou de contrôle d’accès.

Sources :
- https://developer.wordpress.org/apis/security/nonces/
- https://developer.wordpress.org/reference/functions/check_ajax_referer/

---

## 11. REST public : conserver le modèle du moteur pop-up 1.20.0

Les pop-up constituent un bon exemple pour les autres modules :

```text
stockage complet interne
        ↓
filtre serveur public
        ↓
projection avec uniquement les champs nécessaires
        ↓
REST / HTML / JS
```

Pour toute route REST publique :

- `permission_callback` explicite ;
- même si la route est publique (`__return_true`), la callback ne doit exposer que la projection publique ;
- aucune décision de visibilité importante ne doit être laissée au JS.

Source : WordPress REST API — `permission_callback` : https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/

---

## 12. Temps / dates : rendre les tests déterministes

La visibilité du plugin dépend fortement de « aujourd’hui ».

Plusieurs classes appellent directement `wp_date()` ou créent `DateTimeImmutable('now')`. Cela complique les tests de bascule.

Deux solutions possibles sans gros framework :

### Option légère — déjà compatible avec une partie du code

Faire accepter un `$today` explicite aux fonctions métiers et ne lire l’horloge système qu’au bord :

```php
public static function module_visible($year, $flag, $fallback = false, $today = '')
```

Puis les tests injectent :

```text
2026-12-14
2026-12-15
2027-01-01
```

### Option plus structurée

Adopter le principe d’une horloge injectable (`ClockInterface`) si le projet devient plus complexe.

PSR-20 existe précisément pour rendre le temps prévisible en test : https://www.php-fig.org/psr/psr-20/meta/

Pour la prochaine correction, l’option légère est probablement suffisante et évite une dépendance supplémentaire.

---

## 13. Tests de non-régression à ajouter avant développement final

### A. Test « sans JavaScript »

Pour chaque shortcode public majeur, inspecter le HTML PHP brut avant exécution JS.

Cas groupes 2027 :

```text
année publiée + horaires renseignés
=> le HTML de l’URL 2027 contient bien les horaires 2027
=> aucun faux message « horaires non disponibles »
```

### B. Matrice de bascule annuelle

Simuler :

- J-1 avant activation 2027 ;
- jour exact d’activation ;
- J-1 avant retrait 2026 ;
- jour exact de retrait ;
- J+1.

Contrôler :

- calendrier ;
- tarifs visiteurs ;
- horaires groupes ;
- tarifs groupes ;
- devis ;
- payload JS ;
- PDF public.

### C. Test de fuite de données

Créer des fixtures volontairement reconnaissables :

```text
internal_label = SECRET_ADMIN_TEST
show_on_calendar = 0
published = 0
```

Puis tester que `SECRET_ADMIN_TEST` et les données masquées n’apparaissent dans aucune sortie publique.

### D. Test cache / frontière de date

Vérifier que l’enregistrement d’une frontière future programme bien une purge et que la suppression/modification de la date remplace le cron précédent sans doublon.

### E. Test liens réellement navigables

Vérifier que chaque année publique pertinente a un vrai `href`, et que son URL renvoie le bon contenu même avec JS désactivé.

### F. Test endpoints

Année non publique :

- PDF → pas de document ;
- AJAX devis → pas de statut détaillé ;
- REST → pas de propriétés masquées.

---

## 14. Ce qu’il vaut mieux NE PAS ajouter

À ce stade, ne pas ajouter :

- une deuxième base de données « IA » ;
- un endpoint spécial ChatGPT/Gemini ;
- une détection User-Agent de robots ;
- un second calendrier invisible ;
- une copie statique annuelle maintenue séparément ;
- du texte SEO masqué ;
- une duplication manuelle du code 2026 vers 2027.

Google recommande aujourd’hui le rendu serveur/statique/hydratation plutôt que le rendu dynamique spécifique aux robots : https://developers.google.com/search/docs/crawling-indexing/javascript/dynamic-rendering

Le meilleur résultat serait que **le même code public** fonctionne pour :

- navigateur avec JS ;
- navigateur sans JS ;
- lecteur d’écran ;
- crawler ;
- assistant IA utilisant une recherche web.

---

## 15. Priorités proposées pour la future release

### P0 — correction

1. unifier la décision de visibilité annuelle ;
2. corriger le portail groupes pour qu’une année publique soit rendue côté serveur ;
3. supprimer les faux fallbacks présents puis masqués ;
4. filtrer/whitelister `window.ParcsHTPData` avant sérialisation ;
5. interdire `internal_label` dans toute sortie publique.

### P1 — robustesse

6. garde publique explicite PDF / AJAX ;
7. centraliser la purge de cache aux frontières de publication ;
8. filtrer les alertes historiques côté serveur ;
9. faire réutiliser au portail groupes la projection canonique existante.

### P2 — simplification

10. vérifier puis supprimer `class-parcs-ht-alerts.php` si réellement mort ;
11. vérifier la dépendance restante à `Parcs_HT_AI_Google::knowledge` puis préparer sa suppression/migration définitive ;
12. réévaluer la couche `Parcs_HT_Calendar_Semantic` après les corrections : la conserver seulement si les tests externes montrent une valeur ajoutée mesurable.

## Verdict de recherche

La documentation consultée ne pousse pas à ajouter davantage de couches. Elle renforce plutôt la direction suivante :

**rendre le HTML public réellement fonctionnel côté serveur, rendre les états navigables par de vraies URL/liens, filtrer les données avant de les envoyer au client, et n’utiliser JavaScript que comme amélioration d’interface.**

C’est cohérent avec l’objectif du projet : améliorer la fiabilité pour les visiteurs, Google et les IA sans modifier inutilement l’apparence du site.