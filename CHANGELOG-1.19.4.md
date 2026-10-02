# Gestion du parc 1.19.4

## Guides pédagogiques : bibliothèque permanente

- Les guides ne sont plus dupliqués par saison : un document appartient à une bibliothèque permanente commune à toutes les années.
- L’année sélectionnée conserve uniquement l’état d’affichage du document. Un guide ajouté plus tard reste masqué dans les années déjà configurées tant qu’il n’y est pas explicitement activé.
- Les identifiants statistiques restent permanents. La migration des anciennes bibliothèques saisonnières regroupe les copies d’un même document et conserve leurs anciens identifiants comme alias.
- Les statistiques restent enregistrées avec l’année de saison et peuvent toujours être consultées sur 7 jours, 30 jours, la saison sélectionnée ou toutes les saisons.
- La couche de sauvegarde sécurisée utilise le même stockage v4 et ne peut plus réintroduire l’ancien format par saison.

## Administration : suppression des retours vers les anciens menus

- Aperçu et Shortcodes deviennent de vrais écrans dédiés au lieu de ponts vers l’ancien formulaire à onglets.
- Les anciennes URL Guides, Devis groupes, Pop-up et Calendrier de l’Avent sont rabattues vers leurs écrans métier canoniques.
- Une URL d’onglet historique inconnue revient vers la Vue d’ensemble au lieu d’afficher l’ancien écran.
- L’ancien panneau Guides embarqué n’est plus enregistré, le masquage JavaScript de son sous-menu est supprimé et les doublons de slugs sont filtrés dans la navigation.
- Le lien vers les « réglages détaillés historiques » est retiré de l’Administration générale.
- Le passage à 1.19.4 renouvelle aussi la version des assets administratifs afin d’éviter qu’un ancien JavaScript/CSS reste utilisé après mise à jour.

## Compatibilité et données

- Les shortcodes publics des guides restent inchangés.
- Les données de `parcs_ht_settings` ne changent pas.
- L’option `parcs_ht_pedagogical_guides` est migrée du stockage v3 par saisons au stockage v4 bibliothèque + états annuels.
- Les statistiques historiques ne sont pas supprimées.
