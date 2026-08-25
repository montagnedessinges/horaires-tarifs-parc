# Historique des versions

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
