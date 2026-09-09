# Calendrier de l’Avent — dossier de cadrage

Ce dossier est le point d’entrée canonique pour toute conversation ChatGPT qui travaille sur le futur module **Calendrier de l’Avent** de l’extension `horaires-tarifs-parc`.

## État réel du plugin

- Version publiée actuelle au moment de ce cadrage : **1.14.0**.
- La version 1.14.0 ne contient **pas** encore le module Calendrier de l’Avent.
- Il n’existe actuellement ni menu d’administration Avent, ni shortcode `[parc_calendrier_avent]` dans la version de production.
- Ne jamais considérer le module comme livré tant qu’une future version contenant réellement le code n’est pas publiée dans les Releases GitHub avec son ZIP de production.

## Objectif

Construire un module réutilisable chaque année pour :

- La Montagne des Singes (`mds`)
- La Forêt des Singes (`fds`)

La même extension doit gérer les deux installations, avec des campagnes et données strictement séparées.

Aucune donnée annuelle ou propre à un parc ne doit être codée en dur : année, mot mystère, dates, partenaires, lots, nombre de teasings, textes, visuels, horaires ou gagnants doivent venir des données de campagne.

Le module doit surtout simplifier le travail de préparation et de publication : il génère dans l’administration les textes prêts à copier pour les publications, puis après tirage les commentaires de résultat et les textes Story. La publication sur Facebook et Instagram reste manuelle.

## Documents de ce dossier

- `REFERENTIEL-IMPORT.md` : contrat des noms de champs et règles d’import/réimport.
- `SPEC-FONCTIONNELLE.md` : comportement public, administration, sécurité et réseaux sociaux.
- `PLAN-DEVELOPPEMENT.md` : ordre recommandé des mises à jour et dépendance avec l’onglet Aperçu.
- `POINTS-A-DEBATTRE.md` : décisions encore ouvertes ; le chat spécialisé Calendrier de l’Avent peut proposer des corrections ici avant développement.

## Sources de travail opérationnelles

Deux Google Sheets 2026 servent actuellement à préparer les campagnes :

- `Calendrier de l’Avent 2026 – Montagne des Singes – Suivi`
- `Calendrier de l’Avent 2026 – Forêt des Singes – Suivi`

Dans chacun, la feuille `IMPORT plugin - référentiel` constitue le brouillon opérationnel du schéma d’import. Le référentiel GitHub a été enrichi le 9 septembre 2026 après le cadrage 0.5 des feuilles, notamment pour préciser le workflow de génération des résultats et la gestion simplifiée des horaires. Les feuilles devront être resynchronisées avec `REFERENTIEL-IMPORT.md` avant le développement de l’import.

Le référentiel GitHub et les feuilles Google Sheets doivent rester synchronisés avant développement. En cas de modification fonctionnelle validée dans un autre chat, mettre à jour ce dossier puis reporter les nouveaux champs/règles dans les feuilles d’import concernées.

## Règle pour le chat spécialisé Calendrier de l’Avent

Le chat spécialisé peut :

1. lire ce dossier ;
2. contrôler la cohérence avec les Google Sheets ;
3. proposer ou consigner des corrections fonctionnelles ;
4. mettre à jour la documentation si l’utilisateur valide une nouvelle règle.

Il ne doit pas considérer qu’une fonctionnalité est développée simplement parce qu’elle est documentée ici.

Le développement du plugin reste une étape séparée, déclenchée explicitement par l’utilisateur.

## Principes déjà validés

- campagnes `mds` et `fds` strictement isolées ;
- identifiants stables pour campagnes, contenus, partenaires et résultats ;
- import Excel/Google Sheets avec mise à jour intelligente ;
- rapport créations / modifications / inchangés / avertissements / erreurs avant toute écriture ;
- conservation des visuels choisis manuellement dans WordPress lorsqu’un réimport ne fournit pas de remplacement ;
- 24 jours obligatoires pour une campagne Avent ; nombre de teasings libre ;
- vrais jeux quotidiens fermés uniquement : QCM, vrai/faux ou choix multiples ;
- réponses, indices et mot mystère considérés comme données sensibles côté serveur ;
- shortcode générique futur : `[parc_calendrier_avent]` ;
- affichage public piloté par date et heure ;
- par défaut, une seule date/heure principale sert à l’ouverture du contenu sur le site et de repère pour la publication sociale manuelle ;
- jours futurs verrouillés, jours passés consultables ;
- finale du mot mystère séparée du jeu quotidien du dernier jour ;
- lot quotidien du dernier jour distinct du grand lot du mot mystère ;
- validation du mot mystère côté serveur avant déblocage du formulaire final ;
- génération de textes Facebook / Instagram depuis les champs structurés ;
- aucune publication automatique sur les réseaux ;
- `texte_post_override_fr` conserve la priorité lorsqu’un texte manuel est souhaité ;
- après tirage, génération de commentaires résultat Facebook/Instagram et d’un texte Story avec boutons de copie ;
- `explication_reponse_fr` est facultatif et sert principalement à expliquer pourquoi la réponse est correcte dans le commentaire de résultat ;
- la relance de fin de commentaire s’adapte automatiquement à la disponibilité de la prochaine question ou à la fin du calendrier ;
- interface admin compacte avec grille 24 jours cliquable et teasings dans une vue dédiée ;
- l’Aperçu du plugin doit simuler le vrai rendu à une date + heure données, sans modifier le temps réel public.

## Règle de coordination

Le chat spécialisé Calendrier de l’Avent sert au débat fonctionnel et au contenu. Le chat de développement doit relire ce dossier avant de coder et ne doit pas inventer une règle manquante.
