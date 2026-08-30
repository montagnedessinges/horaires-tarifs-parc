# Décision finale — devis groupes piloté par la date et tarifs annuels — 30/08/2026

## Principe confirmé
- Le visiteur ne choisit jamais manuellement « tarifs 2026 », « tarifs 2027 » ou une année tarifaire.
- La date de visite saisie dans Contact Form 7 détermine automatiquement l’année tarifaire.
- Il existe une seule grille de tarifs groupes par année : toute la saison 2026 utilise la grille 2026, toute la saison 2027 utilise la grille 2027, etc.
- L’idée de tarifs groupes spéciaux ou de plusieurs grilles selon des périodes dans une même année est abandonnée et ne doit pas être développée.
- Si la grille de l’année choisie est disponible, le calcul utilise cette grille.
- Si elle n’est pas disponible, aucun devis chiffré ne doit être généré et aucun tarif d’une autre année ne doit être utilisé en secours.

## Parcours Contact Form 7
Le parcours souhaité conserve l’affichage conditionnel existant :
1. date de visite ;
2. vérification automatique de la disponibilité des tarifs de l’année ;
3. choix du type de groupe : `Groupe` ou `Groupe en situation de handicap` ;
4. affichage des champs correspondant au type de groupe ;
5. calcul ;
6. envoi et génération du PDF.

Le choix du type de groupe reste distinct de la sélection de l’année : l’année n’est jamais demandée au visiteur.

## Traçabilité PDF obligatoire
Le PDF stable conserve son titre dynamique :
- date 2026 → `DEVIS 2026` ;
- date 2027 → `DEVIS 2027` ;
- et ainsi de suite.

Le champ `[devisannee]` doit être alimenté par la même grille annuelle réellement utilisée pour les calculs. Ce repère permet à l’équipe de savoir immédiatement sur quelle année tarifaire le devis a été établi lorsqu’un client présente uniquement le PDF.

Il n’est pas nécessaire d’ajouter un système de libellés de périodes ou de tarifs spéciaux.

## Source de vérité et sécurité
L’extension Horaires & Tarifs Parc est la source de vérité des tarifs groupes annuels. Le JavaScript assure l’expérience instantanée et les calculs visibles, mais l’envoi Contact Form 7 doit revérifier côté serveur l’année, la disponibilité de la grille et les montants. Les valeurs destinées au mail et au PDF sont recalculées côté serveur afin de ne pas faire confiance à des champs modifiables dans le navigateur.

## Conservation du PDF
La mise en page du PDF historique validée reste la base stable. Ne pas la refondre. Le titre reste `DEVIS [devisannee]` et les prix unitaires restent alimentés par les champs dynamiques déjà prévus.

## Version
Cette décision est mise en œuvre dans la version 1.9.10 de l’extension.