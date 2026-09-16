# Addendum — Mise à jour 2 : refonte visuelle des tarifs

Date : 16/09/2026

Dépôt : `montagnedessinges/horaires-tarifs-parc`

## Statut de cette note

Cette note contient les **dernières décisions graphiques validées** pour la mise à jour 2 et remplace les propositions visuelles antérieures lorsqu’elles sont contradictoires.

La séparation reste stricte :

- **MAJ 1 = technique / fonctionnelle** ;
- **MAJ 2 = visuelle / UX**.

---

# 1. Périmètre graphique final

La mise à jour 2 doit se concentrer principalement sur :

- les **tableaux de tarifs visiteurs** ;
- les **tableaux de tarifs groupes** ;
- les onglets liés aux tarifs ;
- les sélecteurs d’année associés ;
- les moyens de paiement ;
- les petits éléments d’information directement liés aux tarifs.

## Ce qui doit rester comme actuellement

Le calendrier public et la structure générale de la page Horaires & Tarifs fonctionnent déjà correctement et **ne doivent pas faire l’objet d’une refonte graphique globale** dans la MAJ 2.

Le calendrier Groupes doit techniquement réutiliser le même composant que le calendrier principal conformément à la MAJ 1, mais la MAJ 2 ne doit pas inventer un nouveau design de calendrier si ce n’est pas nécessaire.

Ne pas transformer toute la page en grosses cartes ou en nouveau dashboard.

L’objectif est d’améliorer fortement les **tarifs** tout en conservant ce qui fonctionne déjà.

---

# 2. Direction graphique générale

Le rendu doit s’inspirer de l’esprit graphique utilisé sur les **guides / ressources pédagogiques** du site :

- interface propre ;
- blocs simples ;
- arrondis cohérents ;
- informations immédiatement compréhensibles ;
- espaces maîtrisés ;
- identité visuelle du site conservée ;
- pas de surcharge décorative ;
- priorité à la lisibilité et à la compacité.

Tous les tableaux de tarifs doivent sembler appartenir au **même système graphique** : particuliers, réduits et groupes.

Le design doit être nettement plus compact que l’affichage actuel, particulièrement sur mobile.

---

# 3. Onglets : toujours sur une ligne

Les éléments de navigation courts doivent rester sur **une seule ligne** autant que possible.

Sont concernés notamment :

- `Individuels | Tarifs réduits | Groupes` ;
- `Tarifs groupes | Horaires d’ouverture` ;
- les sélecteurs d’année `2026 | 2027 | ...`.

Règles :

- boutons beaucoup plus compacts qu’actuellement ;
- état actif clairement identifiable ;
- ne pas empiler les onglets verticalement sur mobile ;
- sur très petit écran, autoriser si nécessaire un **défilement horizontal local** ;
- ne jamais provoquer de scroll horizontal de toute la page.

Dans le shortcode Groupes, l’ordre fonctionnel `Tarifs groupes` en premier puis `Horaires d’ouverture` est défini dans la MAJ 1. La MAJ 2 doit simplement le présenter proprement.

---

# 4. Tableau des tarifs visiteurs

## Structure

Privilégier un rendu de **tableau compact** ou de lignes compactes, et non une succession de grosses cartes.

Pour l’onglet Individuels, conserver clairement les notions :

- catégorie ;
- Sur place ;
- En ligne.

Les titres `Sur place` et `En ligne` ne doivent pas être répétés inutilement à chaque ligne si un en-tête compact suffit.

## Sur place

- rendu neutre et lisible ;
- fond transparent ou très léger ;
- prix facilement identifiable.

## En ligne

- rendu distinct du Sur place ;
- couleur légèrement mise en avant mais cohérente avec le site ;
- toute la cellule / zone En ligne est cliquable ;
- utiliser un vrai lien accessible ;
- possibilité d’une petite flèche ou icône discrète ;
- ne pas afficher automatiquement `Meilleur tarif`, car le tarif peut être identique au prix sur place.

## Cellules masquées

Lorsqu’un canal n’existe pas pour un tarif :

- ne pas afficher `—` ;
- ne pas afficher une cellule vide bordée ;
- ne pas conserver un faux espace ;
- rééquilibrer naturellement la ligne.

### Exemple : enfant de moins de 5 ans

Afficher seulement l’information utile, par exemple :

`Enfant · moins de 5 ans | Gratuit`

Aucun faux bloc En ligne.

---

# 5. Tarifs réduits

Les tarifs réduits doivent reprendre le **même langage graphique** que les tarifs individuels, mais sans créer de colonne En ligne si aucun tarif en ligne n’existe.

Afficher de façon compacte le message configurable :

`Tarifs uniquement disponibles sur place.`

Ce texte reste géré techniquement par la MAJ 1 lorsqu’un stockage / réglage est nécessaire.

---

# 6. Tarifs groupes

Le tableau des tarifs groupes doit être redessiné dans la même famille visuelle que les tarifs visiteurs :

- lignes compactes ;
- catégorie à gauche ;
- prix bien visible ;
- sous-informations discrètes ;
- arrondis, typographie et densité cohérents avec le tableau visiteurs ;
- aucun grand espace inutile ;
- très bonne utilisation de la largeur sur mobile.

Ne pas ajouter artificiellement les colonnes `Sur place / En ligne` si elles ne correspondent pas aux données groupes.

Lorsque plusieurs années sont disponibles, chaque année conserve sa propre grille tarifaire et le sélecteur d’année reste compact sur une seule ligne.

---

# 7. Moyens de paiement — deux listes différentes

Les moyens de paiement **particuliers** et **groupes** ne doivent jamais être confondus.

Ils utilisent le **même style graphique**, mais leur contenu est indépendant.

## Particuliers

La liste actuellement présentée comprend notamment :

- Carte bancaire ;
- Espèces ;
- Chèques-Vacances papier ;
- Chèques-Vacances Connect.

Le nouveau rendu doit conserver les pictogrammes et libellés, mais rendre les badges plus compacts.

## Groupes

La partie Groupes utilise sa propre liste de moyens de paiement, définie par ses données / réglages, avec notamment les moyens spécifiques aux groupes lorsqu’ils sont activés (voucher, bon de commande, Chorus Pro, etc.).

Il ne faut pas réutiliser automatiquement la liste Particuliers dans la partie Groupes.

## Règle graphique commune

Pour chaque contexte :

- afficher les moyens de paiement sur **une seule ligne** ;
- réduire la taille des pills / badges, des icônes et des espacements ;
- **ne pas passer sur une deuxième ligne** ;
- sur mobile ou écran étroit, utiliser un **défilement horizontal local** de la barre des moyens de paiement ;
- le scroll ne concerne que cette barre, jamais la page complète ;
- conserver des libellés suffisamment explicites : ne pas remplacer toutes les informations par des icônes seules.

Exemple de logique visuelle particuliers :

`Carte bancaire | Espèces | Chèques-Vacances papier | Chèques-Vacances Connect`

---

# 8. Mobile : priorité à la hauteur minimale

Le but principal est de réduire fortement la hauteur occupée par les tarifs.

À environ 320–400 px de largeur :

- onglets sur une seule ligne ;
- sélecteur d’année sur une seule ligne ;
- moyens de paiement sur une seule ligne avec scroll local si nécessaire ;
- lignes tarifaires basses et lisibles ;
- aucune grosse carte par tarif ;
- aucune colonne vide ;
- aucun débordement horizontal global ;
- informations principales visibles rapidement sans devoir faire défiler plusieurs écrans avant d’atteindre les prix.

La compacité ne doit toutefois pas rendre les zones tactiles ou les textes illisibles.

---

# 9. Ce qui appartient à la MAJ 1 et non à la MAJ 2

La MAJ 2 ne doit pas créer la logique métier nécessaire à ces rendus.

Restent dans la MAJ 1 :

- visibilité Sur place / En ligne par tarif ;
- lien d’achat spécifique par tarif ;
- liens de billetterie ;
- stockage des messages configurables ;
- visibilité par année ;
- année active ;
- planification automatique ;
- shortcodes Groupes ;
- ordre fonctionnel Tarifs groupes / Horaires ;
- affichage de plusieurs années ;
- calendrier Groupes réutilisant le calendrier commun ;
- listes / données de moyens de paiement si une évolution de stockage est nécessaire.

La MAJ 2 utilise ces données et ne fait que les présenter.

---

# 10. Critères d’acceptation visuels

- [ ] Le calendrier principal n’a pas été inutilement redessiné.
- [ ] La refonte se concentre sur les tarifs et leurs éléments associés.
- [ ] Le design reprend l’esprit propre et compact des guides / ressources pédagogiques.
- [ ] Les onglets Individuels / Tarifs réduits / Groupes restent sur une ligne.
- [ ] Les onglets Tarifs groupes / Horaires restent sur une ligne.
- [ ] Les sélecteurs d’année restent sur une ligne.
- [ ] Le tableau Individuels est nettement plus compact.
- [ ] Sur place et En ligne restent faciles à distinguer.
- [ ] La cellule En ligne est entièrement cliquable lorsque le tarif est disponible en ligne.
- [ ] Une cellule masquée ne laisse ni tiret ni espace artificiel.
- [ ] Les moins de 5 ans peuvent afficher uniquement `Gratuit` sans fausse cellule En ligne.
- [ ] Les tarifs réduits fonctionnent proprement sans colonne En ligne.
- [ ] Le tableau Groupes reprend le même langage graphique que le tableau visiteurs.
- [ ] Les moyens de paiement Particuliers et Groupes restent deux listes indépendantes.
- [ ] Les moyens de paiement Particuliers tiennent visuellement sur une seule ligne.
- [ ] Les moyens de paiement Groupes tiennent visuellement sur une seule ligne.
- [ ] Sur écran étroit, les barres de paiement utilisent un scroll horizontal local plutôt qu’un retour à la ligne.
- [ ] Aucun scroll horizontal global n’est introduit.
- [ ] Le rendu reste lisible et utilisable à environ 320 px de large.

Cette note est la référence graphique prioritaire pour la **MAJ 2** lorsqu’une ancienne proposition entre en contradiction avec ces choix.