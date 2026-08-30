# Mise à jour 1.9.11 — accès au devis piloté par la date

## Décision fonctionnelle validée le 30/08/2026

Le formulaire CF7 reste le générateur automatique de devis. Le plugin ne remplace pas CF7 et l'équipe ne fabrique pas manuellement le devis.

Parcours public :
1. le visiteur choisit d'abord sa date de visite ;
2. le plugin vérifie la grille de tarifs groupes correspondant à l'année de cette date ;
3. si la grille est disponible, le reste du formulaire CF7 s'affiche et les calculs utilisent cette grille ;
4. si la date correspond à un jour où le parc est fermé, le formulaire reste affiché et un avertissement paramétrable demande de contacter le parc en indiquant la date et l'horaire souhaités ;
5. si aucune grille de tarifs groupes n'est publiée pour l'année, le formulaire complet reste masqué et un message d'indisponibilité paramétrable est affiché ; aucun tarif d'une autre année n'est utilisé en secours.

## Administration

Nouveau sous-menu `Accès au devis` :
- activation/désactivation de l'étape préalable par date ;
- activation/désactivation du message pour date fermée ;
- texte et contact du message date fermée modifiables ;
- activation/désactivation du message tarifs indisponibles ;
- texte et contact du message tarifs indisponibles modifiables.

Le message de fermeture est informatif : il ne bloque jamais la génération du devis si les tarifs de l'année sont disponibles.

## Technique

- Nouveau `includes/class-parcs-ht-quote-gate.php`.
- Nouveau `assets/quote-gate.js`.
- Vérification de la date via AJAX WordPress avec nonce.
- Réutilisation de `Parcs_HT_Schedule::resolve_day()` : aucun second calendrier d'ouverture n'est créé.
- La disponibilité tarifaire est lue dans la grille annuelle du module `Tarifs devis groupes`.
- Les contrôles serveur de la 1.9.10 restent la sécurité canonique des montants envoyés par CF7.

## Étape externe après installation

Le CF7 doit être réordonné afin que `Date de visite` soit le premier champ visible dans le conteneur `.flex-xbetween`. Les champs cachés techniques restent présents. Le PDF ne doit pas être redessiné : seules les valeurs dynamiques déjà prévues sont utilisées.

Avant toute modification du CF7, du script du thème ou du PDF, archiver dans GitHub l'état live exact comme base stable de retour arrière.
