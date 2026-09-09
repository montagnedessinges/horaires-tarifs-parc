# Points restant à débattre — Calendrier de l’Avent

Ce fichier ne contient plus les règles déjà figées dans `SPEC-FONCTIONNELLE.md` et `REFERENTIEL-IMPORT.md`.

Le cadrage fonctionnel de l’affichage public est désormais suffisamment stable pour commencer le développement.

## Décisions désormais figées

Sont considérés comme validés et ne doivent plus être réinventés pendant le développement :

- `[parc_calendrier_avent]` rend uniquement un bloc, jamais une page WordPress complète ;
- `[parc_reglement_avent]` rend uniquement le règlement dynamique de la campagne active ;
- aucune URL MDS/FDS n’est codée en dur ;
- page calendrier et page règlement ont des URL configurables ;
- avant le 1er décembre : un seul visuel teasing public + grille fermée ;
- les multiples teasings servent principalement aux publications sociales ;
- arrivée sur la page : aucune case ouverte automatiquement ;
- le jour courant est seulement mis en avant et devient cliquable à son heure d’ouverture ;
- clic volontaire sur une case ouverte pour afficher son détail ;
- visuel 4:5 central, agrandissable, pouvant faire partie de la question ;
- jours futurs verrouillés, jours passés consultables ;
- jour J : question/lot/partenaire/visuel sans réponse ni gagnants ;
- résultat révélable au plus tôt à J+1 à l’heure prévue ;
- résultat saisi à l’avance mais non publié reste invisible ;
- si l’heure de révélation est passée sans résultat publié : texte `tirage non effectué` configurable ;
- les gagnants Facebook et Instagram sont ensuite visibles dans l’archive ;
- une case `indice du mot mystère` ouvre les champs lettre + position ;
- l’indice réel reste serveur le jour J ;
- le rappel du grand jeu est automatiquement ajouté au texte social d’un jour avec indice ;
- l’indice lettre + numéro est révélé avec le résultat du jour ;
- toutes les publications quotidiennes générées renvoient vers la page centrale du calendrier ;
- `Comment participer ?` ouvre une explication courte intégrée au bloc ;
- un bouton séparé ouvre la page du règlement complet ;
- tous les textes publics ont des valeurs par défaut mais restent modifiables ;
- jour 24 : jeu quotidien + grand jeu final distinct ;
- mot mystère validé côté serveur ;
- formulaire final rendu seulement après mot correct ;
- shortcode du formulaire final non exposé avant autorisation ;
- aucune publication automatique vers Facebook/Instagram ;
- génération admin des posts, commentaires résultat et Stories à copier manuellement.

## 1. Langues du site

Décision actuelle :

- français obligatoire ;
- EN/DE facultatifs ;
- réseaux sociaux principalement en français.

Reste à finaliser avant release : comportement exact si une traduction manque.

Options :

- fallback français avec courte notice ;
- masquer le contenu non traduit ;
- exiger la traduction avant publication.

Ce point n’empêche pas le développement du moteur FR et de l’architecture multilingue.

## 2. Dates exactes de la campagne 2026

Les dates/heures doivent toutes être configurables.

À renseigner plus tard dans les données réelles :

- heure définitive d’ouverture quotidienne ;
- date/heure de fermeture du grand jeu ;
- date de révélation du mot mystère complet ;
- date/heure de révélation du résultat du jour 24.

Aucune de ces valeurs ne doit être codée en dur.

## 3. Formulaire final / RGPD

Le mécanisme technique est figé : validation serveur du mot puis rendu du shortcode de formulaire.

Reste à décider pour le vrai formulaire :

- champs obligatoires ;
- téléphone facultatif ou obligatoire ;
- adresse nécessaire ou non ;
- newsletter éventuelle ;
- politique de doublons ;
- texte RGPD exact ;
- durée de conservation des données.

Ces choix peuvent être finalisés après le premier prototype.

## 4. Apparence fine de la grille

La mécanique est figée, mais le style peut être ajusté après aperçu :

- nombre exact de colonnes selon largeur ;
- couleurs des états fermé/ouvert/aujourd’hui ;
- animation éventuelle ;
- taille exacte des numéros ;
- détail inline ou panneau animé, tant que l’utilisateur reste dans le même bloc et qu’aucun jour ne s’ouvre automatiquement.

Le premier prototype doit privilégier mobile, lisibilité et simplicité.

## 5. Interface admin fine

Principe figé : grille 24 jours, éditeur d’un seul jour à la fois, vues séparées Campagne / Teasings sociaux / Calendrier / Grand jeu / Partenaires / Résultats / Import.

Reste ajustable après test :

- panneau latéral, modale admin ou écran dédié ;
- badges/couleurs ;
- actions rapides ;
- ergonomie exacte du bouton `Publier le résultat`.

## 6. Modèles de textes sociaux

La logique est figée : générateur + aperçu + copie manuelle + overrides.

Reste à affiner avec les vrais contenus 2026 :

- formulation exacte Facebook ;
- formulation exacte Instagram ;
- longueur cible ;
- hashtags ;
- style de Story résultat ;
- textes par défaut du rappel grand jeu et du lien vers la page calendrier.

Tous ces textes sont modifiables sans changer le code.

## 7. Import

Référentiel courant : `REFERENTIEL-IMPORT.md`, cadrage **0.7**, `schema_version = 3`.

Reste à choisir techniquement :

- bibliothèque XLSX ;
- prise en charge exacte du CSV en production ;
- export inverse vers XLSX ;
- niveau de tolérance aux colonnes inconnues.

Un CSV de démonstration pourra être utilisé dès qu’une première version de l’import existe pour tester visuellement le rendu.

## 8. Visuels futurs et confidentialité

Règle figée : le module ne doit pas exposer l’URL d’un visuel futur dans ses payloads publics avant ouverture.

Reste à évaluer si une protection supplémentaire des médias WordPress futurs est souhaitée. Une image déjà publique dans la médiathèque ne peut pas être considérée comme absolument secrète simplement parce que le shortcode ne l’affiche pas encore.

## Feu vert fonctionnel

Les points restants ci-dessus ne bloquent pas le démarrage du développement du module.

Le chat de développement doit prendre `README.md`, `SPEC-FONCTIONNELLE.md`, `REFERENTIEL-IMPORT.md`, `PLAN-DEVELOPPEMENT.md` et `HANDOFF-DEVELOPPEMENT.md` comme sources canoniques et ne pas revenir aux anciennes décisions remplacées.
