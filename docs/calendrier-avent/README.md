# Calendrier de l’Avent — dossier de cadrage

Ce dossier est le point d’entrée canonique pour toute conversation ChatGPT qui travaille sur le futur module **Calendrier de l’Avent** de l’extension `horaires-tarifs-parc`.

## État réel du plugin

- La version **1.15.0** a introduit le premier prototype fonctionnel du Calendrier de l’Avent selon le cadrage 0.7 / `schema_version = 3`.
- L’audit du 9 septembre 2026 a identifié un défaut d’architecture de navigation dans cette première release : le module existait mais son accès administrateur était insuffisamment visible, et la table Shortcodes utilisait une injection JavaScript dédiée.
- La correction structurelle **1.15.1** remplace ces mécanismes par une navigation WordPress native et une table Shortcodes alimentée directement par le registre central. Voir `AUDIT-ADMIN-1.15.0.md`.
- Une version n’est considérée comme livrée que lorsqu’elle est publiée dans GitHub Releases avec son ZIP de production vérifié.

## État du cadrage fonctionnel

Le débat d’affichage public du 9 septembre 2026 est désormais figé dans ce dossier.

Référentiel d’import courant : **0.7**, `schema_version = 3`.

Le cadrage est suffisamment avancé pour lancer le développement d’un premier prototype fonctionnel. Les quelques points encore ouverts sont listés dans `POINTS-A-DEBATTRE.md` et ne bloquent pas le socle.

## Objectif

Construire un module réutilisable chaque année pour :

- La Montagne des Singes (`mds`) ;
- La Forêt des Singes (`fds`).

La même extension gère les deux installations avec campagnes et données strictement séparées.

Aucune donnée annuelle ou propre à un parc ne doit être codée en dur : année, mot mystère, dates, partenaires, lots, textes, visuels, horaires, gagnants, URL de page calendrier, URL de règlement ou shortcode de formulaire final viennent des données/réglages de campagne.

Le module prépare dans l’administration les textes prêts à copier pour les publications sociales, puis après tirage les commentaires de résultat et les textes Story. La publication Facebook/Instagram reste manuelle.

## Shortcodes prévus

- `[parc_calendrier_avent]` : bloc interactif principal du calendrier ;
- `[parc_reglement_avent]` : bloc du règlement complet de la campagne active.

**Règle absolue : un shortcode rend uniquement un bloc dans une page WordPress existante. Il ne crée jamais toute la page, son H1, son header/footer ou un CSS global.**

## Documents de ce dossier

- `REFERENTIEL-IMPORT.md` : contrat canonique des noms de champs et règles d’import/réimport ;
- `SPEC-FONCTIONNELLE.md` : comportement public, administration, sécurité, mot mystère, règlement et réseaux sociaux ;
- `PLAN-DEVELOPPEMENT.md` : ordre recommandé de développement et scénarios de test ;
- `POINTS-A-DEBATTRE.md` : uniquement les détails encore ouverts ;
- `HANDOFF-DEVELOPPEMENT.md` : consigne courte à relire par le chat qui commence réellement à coder.

## Sources de travail opérationnelles

Deux Google Sheets 2026 servent à préparer les campagnes :

- `Calendrier de l’Avent 2026 – Montagne des Singes – Suivi` ;
- `Calendrier de l’Avent 2026 – Forêt des Singes – Suivi`.

Dans chacun, la feuille `IMPORT plugin - référentiel` doit rester synchronisée avec `REFERENTIEL-IMPORT.md` avant développement de l’import.

Après le cadrage 0.7, les feuilles doivent porter `schema_version = 3` et refléter notamment :

- un seul teasing public sur le site ;
- grille non auto-ouverte ;
- visuel 4:5 central ;
- révélation résultat à J+1 ;
- gagnants visibles en archive ;
- indice lettre + position révélé avec le résultat ;
- URL page calendrier/règlement configurables ;
- explication `Comment participer ?` ;
- règlement dynamique ;
- formulaire final par shortcode rendu uniquement après validation serveur du mot.

## Principes figés

- campagnes `mds` / `fds` strictement isolées ;
- identifiants stables ;
- import intelligent avec rapport avant écriture ;
- conservation des visuels manuels au réimport vide ;
- 24 jours obligatoires ;
- teasings sociaux séparés du teasing public unique ;
- jeux quotidiens fermés : QCM, vrai/faux, choix multiples ;
- réponses, indices, mot mystère et configuration du formulaire final protégés côté serveur ;
- arrivée sur la page = grille visible, aucune case ouverte automatiquement ;
- jours futurs verrouillés, jours ouverts cliquables ;
- visuel 4:5 central et agrandissable ;
- jour J = jeu sans solution ;
- résultat à J+1 au plus tôt ;
- action explicite `Publier le résultat` ;
- gagnants Facebook/Instagram visibles dans l’archive une fois publiés ;
- jour avec indice = rappel social automatique + lettre/position secrètes jusqu’au résultat ;
- indice révélé avec le résultat ;
- toutes les publications quotidiennes générées renvoient vers la page calendrier configurable ;
- `Comment participer ?` intégré au bloc ;
- règlement complet sur page configurable via `[parc_reglement_avent]` ;
- jour 24 = jeu quotidien + grand jeu final distinct ;
- mot final validé serveur ;
- formulaire final non préchargé et rendu seulement après mot correct ;
- aucune publication automatique Meta ;
- aperçu admin piloté par date + heure simulées.

## Règle de coordination

Le chat spécialisé Calendrier de l’Avent sert au débat fonctionnel, aux contenus et au contrôle des données.

Le chat de développement doit relire ce dossier **avant toute modification du code** et considérer les documents les plus récents comme prioritaires.

Les anciennes idées remplacées — notamment plusieurs teasings publics et ouverture automatique du jour courant — ne doivent pas être réintroduites.

Le développement peut maintenant commencer sur cette base. Le premier prototype sera ensuite testé avec une campagne/CSV fictif afin d’ajuster le rendu avec l’utilisateur avant d’intégrer les données définitives 2026.
