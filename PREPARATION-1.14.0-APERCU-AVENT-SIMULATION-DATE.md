# Préparation 1.14.0 — Aperçu Calendrier de l’Avent avec simulation de date

Date : 7 septembre 2026
Référence principale : `PREPARATION-1.14.0-CALENDRIER-AVENT-ET-BASCULE-TARIFS.md`
Statut : exigence fonctionnelle validée pour la préparation de la 1.14.0. Aucune fonctionnalité n’est considérée comme livrée avant publication réelle d’une release GitHub et de son ZIP de production.

## Décision validée

Le Calendrier de l’Avent doit être relié au sélecteur de date/heure déjà présent dans l’onglet **Aperçu** de l’extension.

L’aperçu ne doit pas utiliser obligatoirement la date réelle du serveur pour décider quelles cases de l’Avent sont ouvertes. En mode administration / aperçu, la date et l’heure choisies par l’administrateur deviennent la **date de simulation** du calendrier de l’Avent.

Le site public, lui, continue d’utiliser la vraie date/heure WordPress dans le fuseau du parc. La date simulée ne doit jamais modifier le comportement public ni les données enregistrées de la campagne.

## Objectif

Permettre de préparer et contrôler intégralement une campagne avant de mettre le shortcode sur une page publique.

Exemples attendus :

- simulation avant le lancement : afficher le teaser ou l’état pré-campagne prévu ;
- simulation au 1er décembre : seule la première case doit être accessible ;
- simulation au 15 décembre : jours 1 à 15 accessibles, jours 16 à 24 verrouillés ;
- simulation au 24 décembre : les 24 journées doivent être accessibles et le grand jeu doit suivre ses propres dates d’ouverture ;
- simulation pendant la période du grand jeu : formulaire final / étape mot mystère selon la configuration ;
- simulation après clôture : résultats, réponses, gagnants et révélation des indices selon la date de révélation configurée.

## Intégration avec l’aperçu existant

Le moteur actuel d’aperçu dispose déjà d’un champ date (`data-htp-preview-date`) et d’un champ heure (`data-htp-preview-time`). La 1.14.0 doit réutiliser ce contexte au lieu de créer un deuxième sélecteur spécifique à l’Avent.

Le Calendrier de l’Avent doit recevoir un contexte de rendu explicite, par exemple :

- mode public : date/heure WordPress réelle ;
- mode aperçu : date/heure simulée sélectionnée dans l’onglet Aperçu.

Le moteur Avent doit rester unique : même fonction de décision des états, avec seulement la source de temps qui change selon le contexte.

## États à vérifier dans l’aperçu

À la date simulée, l’administrateur doit pouvoir contrôler au minimum :

- teaser actif ;
- campagne ouverte ou non ;
- cases disponibles ;
- cases futures verrouillées ;
- journée courante mise en avant ;
- visuels ;
- question / QCM / vrai-faux / observation ;
- partenaire et lot ;
- boutons Facebook / Instagram ;
- présence du pictogramme du grand jeu ;
- indices visibles ou encore secrets selon l’état ;
- réponse quotidienne publiée ou non ;
- gagnant Instagram ;
- gagnant Facebook ;
- ouverture du mot mystère ;
- formulaire final ;
- fermeture des participations ;
- révélation finale et mode archive.

## Exigence de sécurité

La simulation n’est qu’un outil d’administration.

Elle ne doit jamais :

- modifier la date système ;
- déverrouiller une journée sur le site public ;
- exposer le mot mystère ou des jours futurs à un visiteur ;
- enregistrer la date simulée comme date réelle de la campagne ;
- contourner les contrôles serveur du frontend public.

Les endpoints du frontend public doivent continuer à vérifier la vraie date/heure du site. Un éventuel endpoint d’aperçu doit être réservé aux utilisateurs disposant des capacités d’administration et protégé par nonce.

## Exigence de test

Ajouter des tests de régression garantissant au minimum :

1. `01/12` simulé => seul le jour 1 est ouvert ;
2. `15/12` simulé => jours 1 à 15 ouverts ;
3. `24/12` simulé => 24 journées accessibles ;
4. une date simulée après clôture révèle seulement les éléments autorisés par la configuration ;
5. la simulation admin n’a aucun effet sur le rendu public ;
6. le moteur de rendu utilisé dans Aperçu reste le même que celui du shortcode public.

## UX souhaitée

Le fonctionnement doit rester simple : l’administrateur sélectionne une date et une heure dans l’aperçu existant puis clique sur **Actualiser l’aperçu**. Tous les composants sensibles au temps, y compris le Calendrier de l’Avent, se mettent à jour sur cette même simulation.

Il ne faut pas demander de régler séparément une date pour les horaires et une autre pour le Calendrier de l’Avent.
