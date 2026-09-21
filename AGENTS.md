# Guide IA / reprise du projet

Ce fichier est destiné à toute IA, tout agent de code ou tout développeur qui reprend le dépôt **Gestion du parc / Horaires & Tarifs Parc**.

Il complète les issues, audits, changelogs et documents de version. Son objectif principal est d'éviter les faux diagnostics, les couches techniques supplémentaires inutiles et les corrections qui recréent une fonction déjà existante.

## 1. Règle fondamentale : diagnostiquer avant de développer

Quand l'utilisateur signale « ça ne marche pas », « ça ne s'enregistre pas », « ça ne s'affiche pas », « le bouton manque », etc., **ne pas conclure immédiatement qu'il faut modifier le code**.

Toujours classer le problème dans l'une de ces catégories :

1. **Bug confirmé** : le code ne respecte pas le comportement attendu.
2. **Fonction déjà disponible mais peu visible / mal connue** : expliquer où elle se trouve et comment l'utiliser.
3. **Réglage ou état non activé** : la fonctionnalité existe mais une option, une année, une visibilité, une date ou un module empêche son fonctionnement.
4. **Mauvaise utilisation / mauvais contexte** : mauvaise année sélectionnée, mauvais écran, mauvaise action, données incomplètes, etc.
5. **Amélioration UX réelle** : le fonctionnement est correct mais l'interface peut être rendue plus claire.
6. **Nouvelle fonctionnalité réelle** : aucune fonction existante ne couvre le besoin.

Ne développer qu'après ce classement.

## 2. Avant toute proposition de code

Vérifier dans cet ordre :

- le code actuel sur `main` ;
- la dernière release publiée ;
- les issues ouvertes et la roadmap ;
- les audits et documents présents dans le dépôt ;
- les versions antérieures pertinentes si le comportement fonctionnait avant ;
- les réglages qui conditionnent réellement le comportement observé.

Pour un bug apparu après plusieurs mises à jour, comparer plusieurs jalons et pas uniquement la dernière version.

Exemple : pour une régression de sauvegarde apparue en 1.17.x, comparer aussi les versions 1.16.x qui fonctionnaient correctement afin d'identifier le changement architectural réel.

## 3. Questions / contrôles à faire avant de conclure à un bug

Selon le module concerné, vérifier ou demander uniquement ce qui est utile :

- Quelle **année** est administrée ?
- Le module est-il réellement **activé** ?
- Existe-t-il une activation/désactivation automatique par date ?
- La donnée est-elle enregistrée mais simplement masquée publiquement ?
- Le bouton ou l'action existe-t-il déjà dans un autre écran ?
- Le problème se produit-il au premier enregistrement ou uniquement après modification ?
- Le problème touche-t-il un champ, un bloc complet ou toute l'année ?
- L'enregistrement est-il réellement absent en base, ou l'écran recharge-t-il une autre année ?
- Une ancienne URL ou un routeur redirige-t-il vers un autre écran ?
- Un cache peut-il masquer un état pourtant correctement enregistré ?

Exemple important : si l'utilisateur dit « ça ne s'affiche pas », vérifier d'abord si le module doit être activé avant de proposer un correctif d'affichage.

Exemple important : si l'utilisateur dit « il n'y a pas de bouton pour supprimer une année », vérifier d'abord l'interface existante. Dans la 1.17.10, la suppression existe déjà dans **Administration générale → Saisons → Supprimer**. Le problème peut donc être de découvrabilité UX, pas d'absence fonctionnelle.

## 4. Expliquer ce qui existe déjà

L'utilisateur développe et apprend en même temps. Quand une fonctionnalité existe déjà :

- le dire clairement ;
- indiquer où elle se trouve ;
- expliquer ce qu'elle fait réellement ;
- signaler si son placement ou son libellé est trompeur ;
- ne pas proposer automatiquement de la recréer ailleurs.

Une réponse utile peut être :

> « Cette fonction existe déjà. Elle se trouve ici. Le comportement actuel est correct, mais l'interface la rend difficile à trouver. On peut soit ne rien changer, soit améliorer sa découvrabilité. »

## 5. Ne pas empiler de nouvelles couches

Avant d'ajouter une nouvelle classe, un nouveau hook, un nouveau routeur, un nouveau garde, un nouveau stockage ou un nouveau renderer :

1. rechercher si une couche canonique existe déjà ;
2. vérifier pourquoi elle ne couvre pas le besoin ;
3. préférer la correction ou l'extension de la couche canonique ;
4. documenter explicitement pourquoi une nouvelle couche est indispensable si elle l'est réellement.

Éviter les architectures du type :

`nouvel écran → nouveau wrapper → ancien handler → ancien écran → routeur → nouvel écran`

quand un chemin direct peut être utilisé.

Pour les sauvegardes, préférer un propriétaire clair des données et une seule chaîne d'écriture.

## 6. Sauvegardes : principes de sécurité

Pour toute évolution de sauvegarde :

- ne jamais interpréter automatiquement un champ absent comme « supprimer toute la rubrique » ;
- toute suppression importante doit être explicite ;
- recharger l'état actuel avant modification ;
- modifier uniquement le sous-ensemble appartenant à l'écran concerné ;
- préserver les autres années et les autres modules ;
- créer une sauvegarde/révision avant une opération destructive ;
- valider avant écriture ;
- relire après `update_option()` ou autre persistance ;
- revenir sur le même écran et la même année après sauvegarde ;
- éviter les doubles redirections ;
- laisser chaque handler valider son propre nonce, surtout lorsque le nonce dépend de l'année ;
- ne pas ajouter une réécriture JavaScript globale des formulaires sans nécessité démontrée.

## 7. Années : distinguer administration et visibilité publique

Ne pas confondre :

- **Année administrée** : année que l'utilisateur est en train d'éditer dans le back-office ;
- **Visibilité publique** : modules réellement affichés sur le site ;
- année civile actuelle ;
- anciens champs historiques comme `published` ou `active_season_year`.

Si l'utilisateur choisit 2027, la navigation entre écrans annuels doit conserver 2027 sauf action volontaire de sa part.

Le statut historique « brouillon/publié » ne doit pas être présenté comme vérité globale si la visibilité est désormais pilotée module par module.

## 8. UX et actions destructives

Ne pas rendre une action destructive plus visible sans réfléchir au risque utilisateur.

Exemple : « Supprimer une année » doit être accessible et compréhensible, mais ne doit pas devenir un bouton principal placé à côté du sélecteur utilisé quotidiennement si cela augmente le risque de suppression accidentelle.

Une amélioration UX peut être un lien neutre « Gérer les années » vers la zone dédiée plutôt qu'un bouton rouge permanent.

Toute suppression importante doit avoir :

- confirmation explicite ;
- sauvegarde de sécurité ;
- traitement cohérent de tous les stockages liés ;
- retour clair après opération.

## 9. Faits, hypothèses et reproduction

Toujours distinguer :

- **vérifié dans le code** ;
- **reproduit par test** ;
- **reproduit dans un vrai WordPress/navigateur** ;
- **hypothèse probable** ;
- **non vérifié**.

Ne jamais présenter une hypothèse comme une cause confirmée.

Quand les tests automatisés sont verts mais que l'utilisateur constate un problème réel, considérer que les tests peuvent ne pas reproduire le cycle complet navigateur → WordPress → base → redirection → rechargement.

## 10. Tests attendus pour les modifications sensibles

Pour une sauvegarde annuelle, tester au minimum :

- première sauvegarde ;
- seconde sauvegarde après modification d'un seul texte ;
- activation puis désactivation ;
- ajout puis suppression explicite ;
- 2026 et 2027 en parallèle ;
- aucune modification de l'année non ciblée ;
- rechargement des données après sauvegarde ;
- URL finale correcte ;
- même année conservée après navigation ;
- aucun effacement d'un bloc non modifié.

Pour les modules importants, inclure autant que possible : Horaires, Périodes, Événements, Exceptions, Accès limité, Tarifs visiteurs, Tarifs groupes, Devis, Guides, Pop-up, Calendrier de l'Avent et imports CSV.

## 11. Communication avec l'utilisateur

Le but n'est pas seulement de coder : il faut aussi aider l'utilisateur à comprendre son extension.

Quand il signale un problème :

- vérifier avant de conclure ;
- expliquer simplement ce qui se passe ;
- dire si le comportement est déjà disponible ou mal paramétré ;
- montrer où se trouve le réglage si nécessaire ;
- proposer du développement uniquement quand il apporte une vraie valeur ;
- ne pas multiplier les questions si le dépôt permet déjà de répondre ;
- si une information d'usage est réellement nécessaire pour trancher, poser une question ciblée plutôt qu'une question générale.

## 12. Versions et releases

Pour chaque évolution de code, indiquer explicitement si une nouvelle version/release de l'extension est nécessaire.

Ne jamais annoncer qu'une version est fusionnée, publiée ou validée tant que GitHub et les workflows ne le confirment pas réellement.

Une modification purement documentaire de ce fichier ne nécessite pas, à elle seule, une nouvelle release du plugin.

## 13. Référence actuelle

Au moment de la création de ce guide :

- version publiée : **1.17.10** ;
- chantier discuté : **1.17.11** ;
- issue de cadrage : **#69 — refonte des sauvegardes et du contexte d'année admin**.

Avant de travailler, vérifier si une version ou une issue plus récente a remplacé ces références.
