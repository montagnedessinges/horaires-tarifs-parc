# Audit 1.17.1 — Apparence globale, héritage et paramètres visuels

Date : 18/09/2026
Issue de suivi : #51

## Objectif

Avant de développer la nouvelle page « Administration générale », inventorier les réglages visuels réellement utilisés afin de construire un système cohérent : une apparence globale par défaut, puis des personnalisations locales uniquement lorsqu’elles sont utiles.

Le but n’est pas de supprimer la personnalisation fine. Le but est d’éviter que chaque module recopie ses propres couleurs, boutons, cartes et bordures alors que, dans la majorité des cas, le parc souhaite un rendu homogène.

## Constat général

Le plugin possède déjà une base de design globale dans `settings.general` et dans les variables CSS `--htp-*`, mais cette base s’est construite progressivement. Plusieurs modules disposent ensuite de leur propre palette, parfois avec les mêmes concepts sous des noms différents.

Le problème principal n’est donc pas l’absence de réglages : c’est leur dispersion et l’absence d’un mécanisme explicite « hériter du global / personnaliser ce module ».

## 1. Réglages globaux existants

Source principale : `includes/class-parcs-ht-defaults.php` + `includes/class-parcs-ht-admin.php`.

Déjà globaux :

- `primary_color`
- `secondary_color`
- `accent_color`
- `highlight_color`
- `body_text_color`
- `heading_text_color`
- `border_color`
- `block_spacing`
- `block_border_enabled`
- profil typographique et tailles avancées (`font_*`)

Ces valeurs alimentent déjà de nombreuses variables CSS dans `Parcs_HT_Shortcodes::style_variables()` :

- `--htp-primary`
- `--htp-secondary`
- `--htp-accent`
- `--htp-highlight`
- `--htp-body-text`
- `--htp-heading-text`
- `--htp-border`
- variables calendrier, tarifs, paiements, boutons et alertes.

Conclusion : il ne faut pas créer un deuxième système global. Il faut transformer cette base existante en référentiel visuel canonique.

## 2. Statut du jour / Horaires / Calendrier

Stockage actuel : principalement `settings.general`, avec certaines couleurs métier portées par les périodes elles-mêmes.

Réglages visuels existants :

- texte et fond du bloc « Aujourd’hui » ;
- couleur OUVERT ;
- couleur FERMÉ ;
- texte de détail ;
- titre calendrier et son fond ;
- jours de semaine ;
- fond des jours ouverts ;
- boutons de navigation des mois ;
- mois actif ;
- texte/bordure du détail d’une journée ;
- jour fermé ;
- contour du jour sélectionné ;
- couleur propre de chaque période d’ouverture (`regular_periods[].color`) ;
- couleur/cadre des jours fériés ;
- couleur des périodes repères ;
- couleur propre des règles d’accès limité.

Décision proposée :

- Navigation des mois, boutons, bordures et textes génériques doivent pouvoir hériter du thème global.
- Les couleurs qui représentent un état ou une donnée doivent rester locales : horaires/périodes, ouvert/fermé, jours fermés, événement, accès limité, exception.
- Les cases du calendrier restent en fond uni lorsque leur état/horaire le demande, conformément au fonctionnement actuel.

## 3. Tarifs visiteurs

Sources :

- `includes/class-parcs-ht-admin.php`
- `includes/class-parcs-ht-tariff-display.php`
- `assets/tariffs-ui.css`

Réglages généraux existants :

- titre tarifs ;
- titre moyens de paiement ;
- fond/texte/icône/bordure des paiements ;
- onglets + onglet actif ;
- panneaux ;
- prix ;
- notes ;
- bouton devis ;
- bouton Acheter ;
- couleurs et fonds de titres.

Réglages très fins existants :

- chaque moyen de paiement peut avoir son propre fond, texte, icône, bordure et transparence ;
- chaque ligne tarifaire peut avoir sa couleur de libellé, sous-titre, note, prix, fond et séparateur.

Le CSS `tariffs-ui.css` possède aussi des valeurs de structure codées directement : rayons, épaisseurs, espacements, forme « pilule » des onglets/boutons, etc.

Décision proposée :

- Le composant Tarifs doit consommer les tokens globaux par défaut pour boutons, onglets, cartes, bordures et couleurs communes.
- Les styles ligne par ligne restent possibles mais deviennent des overrides avancés.
- Les moyens de paiement doivent utiliser un style global commun par défaut ; une personnalisation par moyen reste disponible uniquement si nécessaire.
- Les tarifs visiteurs et groupes doivent partager le même langage visuel par défaut.

## 4. Tarifs groupes

Source : `includes/class-parcs-ht-group-tariff-settings.php`.

Store séparé : `parcs_ht_group_tariff_settings`, par saison.

Apparence locale actuelle :

- titre tarifs ;
- fond du titre ;
- titre moyens de paiement ;
- fond du titre paiement ;
- fond/texte/icône/bordure des moyens de paiement ;
- panneaux ;
- prix ;
- notes groupes ;
- bouton ;
- styles par ligne.

Historiquement, cette apparence a été séparée pour autoriser un rendu indépendant des tarifs visiteurs. C’est utile, mais cette indépendance ne doit plus être le comportement obligatoire.

Décision proposée :

- ajouter à terme un mode `global` / `custom` ;
- `global` doit être le comportement par défaut des nouvelles configurations ;
- `custom` conserve l’apparence actuelle spécifique ;
- ne jamais supprimer brutalement les valeurs existantes ; une installation déjà personnalisée doit continuer à rendre exactement pareil après migration.

## 5. Portail Groupes

Source : `includes/class-parcs-ht-group-portal.php`.

Constat important : le portail injecte actuellement en inline :

- `--htp-primary:#006757`
- `--htp-highlight:#e7c55b`

Les onglets du portail ont donc encore des couleurs/fallbacks locaux au lieu d’hériter proprement du système global.

Décision proposée : le portail Groupes doit utiliser les mêmes tokens d’onglets/boutons que Tarifs et ne conserver aucun thème local par défaut.

## 6. Guides pédagogiques

Sources :

- `includes/class-parcs-ht-guide-appearance.php`
- `assets/pedagogical-guides.css`

Store séparé : `parcs_ht_guide_appearance`.

Réglages actuels :

- fond de carte ;
- texte ;
- titre ;
- catégorie ;
- fond bouton principal ;
- texte bouton principal ;
- bouton secondaire.

Le CSS fixe encore directement plusieurs éléments structurels : rayons des cartes, rayons des boutons, bordures, ombres de badges, etc.

Décision proposée :

- cartes et boutons héritent du système global ;
- garder un mode personnalisé pour les Guides ;
- le format d’image 3:4 et les contraintes propres aux documents restent spécifiques, car ce ne sont pas des éléments de thème.

## 7. Page Devis groupes

Sources :

- `includes/class-parcs-ht-shortcodes.php`
- `assets/frontend.css`
- `assets/quote-gate.css`

Le rendu est déjà volontairement assez neutre : fonds transparents, texte hérité du thème, bordures discrètes.

Cependant, plusieurs styles restent codés directement :

- cartes avec rayon 12px ;
- boutons secondaires en pilule ;
- espacements ;
- bordures ;
- cartes d’accès au formulaire avec rayon 10px.

Les « messages importants » possèdent une couleur propre par bloc (`quote_page.important_messages[].color`). Cette couleur est sémantique et doit rester personnalisable localement.

Décision proposée : cartes et boutons du devis utilisent les tokens globaux ; les couleurs des messages importants restent locales.

## 8. Pop-up / Alertes

Sources :

- `includes/class-parcs-ht-admin.php`
- `includes/class-parcs-ht-alerts.php`

Réglages locaux très complets déjà présents :

- fond ;
- titre ;
- texte ;
- bordure + épaisseur ;
- rayon ;
- bouton fond/texte/bordure ;
- bouton fermer ;
- overlay + opacité ;
- ombre ;
- tailles de texte.

Décision proposée :

- le pop-up peut hériter des couleurs globales pour fenêtre/bouton par défaut ;
- overlay, bouton fermer, rayon, ombre et opacité restent des réglages spécifiques au pop-up ;
- conserver un mode « personnaliser le pop-up » pour les installations existantes.

## 9. Calendrier de l’Avent

Sources :

- `includes/class-parcs-ht-advent-appearance.php`
- `assets/advent.css`

Store séparé par campagne : `parcs_ht_advent_appearance`.

Palette :

- primary ;
- secondary ;
- open_day ;
- today ;
- locked ;
- special.

Ces couleurs portent en partie une information fonctionnelle.

Décision proposée :

- `primary` et `secondary` peuvent éventuellement hériter du thème global si vides ;
- `open_day`, `today`, `locked`, `special` restent propres à la campagne ;
- ne pas forcer le thème global sur les états du Calendrier de l’Avent.

## 10. Périodes / événements / accès limité

Constats :

- une période habituelle a une couleur calendrier ;
- une période repère a une couleur ;
- un accès limité a une couleur ;
- les événements actuels ont encore certains choix de couleur/repère imposés ;
- les jours fériés ont un cadre spécifique.

Décision : ces couleurs décrivent un type d’information et ne doivent pas être remplacées par une couleur de marque unique. Elles peuvent recevoir un défaut cohérent, mais restent des réglages métier.

## 11. Système cible recommandé

### A. Apparence globale dans Administration générale

Créer une section simple avec des réglages réellement transversaux :

#### Identité
- couleur principale ;
- couleur secondaire ;
- accent ;
- mise en valeur ;
- texte principal ;
- texte secondaire/muted ;
- bordure générique ;
- couleur des liens/focus si nécessaire.

#### Boutons
- bouton principal : fond, texte, bordure ;
- bouton secondaire : fond/transparent, texte, bordure ;
- rayon commun ;
- éventuellement hauteur/padding uniquement si cela apporte une vraie valeur.

#### Cartes / panneaux
- fond par défaut : transparent ;
- bordure ;
- rayon ;
- ombre facultative.

#### Chips / badges / onglets
- rayon ;
- fond/texte par défaut ;
- état actif basé sur primary/highlight.

#### Espacement / typographie
- conserver les réglages actuels déjà présents ;
- ne pas multiplier les contrôles sans nécessité.

### B. Héritage par module

Chaque module avec apparence propre doit avoir :

- `Utiliser l’apparence globale` — recommandé / défaut ;
- `Personnaliser ce module` — affiche uniquement les overrides locaux.

Modules concernés :

- Tarifs visiteurs ;
- Tarifs groupes ;
- Guides ;
- Pop-up ;
- Devis ;
- éventuellement Calendrier de l’Avent uniquement pour primary/secondary.

Le Calendrier/horaire conserve ses couleurs d’état et de périodes.

### C. Principe technique

Ne pas copier les valeurs globales dans tous les stores.

La cascade doit être calculée au rendu :

1. token global ;
2. override du module si présent ;
3. override de l’élément (ligne, moyen de paiement, événement…) si présent ;
4. fallback de compatibilité historique.

Exemple :

`global.button.primary.bg` → `tariffs.override.button.primary.bg` → valeur spécifique éventuelle.

Ainsi, changer la couleur globale met réellement à jour tous les modules qui héritent du global.

## 12. Tokens globaux proposés

Noms conceptuels à stabiliser avant codage :

- `color.primary`
- `color.secondary`
- `color.accent`
- `color.highlight`
- `text.primary`
- `text.muted`
- `border.default`
- `link.color`
- `focus.color`
- `button.primary.bg`
- `button.primary.text`
- `button.primary.border`
- `button.secondary.bg`
- `button.secondary.text`
- `button.secondary.border`
- `button.radius`
- `card.bg`
- `card.border`
- `card.radius`
- `card.shadow`
- `chip.bg`
- `chip.text`
- `chip.border`
- `chip.radius`
- `tabs.bg`
- `tabs.text`
- `tabs.active_bg`
- `tabs.active_text`
- `spacing.block`

En CSS, conserver la convention `--htp-*` afin de ne pas créer une seconde nomenclature concurrente.

## 13. Migration sans changement visuel

Règle absolue : la 1.17.1 ne doit pas changer le rendu d’une installation existante simplement parce que le nouveau système existe.

Stratégie :

1. introduire le référentiel global en mappant d’abord les clés actuelles ;
2. ne supprimer aucune ancienne clé ;
3. pour chaque module possédant déjà une apparence locale, détecter les valeurs existantes ;
4. si elles correspondent à une personnalisation réelle, conserver le module en mode `custom` ;
5. si le module n’a jamais été personnalisé, permettre l’héritage global ;
6. seulement dans une version de nettoyage ultérieure, supprimer les anciens chemins devenus réellement inutiles.

## 14. Priorités pour 1.17.1

La 1.17.1 ne doit pas tenter de convertir tout le front en une seule fois.

Elle doit :

1. créer l’Administration générale ;
2. centraliser saisons + publication annuelle ;
3. exposer l’Apparence globale ;
4. définir le service/les tokens canoniques ;
5. brancher en priorité les composants communs les plus visibles : boutons, cartes/panneaux, onglets, bordures ;
6. conserver les anciens overrides ;
7. laisser les refontes détaillées de chaque module aux versions 1.17.2+ prévues par la roadmap.

## Conclusion

L’audit confirme qu’un système global est pertinent et qu’il peut être construit sans réécrire les moteurs métier.

Le point important est de ne pas « fusionner tous les réglages ». Il faut au contraire créer une cascade claire : **global par défaut, module si nécessaire, élément si nécessaire**.

C’est ce modèle qui permettra d’avoir, par exemple, les mêmes cartes et les mêmes boutons sur Tarifs visiteurs, Groupes, Guides et Devis sans avoir à ressaisir les mêmes couleurs quatre fois, tout en conservant la possibilité de faire une exception ciblée.
