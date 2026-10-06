# Gestion du parc 1.20.2 — correction de la surface publique multi-années

## Objectif

La 1.20.2 est une mise à jour corrective ciblée. Elle ne cherche pas à ajouter une nouvelle couche « IA » ni à modifier visuellement le site.

Le problème confirmé était le suivant : certaines informations réellement publiques, notamment les horaires groupes d’une année future, étaient visibles après exécution JavaScript mais mal représentées dans le HTML initial. À l’inverse, un texte d’indisponibilité pouvait rester présent dans le document alors que les horaires existaient réellement.

La règle de cette version est :

> une donnée publique doit être représentée correctement côté serveur ; une donnée non publique ne doit pas devenir publique à cause du correctif.

## Corrections principales

### 1. Un seul portail Groupes canonique

Deux implémentations du shortcode groupes coexistaient : `Parcs_HT_Group_Portal` et une seconde logique dans `Parcs_HT_Shortcode_Composer`.

La 1.20.2 conserve `Parcs_HT_Group_Portal` comme seul renderer du shortcode public. L’ancien assembleur garde uniquement une méthode de compatibilité PHP qui délègue au portail canonique.

Aucune donnée n’est migrée et aucun stockage n’est ajouté.

### 2. Horaires groupes multi-années dans le HTML serveur

Le calendrier sémantique 1.20.1 peut désormais recevoir une liste explicite d’années déjà autorisées par le contexte public.

Pour le portail groupes, cette liste provient exclusivement de `Parcs_HT_Public_Visibility::group_schedule_years()`.

Conséquence : une année comme 2027 peut être lisible dans le HTML de la page Groupes si ses horaires groupes sont publiés, tout en restant absente du calendrier visiteurs si ce dernier n’est pas encore publié.

### 3. Pas de faux message « horaires non disponibles »

Le portail ne préremplit plus systématiquement le message d’indisponibilité pour ensuite le cacher par JavaScript.

Le texte est émis dans le contenu textuel initial uniquement lorsque l’année active ne possède réellement aucun horaire groupes public.

### 4. Navigation annuelle avec repli serveur

Les années du portail groupes possèdent maintenant une vraie URL `?htp_group_year=AAAA`.

Avec JavaScript, l’expérience reste instantanée et visuellement identique. Sans JavaScript, l’URL permet au serveur de rendre directement l’année choisie.

### 5. Tarifs groupes rendus pour leur année explicite

Chaque année de tarifs groupes réellement publique est rendue côté serveur en appelant le renderer tarifaire avec l’année explicitement demandée.

La décision des années publiques continue de provenir de `Parcs_HT_Public_Visibility`.

### 6. Projection JavaScript groupes réduite

Le payload spécifique au calendrier groupes n’envoie plus la saison brute.

Il reconstruit une projection limitée aux champs nécessaires au calendrier public. Les libellés internes, dont `internal_label`, ne sont jamais sérialisés par le portail groupes.

## Ce qui ne change pas

- aucune nouvelle base de données ;
- aucune double saisie ;
- aucun horaire 2027 visiteurs n’est publié automatiquement ;
- aucune détection de robot ou d’User-Agent ;
- aucun contenu caché uniquement pour Google ou les IA ;
- aucun changement visuel volontaire du calendrier, des tarifs ou du portail groupes ;
- la FAQ, les guides, les pop-up et le Calendrier de l’Avent ne sont pas refondus par cette release.

## Tests ajoutés

La 1.20.2 ajoute des contrôles statiques et d’exécution qui vérifient notamment :

- le portail groupes n’a plus deux renderers concurrents ;
- le calendrier visiteurs peut exposer 2026 sans exposer 2027 ;
- le même moteur sémantique peut exposer 2027 dans le seul contexte groupes lorsque cette année y est publique ;
- les horaires 2027 groupes existent dans le HTML initial ;
- un libellé interne d’une période n’est pas exposé par le payload groupes ;
- le faux message d’indisponibilité n’apparaît pas dans le texte extrait lorsque les horaires existent ;
- le message d’indisponibilité apparaît bien lorsqu’il est réellement applicable ;
- le renderer public des tarifs visiteurs et groupes continue d’utiliser la politique annuelle centrale.

## Vérifications après installation sur La Montagne des Singes

1. Ouvrir la page publique Groupes.
2. Vérifier visuellement que le rendu est inchangé.
3. Sélectionner 2026 puis 2027 et vérifier les tarifs et horaires de chaque année.
4. Afficher la source HTML de la page et rechercher un horaire connu de 2027 : il doit être présent si les horaires groupes 2027 sont publiés.
5. Vérifier que le calendrier visiteurs normal ne présente toujours pas 2027 tant que cette saison visiteurs n’est pas publiée.
6. Purger le cache de page/CDN après installation si le site en utilise un.

## Hors périmètre restant de l’audit #94

L’audit global de toute la surface publique reste plus large que ce correctif. Les autres pistes (réduction globale de `ParcsHTPData`, anciens fallbacks internes dans le moteur historique, exports/endpoints et simplifications supplémentaires) doivent rester auditées séparément avant suppression ou refonte.

Le principe reste de ne pas ajouter de code tant qu’un problème réel n’est pas démontré.
