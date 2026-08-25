# Historique des versions

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
