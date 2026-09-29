# FAQ 1.19.0 — finalisation

Périmètre autorisé : finaliser la branche `feature/1.19.0-faq`, PR #79, puis publier après contrôles verts. Version conservée car 1.19.0 n’était pas publiée ; dernière release consultée : v1.18.0. Lecture préalable d’AGENTS.md, CHATGPT-CONTEXT.md et de l’audit sauvegardes 1.17.11.

## Diagnostic et choix

Le lien Sheet était un simple repère et le script utilisait une source fixe. L’écran ne donnait pas accès au script pourtant distribué dans assets. La demande couvre une fonctionnalité manquante, sans raison de modifier les moteurs annuels.

Le choix de source est maintenant conservé uniquement dans `parcs_ht_faq.google`, après lecture et validation distante. Le script reçoit cet identifiant à chaque export, sans écrire de source distante : aucun protocole de synchronisation supplémentaire ni redéploiement lors d’un changement de Sheet. Les formulaires d’affichage et de connexion sont distincts. Chaque handler vérifie sa capacité et son nonce.

La clé passe uniquement dans le POST initial. La redirection ContentService se fait en GET sans corps et uniquement vers le domaine Google attendu. Le manifeste fourni impose spreadsheets.readonly. Les exports sont limités aux colonnes publiques utiles ; les notes internes ne sont pas transmises.

## Vérifications

Tests exécutables avec API WordPress/Google simulées : mauvais secret, fichier inaccessible, onglet/colonnes invalides, mauvais parc, doublons, réponse malformée, redirection interdite, source précédente intacte en cas d’échec, clé vide conservée, formulaire incomplet, édition d’affichage pendant test Google, aperçu sans écriture, import sélectionné, lignes bloquées, restauration, sauvegarde de sécurité défaillante, aperçus d’ancienne source rejetés, nonces/capacités, isolation des options annuelles.

Les tests FAQ présents sur la branche n’étaient pas raccordés au workflow et le contrat lisait un fichier docs absent du paquet. La CI couvre désormais les sources et le paquet nettoyé ainsi que les régressions de sauvegarde 1.17.11. Le script distribué et sa copie documentaire doivent rester identiques.

Limites : aucune intervention ni test sur les sites de production ; aucun déploiement Apps Script dans un compte réel. Les tests de simulation et la CI ne constituent pas une validation du cycle réel Google → WordPress avec les données du parc. La release fournit le nécessaire pour cette configuration initiale.
