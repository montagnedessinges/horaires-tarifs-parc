# Audit — PDF devis CF7 / ZealousWeb — 30/08/2026

## Contexte
Le PDF de devis de La Montagne des Singes est généré avec le plugin WordPress **Generate PDF using Contact Form 7** de **ZealousWeb**.

Documentation fournie par l’utilisateur :
`https://store.zealousweb.com/documentation/wordpress-plugins/generate-pdf-using-contact-form-7`

Le rendu observé le 30/08/2026 montre :
- un devis qui déborde sur une deuxième page alors que la deuxième page contient presque uniquement le pied de page / mentions légales ;
- plusieurs espacements verticaux très importants ;
- certains titres / blocs visuellement décalés ;
- le besoin explicite de conserver un PDF A4 portrait lisible sur **une seule page**.

## Moteur PDF confirmé
Le plugin ZealousWeb s’appuie sur **mPDF** pour générer les PDF.

Conséquence : le rendu n’est pas celui d’un navigateur classique. Toute correction HTML/CSS du modèle doit être pensée pour les règles et limitations de mPDF. Éviter de supposer qu’un CSS moderne de navigateur sera interprété de façon identique.

## Réglages visibles dans WordPress
Sur la capture fournie par l’utilisateur, les réglages PDF visibles comprennent notamment :
- largeur maximale du logo : `100` ;
- largeur minimale du logo : `85px` ;
- marge externe de l’en-tête PDF : `10` ;
- marge externe du pied de page PDF : `10` ;
- marge externe supérieure du PDF : `45` ;
- marge externe inférieure du PDF : `10` ;
- marge externe gauche du PDF : `10` ;
- marge externe droite du PDF : `10` ;
- champs texte pour l’en-tête en haut à droite et le pied de page inférieur gauche.

Le réglage supérieur `45` est très élevé par rapport aux autres marges et consomme une part importante de la hauteur disponible. Cependant, il ne faut pas considérer ce réglage comme l’unique cause du débordement : le modèle HTML/CSS possède également des espacements internes importants.

## Diagnostic visuel du PDF actuel
Le contenu utile de la première page est proche de tenir sur une seule page, mais plusieurs facteurs se cumulent :

1. espace réservé au header géré par le plugin/mPDF ;
2. marge supérieure du document actuellement importante ;
3. espace réservé au footer ;
4. marges et paddings du HTML/CSS du modèle ;
5. hauteurs de lignes / cellules de tableaux ;
6. gros espaces avant les sections « Langue parlée », « Âge des enfants » et le tableau final de signature ;
7. bloc « Moyen de paiement prévu » relativement haut ;
8. footer / mentions légales repoussés seuls sur une deuxième page.

Le problème doit donc être traité comme un **problème de composition verticale globale**, pas uniquement comme un problème de marge WordPress.

## Objectif validé
Le devis doit rester :
- format A4 ;
- orientation portrait ;
- une seule page ;
- lisible, sans réduire excessivement la police ;
- avec logo, informations client, tableau du devis, avertissement, paiement/facturation, langue, âge des enfants, signatures et footer ;
- correctement aligné et compact, sans grands espaces inutiles.

## Piste de réglages à tester
Valeurs recommandées comme base de travail, à tester avec le modèle réel :
- marge haute : environ `15–20` ;
- marge basse : environ `5–10` ;
- marge gauche : `10` ;
- marge droite : `10`.

Ne pas modifier le format A4 ou passer en paysage uniquement pour masquer le problème.

Ces valeurs ne sont pas une correction définitive tant que le HTML/CSS exact du modèle actif n’a pas été audité.

## Stratégie de correction recommandée
Avant toute modification :
1. récupérer le HTML/CSS exact actuellement enregistré dans ZealousWeb ;
2. l’archiver dans GitHub comme **base stable / avant modification** ;
3. conserver séparément la version modifiée ;
4. adapter le CSS spécifiquement à mPDF ;
5. réduire d’abord les marges/paddings et hauteurs inutiles, plutôt que diminuer fortement toutes les tailles de police ;
6. aligner les colonnes et titres sur une grille cohérente ;
7. vérifier que le footer reste sur la première page ;
8. générer un devis réel de test avant mise en production.

## Points de vigilance mPDF
- privilégier des tableaux simples pour les mises en page complexes lorsque cela améliore la stabilité du rendu ;
- éviter de dépendre de propriétés CSS modernes dont le support mPDF est incertain ;
- surveiller les `margin`, `padding`, `line-height`, hauteurs de cellules et sauts de page ;
- ne pas utiliser de hauteur fixe inutile qui pourrait forcer un débordement ;
- vérifier le comportement du header et du footer séparément du corps du document.

## Lien avec le système de devis dynamique
Le modèle PDF reste lié au chantier du devis multi-années :
- année dynamique via `[devisannee]` ;
- prix unitaires dynamiques via `[tarifenfant]`, `[tarifadulte]`, `[tarifhandicap]`, `[tarifaccompagnateur]` ;
- totaux existants via les champs CF7 correspondants.

La base PDF historique 2026 ne doit jamais être écrasée. Toute nouvelle version optimisée une page doit être archivée séparément.

## Règle permanente
Toute nouvelle information sur ZealousWeb, mPDF, ses réglages, le modèle PDF actif ou ses limitations doit être ajoutée à GitHub afin qu’un futur chat puisse reconstruire et maintenir le système sans dépendre de la conversation d’origine.
