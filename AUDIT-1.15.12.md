# Correctif 1.15.12 — état du 15 septembre 2026

Base : commit 8cf3b6eff1f9f55fc212bbbc04b4b5892422f05d, tag 1.15.11. Tous les fichiers récupérés ont été contrôlés par leur empreinte Git.

## Défauts constatés

- normalize_tariffs modifiait par référence une expression temporaire au lieu des lignes du tableau. Les nouvelles colonnes ne trouvaient plus les prix dans les anciennes cellules.
- Ordre forcé En ligne / Sur place et colonnes vides conservées, alors que 2026 ne propose pas de tarifs en ligne.
- Les options groupes étaient réécrites en lecture selon le calendrier, puis réécrites à nouveau par une seconde couche ; publication publique et autorisation devis restaient confondues.
- Les commandes étaient injectées par JavaScript dans un sous-bloc et leur absence lors d’une sauvegarde partielle était interprétée comme NON.
- La navigation visiteurs demandait une page HTML complète et rechargeait toute la page en cas d’échec. Le motif d’insertion des onglets d’année comportait aussi une frontière de mot invalide après le guillemet.

## Correction et contrôles

- Conversion en lecture, cellules existantes conservées, sauvegarde sans conversion des autres années.
- Sur place puis En ligne ; colonne En ligne masquée lorsqu’aucune ligne activée ne possède de prix.
- Quatre commandes OUI/NON en haut du formulaire annuel, présentes dans le HTML serveur et conservées lors des sauvegardes partielles.
- Test PHP des 16 combinaisons indépendantes, avec le moteur de devis réel et la grille canonique 2027.
- Navigation visiteurs sur panneaux déjà rendus : 40 bascules DOM, clavier et plusieurs instances, zéro requête.
- Rendu navigateur vérifié sur données synthétiques à 1100 px et 375 px ; pas de débordement horizontal observé.
- La CI contrôle PHP 7.4, 8.1, 8.2 et 8.3, JavaScript, sécurité WordPress et préparation du paquet.

## Limites et suite demandée pour demain

Aucune base WordPress de production n’a été modifiée. Les données de production et la soumission réelle CF7/PDF ne sont pas encore vérifiées. Il faut contrôler les quatre états dans l’administration après installation et vérifier un devis 2027 avec les montants réels. Si l’ancien état visiteurs 2026 a été enregistré sur NON, le réactiver explicitement : aucune intention de visibilité enregistrée n’est écrasée automatiquement.

L’audit complet performance, l’intégration visuelle aux différents thèmes, les offres temporaires sur plusieurs années, la navigation annuelle de l’administration et la consolidation des anciens réglages groupes restent à terminer. Pas de fusion ni de publication automatique de cette PR.
