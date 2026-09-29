# Audit / cadrage — IA & Google 1.18.0

Date : 29/09/2026

## Périmètre

Cette version traite uniquement le socle IA / Google. La refonte complète de la page FAQ & Contact reste prévue séparément en 1.19.0 (issue #77).

## Contexte vérifié

- Version de départ : 1.17.12.
- SEOPress 10.2 Free est actif sur le site de la Montagne des Singes d’après la capture fournie le 29/09/2026.
- SEOPress PRO est inactif sur cette capture.
- Les modules visibles Titres & Metas, SEO images/réglages avancés, Réseaux sociaux, Indexation instantanée, Plans de site XML & HTML et Outils sont activés.
- LiteSpeed Cache est présent ; SEOPress affiche un avertissement de compatibilité à contrôler.
- ACF PRO est actif.

## Décision d’architecture

Ne pas recréer les fonctions déjà couvertes par SEOPress : titres, méta-descriptions, Open Graph, réseaux sociaux, sitemaps et outils généraux SEO restent hors du périmètre de Gestion du parc.

Gestion du parc 1.18.0 ajoute uniquement les données que l’extension connaît déjà de façon canonique :

1. horaires saisonniers et périodes ;
2. fermetures / horaires exceptionnels ;
3. identité complémentaire du parc lorsque nécessaire ;
4. base de réponses officielles réutilisable par la future FAQ ;
5. diagnostics simples d’indexation sans forcer robots.txt.

## Données structurées

Le JSON-LD est généré uniquement :

- sur la page d’accueil ;
- sur les pages contenant les shortcodes publics Horaires / Tarifs / Calendrier.

Le type `TouristAttraction` est toujours utilisé. `LocalBusiness` n’est ajouté que si une adresse avec rue et ville est renseignée.

Les horaires ne sont jamais ressaisis. Ils sont convertis depuis les saisons existantes :

- `regular_periods` -> `openingHoursSpecification` ;
- dates de début/fin -> `validFrom` / `validThrough` ;
- deuxième créneau éventuel -> deuxième `OpeningHoursSpecification` ;
- exceptions -> `specialOpeningHoursSpecification` ;
- fermeture exceptionnelle -> `opens: 00:00` / `closes: 00:00`.

La visibilité annuelle reprend le moteur `Parcs_HT_Public_Visibility` lorsque disponible.

## Anti-doublon

La version ne génère ni `Organization`, ni `WebSite`, ni `Breadcrumb`, ni `FAQPage` afin de ne pas empiéter sur le plugin SEO / le thème.

Par sécurité, si SEOPress PRO est détecté par ses constantes / classe connues, la sortie JSON-LD propre à Gestion du parc est suspendue. Un filtre `parcs_ht_ai_google_emit_schema` permet à un développeur de modifier ce comportement après audit.

## Base de connaissances officielle

Une première réponse officielle est fournie dans la catégorie « Règles de visite » :

Question : « Peut-on nourrir les singes ? »

Réponse FR par défaut : « Les visiteurs ne nourrissent pas les singes. Les nourrissages sont assurés par les animateurs nature lors des nourrissages commentés. »

Les mots-clés `popcorn`, `pop-corn`, `nourrir`, `donner à manger` restent internes et ne sont jamais injectés dans le HTML public.

La réponse publique est affichée de façon discrète dans un `<details>` fermé par défaut sous le shortcode de page complète Horaires & Tarifs. Elle reste présente dans le HTML initial : aucune réponse n’est chargée uniquement après un clic.

Cette première structure doit être reprise / généralisée par la 1.19.0 plutôt que dupliquée.

## Robots / crawlers

La version ne modifie pas automatiquement robots.txt et ne contourne aucun pare-feu / CDN / règle anti-bot.

L’écran IA & Google fournit :

- l’état de la visibilité WordPress (`blog_public`) ;
- un lien vers le robots.txt public ;
- l’état de détection de SEOPress PRO ;
- un rappel que Googlebot et OAI-SearchBot doivent être contrôlés après installation sur le site réel.

Un fichier robots.txt statique ou une règle d’hébergement ne peut pas être confirmé depuis le seul code du plugin.

## Performance

- Aucun appel réseau côté public.
- Aucun nouveau JavaScript public.
- Le JSON-LD est calculé uniquement sur les pages pertinentes.
- Les données sont lues depuis les options WordPress déjà utilisées par l’extension.
- La base de connaissances 1.18.0 reste volontairement légère.

## Sécurité

- Nouvelle sauvegarde protégée par `manage_options` + nonce.
- URLs passées par `esc_url_raw`.
- E-mail passé par `sanitize_email`.
- Textes passés par les fonctions de sanitation WordPress.
- Sortie HTML échappée.
- JSON produit avec `wp_json_encode`.
- Aucune clé API ni secret ajouté.

## Points à vérifier après installation

1. source HTML : présence d’un seul bloc JSON-LD Gestion du parc sur l’accueil / Horaires ;
2. absence de `LocalBusiness` concurrent venant d’un autre plugin / thème ;
3. cohérence des horaires JSON-LD avec le calendrier public ;
4. exceptions / fermetures ;
5. FR / EN / DE ;
6. accordéon « Règles de visite » discret et présent dans le HTML initial ;
7. robots.txt : Googlebot et OAI-SearchBot non bloqués ;
8. cache LiteSpeed après sauvegarde ;
9. validation Schema.org / Google lorsque le type est pris en charge.

## Hors périmètre

- refonte complète FAQ / Contact ;
- copie de la fiche Google Maps ou des avis ;
- synchronisation Google Business Profile ;
- génération d’articles SEO ;
- dépendance à `llms.txt` ;
- modification automatique du robots.txt ;
- remplacement de SEOPress.
