# Historique des versions

## 1.13.3
- Remplacement direct de l’ancienne page « Shortcodes » dans l’administration : aucune surcouche JavaScript ni réécriture après affichage.
- La page contient statiquement les 48 shortcodes utilisables : 12 modules, chacun en version Automatique, FR, EN et DE.
- Ajout explicite des shortcodes Tarifs groupes, Horaire d’accueil et Guides pédagogiques qui manquaient dans l’ancienne page.
- Suppression du composant `Parcs_HT_Feature_Hub` qui servait uniquement à remplacer l’ancienne table après son rendu.
- Le registre central reste utilisé pour l’exécution des shortcodes et les aperçus, mais la page de référence des shortcodes n’en dépend plus.
- Aucun réglage, horaire, tarif, devis, formulaire ou donnée des parcs n’est modifié.

## 1.9.22
- Correctif dédié à l’enregistrement des formulaires Contact Form 7 configurés par langue dans « Devis par langue ».
- Retour au traitement direct et champ par champ du formulaire FR / EN / DE, sans pré-transformation globale des valeurs avant validation.
- Un shortcode CF7 invalide n’est plus transformé silencieusement en champ vide avec retour au formulaire général : l’administration affiche désormais une erreur explicite afin d’éviter une fausse impression d’enregistrement.
- Après écriture, l’extension relit l’option WordPress et refuse d’afficher une confirmation si les valeurs réellement stockées ne correspondent pas aux valeurs demandées.
- Le cache LiteSpeed est purgé après modification des formulaires par langue afin que le nouveau formulaire soit visible immédiatement sur les pages publiques.
- Ajout d’un test de contrat exécuté sur PHP 7.4, 8.1, 8.2 et 8.3 pour protéger ce circuit d’enregistrement.

## 1.9.21
- Correction de la suppression définitive des éléments du module Devis groupe.
- Lorsqu’un « Message important », un lien rapide, un bloc complémentaire ou un accordéon est réellement supprimé puis que l’onglet Devis est enregistré, l’ancienne ligne n’est plus réinjectée depuis la configuration précédente.
- Une liste volontairement vidée reste vide après sauvegarde ; la désactivation d’un élément reste disponible indépendamment de sa suppression.
- Ajout d’un test de non-régression spécifique à la suppression des listes du module Devis groupe.

## 1.9.20
- Correctif urgent après la régression constatée en 1.9.19 sur le devis groupe et la sauvegarde de l’administration.
- Le moteur JavaScript des devis groupes est de nouveau chargé assez tôt pour garantir la disponibilité de sa dépendance avant le contrôle préalable de date ; le fonctionnement validé en 1.9.17/1.9.18 est restauré sans modifier les tarifs ni les formulaires CF7.
- Ajout d’une protection de sauvegarde indépendante et très légère dans l’administration : lors de « Enregistrer cet onglet », les champs des autres onglets sont exclus du POST avant toute autre initialisation JavaScript. Cela évite qu’un problème d’interface secondaire ou une limite PHP `max_input_vars` empêche l’onglet actif d’être réellement enregistré.
- La protection historique présente dans `admin.js` reste compatible ; le nouveau garde-fou est volontairement isolé afin que la sauvegarde continue de fonctionner même si une autre initialisation de l’interface d’administration échoue.
- Aucun réglage existant, horaire, date, saison, tarif, événement, exception ou formulaire n’est remplacé par la mise à jour.
- Ajout d’un test de non-régression vérifiant le chargement du moteur devis et l’exclusion des onglets inactifs lors d’une sauvegarde ciblée.

## 1.9.19
- Correction des huit constats de l’audit du 31 août 2026, conservé dans `AUDIT-2026-08-31.md`.
- Le calendrier ne déclenche plus de boucle de recalcul à la suite de ses propres modifications ; les dernières entrées des deux créneaux restent distinctes.
- Une saison brouillon ne peut plus autoriser un devis, même si sa grille était auparavant disponible. L’autorisation indépendante des devis est conservée ; une grille ancienne sans indicateur explicite suit la publication de sa saison.
- Les tarifs de devis non disponibles ne sont plus transmis au navigateur. Le script de calcul est chargé lors du rendu du devis ou d’un formulaire CF7 compatible, et non sur toutes les pages.
- Les exports publics ignorent les demandes d’aperçu brouillon et les colonnes masquées. Sans saison publiée, le rendu public n’utilise aucun ancien prix de secours. Les données brutes restent conservées pour les sauvegardes et migrations.
- Les réponses AJAX obsolètes ne peuvent plus rouvrir un formulaire ni remplacer sa date. Le sélecteur et le champ Contact Form 7 restent synchronisés, y compris lors d’un effacement.
- Les journées fermées annoncent à nouveau la prochaine ouverture en FR/EN/DE. Les informations d’exception restent affichées après rafraîchissement, dans le respect du marqueur public.
- Consolidation des rafraîchissements accueil/en-tête/Aujourd’hui dans `status-sync.js`, à partir du moteur partagé ; la couche des dernières entrées se concentre désormais sur le calendrier.
- Renforcement des vérifications de nonce/capacité, de l’échappement CSS/SVG et des redirections PDF. Les exceptions d’analyse statique restantes sont limitées à des lignes documentées (HTML interne déjà échappé, flux PDF, paramètres de présentation et lien natif de vérification WordPress).
- Les contrôles de sécurité/compatibilité PHP et les erreurs du contrôle officiel WordPress bloquent désormais la publication. Le contrôle WordPress précède le retrait des commentaires ; la construction vérifie l’identité des tokens exécutables PHP avant/après nettoyage.
- Ajout de tests comportementaux des données publiques et du DOM, également exécutés sur les sources nettoyées destinées au ZIP. Aucun remplacement des réglages ou formulaires des deux parcs ; aucune nouvelle migration de données.

## 1.9.18
- Les messages publics de l’onglet « Accès au devis » sont désormais réellement multilingues en français, anglais et allemand.
- Les avertissements « parc fermé » et « tarifs indisponibles » disposent chacun de trois contenus FR / EN / DE dans l’administration, avec un contact ou lien commun.
- Les réglages français déjà enregistrés avant la mise à jour sont conservés automatiquement comme version FR ; les textes EN et DE disposent de valeurs par défaut traduites.
- Le message affiché sur le site est sélectionné selon la langue du shortcode/page de devis, avec secours vers le français si une traduction est volontairement laissée vide.
- Les libellés d’accessibilité des boutons mois précédent / mois suivant du calendrier sont adaptés à la langue FR / EN / DE au lieu de rester uniquement en français.
- Aucun changement n’est apporté au calcul des devis, aux tarifs annuels, au préremplissage de la date Contact Form 7 ni au PDF dynamique.

## 1.9.17
- Correction du contrôle de disponibilité des tarifs du devis : l’accès au formulaire utilise désormais les réglages canoniques du module Devis groupe au lieu de lire directement l’option brute.
- Cette correction rétablit le fonctionnement validé du choix de date : une année disposant d’une grille de devis publiée ouvre le formulaire et recopie la date dans le champ `visite`.
- Aucun nouveau moteur de publication par année n’est ajouté : le fonctionnement 1.9.17 testé sur le site reste la base stable du circuit devis/date.

## 1.9.16
- Le comportement public du Devis groupe revient au principe minimal validé : tous les textes, remarques, liens, blocs et accordéons déjà configurés restent affichés comme avant.
- Le titre configuré au-dessus du formulaire reste visible avant le choix de la date afin que le visiteur comprenne immédiatement qu’il s’agit du devis en ligne.
- Seul le bloc Contact Form 7 est masqué avant validation de la date ; le contrôle de date du plugin est inséré directement entre le titre du formulaire et le CF7.
- Une date dont l’année possède une grille de tarifs groupes publiée ouvre le formulaire configuré et recopie automatiquement la date dans le champ `visite`.
- Une date fermée n’empêche pas le devis : le message de fermeture configuré est affiché et le formulaire reste accessible.
- Une année sans grille publiée laisse le formulaire masqué et affiche le message d’indisponibilité configuré.
- Le formulaire FR fourni le 30/08/2026, incluant `devisannee`, `tarifenfant`, `tarifadulte`, `tarifhandicap` et `tarifaccompagnateur`, est archivé séparément sans remplacer la base stable historique.
- Le shortcode CF7 reste modifiable dans l’administration. Le formulaire français est la référence de test actuelle ; les formulaires EN/DE ne sont pas encore considérés comme adaptés et validés avec ce nouveau circuit.

## 1.9.10
- Le devis groupe est désormais piloté par la date de visite : une date 2026 utilise uniquement la grille groupes 2026, une date 2027 uniquement la grille 2027, sans choix manuel d’année par le visiteur.
- Une seule grille de tarifs groupes est prévue par année ; les tarifs spéciaux par période ne font pas partie de ce moteur.
- Si la grille de l’année choisie n’est pas marquée disponible, aucun tarif d’une autre année n’est utilisé en secours et Contact Form 7 bloque l’envoi du devis.
- Le choix du type de groupe reste inchangé : « Groupe » ou « Groupe en situation de handicap » ; il devient exploitable après sélection d’une date correspondant à une grille disponible.
- Les montants et tarifs transmis au mail/PDF sont recalculés côté serveur à partir de la grille enregistrée dans l’extension afin de ne pas faire confiance aux valeurs modifiables dans le navigateur.
- Le champ PDF `devisannee` est alimenté automatiquement avec l’année réellement utilisée. Le modèle PDF stable peut donc conserver `DEVIS [devisannee]` et afficher automatiquement `DEVIS 2026`, `DEVIS 2027`, etc., sans changement de mise en page.
- Les prix unitaires PDF (`tarifenfant`, `tarifadulte`, `tarifhandicap`, `tarifaccompagnateur`) et les totaux sont eux aussi réécrits avec les valeurs canoniques de l’extension lors de l’envoi.

## 1.9.9
- Ajout d’une configuration indépendante des formulaires Contact Form 7 français, anglais et allemand.
- Les shortcodes de devis FR/EN/DE peuvent utiliser chacun leur formulaire CF7, avec maintien du formulaire général comme secours lorsqu’aucun formulaire spécifique n’est configuré.

## 1.9.8
- Correctif urgent du moteur de devis groupes après retrait de l’ancien calcul 2026 du `script.js` du thème.
- Le moteur de devis de l’extension est désormais actif automatiquement : il n’existe plus de dépendance à une case d’activation séparée.
- La détection du formulaire Contact Form 7 ne dépend plus d’un ID technique figé (`806`). Le moteur détecte le formulaire à partir des champs fonctionnels `visite` et `groupedevis`, ce qui reste compatible avec les nouveaux identifiants CF7 alphanumériques.
- Le formulaire français actuellement affiché utilise un identifiant CF7 alphanumérique (`6c681fb`) ; cette différence expliquait pourquoi le moteur 1.9.7 ne prenait pas la main même après retrait de l’ancien script.
- Les tarifs groupes 2026 restent : enfant 6 €, adulte 8,50 €, personne en situation de handicap 6 €, accompagnateur 6 €, avec un adulte gratuit par tranche complète de 10 enfants, limité au nombre d’adultes présents.
- Les champs techniques destinés au PDF (`devisannee`, `tarifenfant`, `tarifadulte`, `tarifhandicap`, `tarifaccompagnateur`) continuent d’être alimentés par le moteur.
- La page avancée des tarifs de devis, lorsqu’elle est accessible, n’est plus nécessaire pour activer le calcul ; elle sert uniquement à gérer les paramètres et tarifs par année.

## 1.9.7
- Ajout d’un module « Devis groupes » pour centraliser les tarifs de devis par année de visite tout en conservant, pour le moment, le formulaire Contact Form 7 français existant.
- Le formulaire français peut utiliser un seul moteur pour plusieurs années : l’année est déterminée par le champ `visite` et une année non publiée n’utilise jamais silencieusement les tarifs d’une autre année.
- Tarifs groupes 2026 initialisés : enfant 6 €, adulte 8,50 €, personne en situation de handicap 6 €, accompagnateur 6 €, avec un adulte gratuit par tranche complète de 10 enfants dans la limite du nombre d’adultes présents.
- Le moteur de l’extension reste désactivé par défaut pendant la transition afin d’éviter un double calcul tant que l’ancien calcul existe dans le `script.js` du thème.
- Préparation de la transition multi-années du PDF : le modèle 2026 actuel contient encore le titre `DEVIS 2026` et des prix unitaires 2026 en dur. Il doit être remplacé par un modèle dynamique avant activation des tarifs 2027.
- Les modèles PDF et le script du thème restent des composants externes à l’extension : leurs bases stables sont conservées dans GitHub et les versions modifiées doivent être archivées séparément avant mise en production.

## 1.9.6
- Correction du rendu public après la fermeture du parc : une ouverture le lendemain n’affiche plus une date technique au format ISO (`2026-08-30 · 10h`).
- Pour le lendemain, l’affichage devient humain et compact, par exemple `À demain !` puis `Ouverture à 10h`.
- Lorsqu’une prochaine ouverture est plus éloignée, la date est affichée dans la langue de la page sous une forme lisible, par exemple `5 septembre · 10h`, jamais sous forme ISO brute.
- La correction est faite dans le moteur d’état partagé afin que la page d’accueil et le bloc « Aujourd’hui » utilisent exactement la même logique.
- Ajout de tests de non-régression pour l’affichage après fermeture, le lendemain et les prochaines ouvertures plus éloignées.

## 1.9.5
- Nettoyage du paquet de production sans modifier les sources de maintenance conservées sur GitHub.
- Les commentaires PHP, JavaScript et CSS sont retirés de la copie construite pour WordPress ; seul l’en-tête officiel du plugin est conservé car WordPress en a besoin pour identifier l’extension.
- Tous les fichiers Markdown, audits, documents de travail, tests, outils de build, dépendances de développement et métadonnées GitHub sont exclus du ZIP de production.
- La release vérifie après nettoyage la syntaxe PHP et JavaScript afin d’empêcher qu’un retrait de commentaire altère le code exécuté.
- Un contrôle bloque la publication si une attribution de développement explicite telle que ChatGPT, OpenAI, GitHub Copilot ou « généré par IA » apparaît dans les sources livrées.
- Aucun changement fonctionnel des horaires, tarifs, saisons, pop-up ou affichages publics dans cette version.

## 1.9.4
- Correction définitive du faux message « Créneau 2 incomplet » : le créneau 2 est réellement facultatif et n’est signalé incomplet que si une seule des deux heures est renseignée.
- Quand les deux heures du créneau 2 sont présentes, la validation contrôle uniquement l’ordre ouverture/fermeture et le chevauchement éventuel avec le créneau 1.
- L’aperçu n’affiche plus deux cartes identiques pour l’accueil et « Aujourd’hui » : un seul bloc « Affichage du jour » utilise le moteur partagé.
- L’aperçu interprète désormais explicitement l’heure simulée : avant ouverture, pendant un créneau, entre deux créneaux et après fermeture, avec l’heure d’ouverture, de réouverture ou de fermeture correspondante.
- La sauvegarde et la publication des saisons sont séparées. Une simple sauvegarde ne peut plus changer le statut d’une saison.
- Pour une saison brouillon : actions « Enregistrer le brouillon » et « Publier la saison ».
- Pour une saison publiée : actions « Enregistrer les modifications » et « Remettre en brouillon ».
- « Remettre en brouillon » retire la saison du moteur public sans supprimer ses horaires, tarifs, événements, exceptions ou pop-up.
- La case de publication mélangée aux réglages généraux est masquée dans l’administration ; le changement de statut passe par une action explicite avec confirmation.
- Les anciens libellés ambigus « Enregistrer cet onglet » / « Enregistrer tous les réglages » sont remplacés dans l’interface par les actions de saison correspondantes.
- Ajout de tests de non-régression GitHub pour la gestion brouillon/publié, la validation du second créneau et l’aperçu horaire.