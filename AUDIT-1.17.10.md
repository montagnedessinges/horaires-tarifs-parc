# Audit final — Gestion du parc 1.17.10

La 1.17.10 cumule deux objectifs distincts :

1. le nettoyage final prévu de la série 1.17.x après les refontes 1.17.2 à 1.17.9 ;
2. le correctif transversal des sauvegardes d’administration signalé pendant l’audit.

## Navigation et couches historiques

### Retiré / désactivé comme couche active

- Le pont 1.17.2 « Périodes & événements » ne rend plus sa page intermédiaire : le slug `parcs-ht-periods` est rebondé sur l’écran métier canonique `Parcs_HT_Admin_Periods::page()` introduit en 1.17.4.
- L’ancienne page d’atterrissage `parcs-ht-communication` ne rend plus de seconde navigation intermédiaire : elle redirige vers la page Pop-up 1.17.9, qui contient déjà la navigation Pop-up / Calendrier de l’Avent.

### Conservé volontairement

- Les anciennes URLs `page=parcs-horaires-tarifs&tab=...` restent routées vers les nouveaux écrans. Elles sont conservées comme compatibilité de liens et de retours d’actions, pas comme seconde interface.
- `Parcs_HT_Tariff_Public_Fixes` et `Parcs_HT_Tariff_Shared_1168` restent présents car le tableau public partagé réutilise encore explicitement leurs renderers canoniques. Les retirer en 1.17.10 recréerait un second renderer ou modifierait le rendu public.
- Les normaliseurs/migrations d’anciennes installations sont conservés lorsqu’ils sont encore nécessaires à la compatibilité des données existantes.
- Les shortcodes historiques restent enregistrés.

## Groupes / Tarifs groupes / Devis groupes

La source annuelle des tarifs Groupes reste `Parcs_HT_Public_Visibility::group_tariff_years()` :

- elle exige une grille Groupes réellement exploitable via `group_tariff_grid_ready()` ;
- elle applique séparément `group_tariffs_visible` et la fenêtre automatique de publication ;
- elle ne dépend pas de `group_quotes_enabled` ni de l’état du moteur de devis.

Le portail Groupes et le tableau public partagé lisent tous les deux cette même liste d’années. Le devis conserve sa propre disponibilité annuelle et sa propre liaison de tarifs.

Le message historique « Les tarifs groupes ne sont pas disponibles pour cette année. » et le marqueur `data-group-tariff-unavailable` ne doivent plus être produits par le portail, le renderer partagé, le shortcode Groupes ou l’administration Devis.

## Isolation annuelle

Le contrôle 1.17.10 conserve les garanties précédentes :

- tarifs Groupes : grille de l’année demandée uniquement ;
- devis Groupes : `binding_for_year($year)` et `season_for_year($year)` sans fallback vers une autre année ;
- horaires, tarifs visiteurs et visibilité publique : année sélectionnée conservée ;
- changement 2026 / 2027 sans mélange de grille ou de liaison.

## Correctif des sauvegardes

Certains écrans reconstruisent volontairement une liste complète depuis le POST ; une ligne absente signifie alors « supprimer ». Une requête tronquée par PHP pouvait donc être confondue avec une suppression volontaire.

La 1.17.10 ajoute `Parcs_HT_Admin_Save_Guard_11710` :

- les formulaires protégés sont sérialisés côté navigateur dans un snapshot JSON compact ;
- le snapshot utilise une seule variable POST et contourne ainsi `max_input_vars` ;
- les fichiers éventuels restent envoyés comme fichiers ;
- le serveur reconstruit `$_POST` avant le handler métier ;
- un marqueur final confirme que la requête est complète ;
- si le snapshot ou le marqueur est incomplet, la requête est arrêtée avant toute écriture ;
- une suppression volontaire reste possible, y compris une liste explicitement vide.

La protection couvre les sauvegardes principales des écrans Administration générale, Contenus & traductions, Horaires, Périodes/événements, Tarifs visiteurs, Groupes, Devis, Guides, Pop-up, Calendrier de l’Avent et import CSV.

## Chargement et performances

Les nouveaux composants propres à 1.17.10 sont administratifs :

- le garde de sauvegarde et le nettoyage de navigation sont chargés depuis le bootstrap uniquement lorsque `is_admin()` est vrai ;
- le JavaScript du garde n’est chargé que sur les pages de l’extension ;
- aucun nouvel asset public n’est ajouté ;
- le chargement conditionnel historique du moteur de pop-up public reste conservé via `Parcs_HT_Defaults::has_popup_source_fast()`.

## Tests bloquants ajoutés ou renforcés

- contrat du garde anti-troncature ;
- test d’exécution de restauration du snapshot JSON ;
- suppression volontaire d’un tableau vide ;
- conservation du contexte annuel ;
- refus d’un snapshot destiné à une autre action ;
- contrat de nettoyage des pages-ponts ;
- source annuelle Groupes unique ;
- indépendance Tarifs Groupes / Devis Groupes ;
- absence du faux message d’indisponibilité et de son ancien marqueur ;
- conservation du renderer Groupes canonique ;
- isolation annuelle du devis.

La 1.17.10 ne doit être fusionnée ou publiée qu’après une CI complète verte sur les versions PHP supportées et revalidation du paquet de production.
