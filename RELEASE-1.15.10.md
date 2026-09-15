## Gestion du parc 1.15.10

Cette version regroupe la vraie évolution multi-saisons initialement préparée avant la publication accidentelle de la 1.15.9.

### Saisons et affichage public
- dates de début et de fin d’affichage public par saison ;
- forçage manuel prioritaire pour tester ou publier une saison plus tôt ;
- sélecteur d’année uniquement lorsque plusieurs années sont réellement disponibles ;
- conservation de l’affichage actuel lorsqu’une seule année est visible.

### Tarifs individuels
- deux canaux fixes et lisibles : **En ligne** et **Sur place** ;
- un canal sans prix n’est pas affiché ;
- sauvegarde interne des anciennes colonnes avant normalisation ;
- aucun changement de structure imposé aux tarifs groupes.

### Groupes
- tarifs groupes disponibles dès que leur année est publiée, indépendamment des dates grand public ;
- horaires d’une future année affichables spécifiquement aux groupes ;
- nouveau shortcode `[parc_groupes_horaires_tarifs]` avec **Horaires d’ouverture** et **Tarifs groupes** ;
- ancien shortcode `[parc_tarifs_groupes]` conservé.

### Import CSV horaires & calendrier
- modèle CSV téléchargeable depuis l’administration ;
- import d’une saison complète ou partielle ;
- prise en charge des périodes d’ouverture habituelles, horaires exceptionnels, fermetures exceptionnelles, jours fériés, périodes repères, vacances scolaires et événements ;
- séparateurs `;`, `,` et tabulation acceptés ;
- seules les catégories réellement présentes dans le CSV sont remplacées ;
- création automatique d’une révision de sécurité avant chaque import.

### Calendrier de l’Avent
- campagnes indépendantes conservées ;
- anciennes éditions archivables et consultables par identifiant ;
- brouillons toujours privés.

### Compatibilité
- aucune saison existante n’est supprimée ;
- aucune donnée groupe existante n’est réinitialisée ;
- système privé de mise à jour GitHub et contrôle SHA-256 conservés ;
- compatibilité PHP 7.4, 8.1, 8.2 et 8.3 vérifiée par la CI avant publication.
