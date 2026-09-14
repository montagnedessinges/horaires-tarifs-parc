# Métadonnées Analytics — GA4 / Google Tag Manager

Cette couche ne contacte aucun service Analytics et n'envoie aucune donnée. Elle expose uniquement des métadonnées publiques dans le DOM afin que Google Tag Manager puisse décider quoi transmettre à GA4 selon la configuration de consentement du site.

## Principes

- Pas de paramètre `park` : chaque parc utilise sa propre propriété GA4.
- Aucun nom, prénom, e-mail, téléphone, adresse, organisme ou texte libre n'est exposé pour Analytics.
- Pas d'identifiant visiteur, pas de cookie, pas de fingerprinting et pas de transport direct vers Google.
- Les valeurs à forte cardinalité sont évitées : les dates de visite sont regroupées au mois pour les dimensions d'analyse.
- Les identifiants permanents déjà existants sont réutilisés, notamment pour les guides pédagogiques et le Calendrier de l'Avent.

## Événements principaux

| Événement | Module | Paramètres utiles |
| --- | --- | --- |
| `calendar_date_select` | Calendrier | `content_language`, `season_year`, `selected_month`, `day_status`, `has_event` |
| `event_cta_click` | Calendrier | `content_type`, `source`, `content_language`, `season_year`, `selected_month` |
| `document_download` | Calendrier / tarifs | `document_type`, `season_year`, `content_language` |
| `tariff_section_select` | Tarifs | `tariff_section`, `season_year`, `content_language` |
| `ticket_cta_click` | Tarifs | `source`, `season_year`, `content_language` |
| `special_offer_click` | Tarifs | `content_type`, `source`, `season_year`, `content_language` |
| `quote_cta_click` | Tarifs groupes | `source`, `season_year`, `content_language` |
| `quote_date_selected` | Devis | `visit_year`, `visit_month`, `content_language` |
| `quote_form_open` | Devis | `visit_year`, `visit_month`, `content_language` |
| `generate_lead` | Devis CF7 | `visit_year`, `visit_month`, `content_language`, `source` |
| `guide_view` / `guide_download` | Guides | `guide_id`, `cycle`, `season_year`, `language` via les attributs `data-guide-*` existants |
| `advent_day_open` | Avent | `campaign_id`, `content_id`, `day_number`, `content_language` |
| `advent_social_click` | Avent | `platform`, `campaign_id`, `content_id`, `content_language` |
| `advent_word_attempt` | Avent | `campaign_id`, `content_language` |
| `advent_word_result` | Avent | `result`, `campaign_id`, `content_language` |
| `advent_entry_submit` | Avent | `campaign_id`, `content_language` |

## Attributs DOM

Les modules principaux utilisent des attributs `data-ga-*`, par exemple :

```html
<button
  data-ga-event="calendar_date_select"
  data-ga-module="calendar"
  data-ga-season-year="2027"
  data-ga-selected-month="2027-05"
  data-ga-day-status="open"
  data-ga-has-event="yes"
  data-ga-content-language="fr">
</button>
```

Pour les vues ou réussites qui ne correspondent pas à un clic, les attributs suivants sont utilisés :

- `data-ga-view-event` : élément devenu visible, par exemple `quote_form_open` ou `advent_word_result`.
- `data-ga-submit-event` : soumission utilisateur, par exemple `advent_word_attempt`.
- `data-ga-success-event` : succès confirmé d'un formulaire, par exemple `generate_lead` ou `advent_entry_submit`.

## Guides pédagogiques

Le module possède déjà une structure plus précise que des attributs génériques :

- `data-guide-id`
- `data-guide-action` (`view` / `download`)
- `data-guide-season`
- `data-guide-lang`
- `data-cycle` sur la carte

Ces valeurs doivent être utilisées directement dans GTM afin de ne pas dupliquer les identifiants ni modifier le système statistique interne existant.

## Devis Contact Form 7

Le succès du formulaire doit être déclenché sur l'événement DOM `wpcf7mailsent`, et uniquement pour un formulaire situé dans `.parcs-ht-quote`.

Les champs personnels ne doivent jamais être transmis à GA4.

Les seules informations métier envisagées sont :

- type de groupe ;
- mois / année de visite ;
- tranche d'effectif ;
- langue.

La présente version expose déjà `visit_year`, `visit_month`, la langue et la source au niveau du formulaire. L'ajout futur de `quote_type` et `group_size` devra rester basé sur une liste blanche stricte et des tranches d'effectif.

## Calendrier de l'Avent

Le mot saisi par le visiteur n'est jamais exposé dans les métadonnées. Seul le résultat `success` / `failure` peut être utilisé pour l'analyse.

Les formulaires finaux peuvent être suivis par leur événement de succès CF7 sans transmettre les coordonnées du participant.

## Pop-up / alertes

Les pop-up existants possèdent des sélecteurs stables :

- `.parcs-ht-auto-modal` pour les pop-up automatiques ;
- `.parcs-ht-modal` pour les alertes en mode pop-up ;
- `.parcs-ht-auto-link` ou `.parcs-ht-modal .parcs-ht-button` pour leurs CTA.

Un déclencheur GTM « visibilité de l'élément » peut mesurer l'affichage sans modification du rendu. Les clics CTA peuvent être captés par un déclencheur de clic CSS.

## Tarifs groupes autonome

Le shortcode autonome expose déjà :

- `.parcs-ht-group-tariffs-only`
- `data-htp-lang`
- `.parcs-ht-panel-actions .parcs-ht-button` pour la demande de devis
- `.parcs-ht-special-buy` pour une offre spéciale

Ces sélecteurs suffisent pour GTM sans ajouter de JavaScript supplémentaire au shortcode.

## Dimensions personnalisées GA4 recommandées

À créer uniquement lorsque l'analyse le justifie :

- `module`
- `content_language`
- `season_year`
- `source`
- `content_type`
- `content_id`
- `selected_month`
- `day_status`
- `has_event`
- `tariff_section`
- `document_type`
- `visit_year`
- `visit_month`
- `school_cycle`
- `campaign_id`
- `day_number`
- `platform`
- `result`

Éviter de créer une dimension personnalisée pour une date exacte, une URL complète, un titre libre ou toute donnée saisie par un visiteur.
