# Historique des versions

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

## 1.9.3
- Tarifs indépendants par saison : une saison brouillon peut être préparée sans modifier les tarifs de la saison publiée.
- La duplication d’une saison peut emporter ses tarifs et la copie reste en brouillon jusqu’à publication explicite.
- Migration non destructive des tarifs existants vers les saisons qui n’avaient pas encore leur propre configuration.
- Colonnes tarifaires affichables/masquables, notamment pour préparer « Sur place » et « En ligne ».
- Offres spéciales avec période de vente/affichage distincte de la période de validité des billets.
- Plusieurs billets (adulte, enfant, etc.) peuvent partager un même repère interne d’offre, uniquement en français.
- Pop-up facultatif lié à une offre spéciale pendant sa période de vente ; titre public facultatif.
- Aperçu d’administration étendu aux tarifs de la saison sélectionnée, y compris en brouillon, selon la date simulée.
- Correction : seul le repère interne FR est obligatoire ; aucun titre public FR/EN/DE n’est obligatoire.
- Tests de non-régression et contrôles de syntaxe ajoutés au workflow GitHub.

## 1.9.2
- Suppression de la rustine qui parcourait le DOM Elementor/thème pour rechercher un texte statique « Ouvert / Open / Geöffnet » et le remplacer dynamiquement.
- La synchronisation publique ne modifie désormais que les composants natifs de l’extension (`home-opening`, `header-status`, `header-hour`, `today`).
- Conservation du moteur d’état partagé introduit en 1.9.1 pour les phases avant ouverture, ouvert, coupure entre créneaux et après fermeture.
- Le bloc de page d’accueil doit utiliser le composant/shortcode dynamique de l’extension ; un texte statique ajouté dans Elementor ou le thème n’est plus corrigé artificiellement par JavaScript.
- Aucun changement des horaires, dernières entrées, tarifs, pop-up ou règles de saison dans cette version : mise à jour volontairement ciblée sur la simplification de l’affichage public.

## 1.9.1
- Unification de l’aperçu d’administration et de l’affichage public autour d’un moteur d’état partagé (`assets/display-state.js`) : mêmes phases horaires, mêmes créneaux et mêmes calculs de dernière entrée.
- Correction des dernières entrées par créneau : le créneau 1 utilise `last_entry_minutes_slot1` et le créneau 2 `last_entry_minutes_slot2`, avec fallback vers les anciens champs uniquement pour compatibilité.
- Correction du cas 10h–12h / 13h–17h30 avec 30 minutes : dernière entrée 11h30 puis 17h00.
- Le moteur public de synchronisation met également à jour le bloc « Aujourd’hui » et le bloc d’accueil avec ce calcul partagé.
- Ajout du réglage « Langues publiques actives » dans l’administration : français toujours obligatoire, anglais et allemand activables indépendamment selon le site.
- Les contrôles de pop-up et de contenus publics ne rendent obligatoires que les langues actives ; un site FR/EN ne génère plus d’erreur pour l’allemand.
- Les titres FR deviennent obligatoires pour les périodes/événements et exceptions activés afin que chaque règle soit identifiable dans l’administration, même si elle n’est pas affichée publiquement.
- Pour un élément visible publiquement, les titres des autres langues actives deviennent obligatoires.
- Ajout d’une validation immédiate avant enregistrement : champs manquants, créneau 2 incomplet et chevauchements sont signalés et l’enregistrement est bloqué jusqu’à correction.
- Les messages de vérification identifient désormais le type d’élément, son numéro, son titre lorsqu’il existe et ses dates.
- Le contrôle après sauvegarde relit les valeurs réellement persistées afin de tester le bon choix de langues et les valeurs filtrées.
- Ajout d’un test GitHub dédié au moteur partagé et aux dernières entrées des deux créneaux.

## 1.9.0
- Refonte du système de vérification : suppression du contrôle quotidien et passage à une vérification événementielle.
- Un contrôle complet est lancé après chaque enregistrement de configuration, une seule fois après changement de version de l’extension, ou manuellement depuis l’administration.
- Vérification des créneaux : heures inversées, créneau 2 incomplet, chevauchements et cohérence des dernières entrées.
- Vérification des rendus principaux : composant d’accueil, statut/horaire d’en-tête et bloc « Aujourd’hui » de la page Horaires & Tarifs dans les trois langues.
- Vérification des pop-up FR / EN / DE : chaque pop-up actif doit disposer d’un contenu correspondant à chaque langue.
- La langue des pop-up reste pilotée par qTranslate-XT lorsqu’il est disponible, avec fallback sur la locale WordPress ; elle ne dépend pas de `navigator.language`.
- Ajout d’un test de non-régression GitHub qui bloque une release si la sélection de langue des pop-up revient à la langue du navigateur ou si un contrôle quotidien est réintroduit.
- Le résultat du dernier contrôle est mémorisé et visible dans l’administration et dans Santé du site.
- Les erreurs techniques runtime restent consignées, mais les e-mails/contrôles quotidiens répétitifs sont supprimés.

## 1.8.9
- L’onglet « Aperçu » devient un simulateur date + heure pour tester le comportement public sans attendre l’heure réelle.
- Le simulateur affiche séparément le rendu attendu sur la page d’accueil et dans le bloc « Aujourd’hui » de la page Horaires & Tarifs.
- L’aperçu indique la règle réellement appliquée : horaire classique, horaire exceptionnel prioritaire, fermeture exceptionnelle ou absence d’horaire.
- Le simulateur prend en compte les phases avant ouverture, pendant un créneau, entre deux créneaux et après la fermeture finale, avec la dernière entrée du créneau actif.
- L’onglet Aperçu signale et représente le premier pop-up actif correspondant à la date testée lorsqu’il est configuré dans l’administration.
- Le bouton « Enregistrer tous les réglages » est retiré de l’interface afin de privilégier la sauvegarde sûre de l’onglet actif.
- Le nouveau module d’aperçu est chargé uniquement dans l’administration de l’extension et n’ajoute aucune charge aux pages publiques.
- Le workflow GitHub vérifie désormais aussi la syntaxe du nouveau module `assets/admin-preview-enhanced.js`.

## 1.8.8
- Correction du statut public lorsqu’un horaire exceptionnel est actif : les classes d’état utilisent désormais l’ouverture réelle à l’instant T et non le simple fait que la journée possède des horaires.
- Un horaire exceptionnel suit désormais la même logique visuelle qu’un horaire classique : avant ouverture, ouvert pendant le créneau, réouverture entre deux créneaux, puis prochaine ouverture après la fermeture finale.
- Correction ciblée du cas observé à La Forêt des Singes où un horaire exceptionnel pouvait laisser un libellé « Ouvert » après la fermeture.
- Ajout d’un test JavaScript couvrant un horaire exceptionnel avant ouverture, pendant l’ouverture, après fermeture et entre deux créneaux.
- Le workflow contrôle maintenant aussi `assets/status-sync.js` et exécute le nouveau test de statut dynamique.
- Les releases déjà publiées sont désormais conservées telles quelles : le workflow ne remplace plus leurs ZIP avec `--clobber`.

## 1.8.7
- Correction du bloc d’accueil lorsque le libellé « Ouvert » est un texte statique du thème placé à côté du shortcode horaire.
- Le moteur synchronise désormais ce libellé visuel avec l’état horaire réel : ouverture future, réouverture, ouvert maintenant ou prochaine ouverture.
- Cette synchronisation reste limitée au bloc contenant le shortcode horaire afin d’éviter de modifier d’autres contenus du site.
- Le contrôle reste réévalué automatiquement toutes les 30 secondes.

## 1.8.6
- Synchronisation du statut public avec l’état horaire réel pour éviter toute combinaison incohérente du type « Ouvert » avec une ouverture future.
- Les shortcodes de statut et d’horaire utilisent désormais un contrôle commun supplémentaire chargé après les moteurs historiques.
- Avant ouverture : affichage d’une ouverture future ; entre deux créneaux : réouverture ; pendant un créneau : OUVERT ; après fermeture : prochaine ouverture.
- Le contrôle est réévalué automatiquement toutes les 30 secondes.

## 1.8.5
- Vérification complète du mécanisme de mise à jour GitHub : dépôt, slug, URL de mise à jour et noms des assets inchangés depuis la 1.8.1.
- Renforcement des appels HTTPS vers GitHub en utilisant explicitement le bundle de certificats CA fourni par WordPress.
- La vérification SSL reste obligatoire ; aucun contournement `sslverify=false` n’est utilisé.
- Le correctif s’applique uniquement aux hôtes GitHub et GitHubusercontent utilisés par le système de mise à jour.

## 1.8.4
- Le statut public est désormais recalculé selon l’heure réelle de consultation : avant ouverture, pendant un créneau, entre deux créneaux et après la fermeture finale.
- Suppression des combinaisons incohérentes du type « OUVERT » avec une heure d’ouverture future.
- Avant le premier créneau : affichage « Ouverture à … » ; entre deux créneaux : « Réouverture à … » ; après le dernier créneau : « Fermé pour aujourd’hui » puis prochaine ouverture.
- Les pages d’accueil, l’en-tête et le bloc « Aujourd’hui » utilisent la même logique.
- Le calcul est réévalué automatiquement chaque minute sans rechargement de la page.
- Conservation des dernières entrées distinctes pour les créneaux 1 et 2 et du résumé mensuel multi-horaires.

## 1.8.2
- Ajout d’une dernière entrée indépendante pour le créneau 1 et le créneau 2, sans modifier la structure existante des horaires.
- Compatibilité automatique avec les anciens réglages : l’ancien délai spécifique est conservé comme valeur de secours pour les deux créneaux.
- Affichage adaptatif sur la page d’accueil, l’en-tête et le bloc « Aujourd’hui » : la journée complète reste visible le matin, puis seuls les créneaux encore utiles sont affichés après la première fermeture.
- Le détail d’une date affiche toutes les dernières entrées correspondantes lorsqu’une journée comporte plusieurs créneaux.
- Le résumé « Horaires du mois » affiche toutes les combinaisons horaires réellement présentes avec leurs dates d’application, avec libellés compacts pour tout le mois, les week-ends ou du lundi au vendredi lorsque cela correspond exactement au calendrier.
- Même code pour la Montagne des Singes et la Forêt des Singes ; chaque site conserve ses propres réglages WordPress.

## 1.8.1
- Correction de l’affichage des doubles créneaux sur la première page, le bandeau, le calendrier, le détail du jour et l’aperçu d’administration.
- Le résumé mensuel conserve toutes les combinaisons horaires réellement présentes dans le mois.
- Entre les deux créneaux, affichage d’une réouverture le jour même au lieu d’une fermeture définitive.
- Calcul de la dernière entrée à partir du dernier créneau et libellés accessibles complets.
- Tests de non-régression sur le cas `10 h–12 h / 14 h–18 h` du 27 octobre.

## 1.8.0
- Moteur PHP canonique pour les priorités horaires, fermetures, événements et accès limité, avec tests de parité JavaScript.
- Correction de l’accès limité qui pouvait apparaître sur le site un jour fermé et du texte automatique des doubles horaires exceptionnels.
- Diagnostic annuel dans l’administration, aperçu par date, intégration Santé du site et historique restaurable des dix dernières configurations.
- Notifications e-mail anti-spam lors d’un nouveau conflit, d’une erreur PDF ou d’un problème de mise à jour GitHub.
- Planning annuel complet de janvier à décembre ; une seconde page de légende est ajoutée automatiquement sans supprimer d’information.
- PDF horaires et tarifs prégénérés après enregistrement, cache atomique surveillé, URL publique stable et limitation des rafales de téléchargement.
- Vérification obligatoire du SHA-256 avant installation d’une release GitHub et chiffrement du token enregistré lorsque le serveur le permet.
- Sauvegarde limitée à l’onglet actif pour éviter les formulaires tronqués, avec conservation de la sauvegarde globale.
- Fuseau horaire configurable, fenêtres modales accessibles et nettoyage facultatif des données à la désinstallation.
- Tests PHP/JavaScript multi-versions, contrôle Plugin Check et paquet de production nettoyé des fichiers de développement.

## 1.7.10
- Journées sans horaire et fermetures exceptionnelles clairement barrées et indiquées fermées.
- Distinction visuelle entre fermeture exceptionnelle et horaire exceptionnel.
- Légende contextuelle détaillée, identique aux règles du calendrier public.

## 1.7.9
- Affichage complet des doubles plages horaires dans la légende du planning annuel.
- Avertissement automatique indiquant le parc, la date de génération et le site à vérifier avant la visite.

## 1.7.8
- Nouveau planning annuel sur une page A3 paysage avec légende globale.
- Respect de la priorité exception applicable, horaire normal applicable, fermeture.
- Affichage des périodes et événements uniquement lorsqu’ils sont visibles sur le calendrier public.
- Prise en compte des jours fériés et des règles d’accès temporairement limité au domaine.
- Cache des PDF horaires et tarifs invalidé automatiquement lors d’une modification des réglages.

## 1.7.7
- Finalisation du système natif de mise à jour depuis les releases GitHub privées.
- Vérification manuelle protégée par `manage_options` et nonce.
- Affichage explicite de la version locale, de la version GitHub, de l’état et de la dernière vérification.
- Conservation du mécanisme WordPress pour les mises à jour automatiques.
- Renforcement de la construction et des contrôles du ZIP de release.

## 1.7.6
- GitHub devient la source canonique vérifiée avant chaque évolution.
- Bouton de vérification immédiate des mises à jour depuis WordPress.
- Affichage version locale / version GitHub / dernière vérification.
- Option de mises à jour automatiques WordPress pour les releases GitHub privées.
- Publication automatisée d’un ZIP de release depuis la branche `main`.

## 1.7.5
- Audit et optimisations de performances.
- Chargement conditionnel des modules administration, GitHub, alertes et shortcodes.
- Réduction des données JavaScript et mise en cache de calculs.
- Cache renforcé des appels GitHub.

## 1.7.4
- Planning PDF visuel basé sur le calendrier effectif.
- Priorité des horaires exceptionnels sur les horaires normaux dans l’export.
- Événements intégrés au planning.
- Configuration de la clé GitHub directement depuis WordPress.

## 1.7.3
- Première intégration du moteur de mises à jour GitHub privées.