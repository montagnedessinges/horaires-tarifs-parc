# Mise à jour 1.13.7 — contenu Tarifs groupes configurable

Cette version généralise le contenu public du shortcode `[parc_tarifs_groupes]` afin que la même extension puisse être utilisée avec des conditions différentes à la Montagne des Singes, à la Forêt des Singes ou sur un autre site.

## Réglages par installation et par saison

Dans **Groupes → Tarifs**, il est désormais possible de :

- activer ou masquer la section des moyens de paiement ;
- modifier son titre en français, anglais et allemand ;
- ajouter, supprimer et réordonner les moyens de paiement ;
- choisir une icône pour chaque moyen de paiement ;
- modifier le libellé de chaque moyen en FR / EN / DE ;
- activer ou masquer les blocs d’informations placés sous les tarifs ;
- ajouter, supprimer et réordonner librement ces blocs ;
- modifier leur titre et leur texte en FR / EN / DE ;
- personnaliser le titre, l’introduction et le bouton de devis du bloc Tarifs groupes.

## Indépendance des sites

Le rendu public ne contient plus de règle conditionnelle propre à `site_type=mds`. Chaque installation affiche uniquement les réglages enregistrés sur ce site et pour la saison concernée.

Le contenu MDS introduit en 1.13.6 est conservé automatiquement lors de la migration vers 1.13.7, mais devient ensuite entièrement modifiable dans l’administration. La Forêt des Singes et les autres installations ne reçoivent pas automatiquement les conditions MDS.

La migration est sans effet de bord sur les lectures publiques : elle peut préparer l’affichage en mémoire, mais l’écriture de la nouvelle structure est effectuée dans le contexte d’administration ou lors d’un enregistrement explicite.

## Sécurité de l’enregistrement

Les listes configurables de moyens de paiement et de blocs d’information sont assainies avant leur décodage puis chaque champ est à nouveau validé et assaini avant l’enregistrement. Cette étape permet de respecter les contrôles WordPress sans modifier la logique de personnalisation.

## Source unique des tarifs

Les prix ne sont pas stockés dans ces nouveaux réglages d’affichage. Le shortcode continue de lire exclusivement la grille canonique `tariffs.groups` de la saison publique, comme le tableau tarifaire principal et le moteur de devis.

La migration ne réécrit donc aucun tarif groupe et ne crée aucune deuxième grille de prix.
