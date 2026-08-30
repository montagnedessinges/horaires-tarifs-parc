# Décision — refonte de l’administration avec sous-onglets

Date : 30/08/2026

## Objectif
Réduire le nombre d’onglets principaux de l’extension et regrouper les fonctions par logique métier. La prochaine évolution doit être une vraie refonte de navigation, avec des onglets principaux stables et des sous-onglets internes.

## Regroupement validé

### 1. Horaires & calendrier
Doit devenir un onglet principal unique regroupant les fonctions liées au calendrier et aux horaires.
Sous-onglets prévus :
- Horaires & calendrier
- Périodes / événements
- Exceptions
- Accès limité

Ces éléments font tous partie de la logique de calendrier / disponibilité et ne doivent plus occuper chacun un onglet principal séparé.

### 2. Tarifs
L’onglet principal « Tarifs » reste indépendant. Il concerne les tarifs affichés publiquement et ne doit pas être fusionné avec les devis groupes.

### 3. Devis groupe
L’onglet principal « Devis groupe » reste indépendant et doit regrouper tout le moteur de devis dans des sous-onglets.
Sous-onglets prévus :
- Tarifs du devis
- Accès au devis / contrôle de la date
- Formulaires / langues
- Messages et réglages liés au devis

Les tarifs utilisés pour le devis restent des tarifs propres au devis, distincts des tarifs publics de l’onglet « Tarifs ». Ils comprennent notamment Enfant, Adulte, Handicap, Accompagnateur et la règle de gratuité.

### 4. Pop-up
À réévaluer pendant la refonte : conserver en onglet principal seulement si cela reste justifié par son usage transversal. Sinon, intégrer dans une rubrique logique adaptée sans dupliquer les réglages.

### 5. Aperçu, Mise à jour, Shortcodes
À conserver comme fonctions distinctes pour le moment, mais leur place exacte dans la nouvelle navigation doit être revue pendant la refonte afin d’éviter une administration surchargée.

## Onglet manquant
Un élément de navigation manque encore dans la structure finale. Il ne faut pas inventer son intitulé ni sa fonction : le créer seulement après confirmation explicite de l’utilisateur sur ce qu’il doit contenir.

## Contraintes
- Ne pas changer la logique métier existante uniquement pour réaliser la refonte visuelle.
- Conserver la compatibilité des réglages/options existants et prévoir une migration sans perte de données.
- Ne pas renommer les clés techniques en fonction des libellés visibles ; les titres d’onglets doivent pouvoir évoluer sans casser le fonctionnement.
- Cette refonte nécessite une nouvelle version fonctionnelle de l’extension.
- Avant développement, auditer la structure actuelle des pages `admin_menu`, sous-menus et onglets internes afin de préparer une migration propre.
