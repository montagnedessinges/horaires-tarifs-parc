# Mise à jour 1.19.6 — Règles du parc et base IA

## Objectif

Rendre certaines questions utiles à l’IA disponibles sans les mettre en avant dans la FAQ principale.

La catégorie **« Règles du parc »** devient une catégorie secondaire :

- absente des filtres principaux ;
- fermée par défaut dans un accordéon discret ;
- toujours accessible volontairement par le visiteur ;
- présente dans le HTML initial ;
- incluse dans la recherche de la FAQ ;
- ouverte automatiquement lorsqu’une recherche correspond à une de ses questions.

Ce fonctionnement évite les techniques de texte invisible ou de contenu réservé aux robots.

## Connaissances singes

Gestion du parc → FAQ propose un second import CSV pour l’onglet **« Connaissances singes - IA »**.

Les lignes importées doivent :

- utiliser un ID `SIN-COM-*` ;
- avoir un statut validé ;
- indiquer un usage IA ;
- contenir au minimum une question et une réponse françaises.

Elles sont converties en fiches **« Règles du parc »** puis fusionnées dans le stockage FAQ existant `parcs_ht_faq`.

Aucune seconde base publique n’est créée.

## Sources

Pour une fiche secondaire, l’extension affiche un lien **« Source »** lorsque la source officielle correspond à une URL publique sûre. Les URLs Google Drive/Docs internes restent exclues par la règle existante de filtrage des liens publics.

## Sécurité des imports

Les garanties existantes sont conservées :

- aperçu avant écriture ;
- sélection humaine ;
- révision de sécurité avant import ;
- aucune suppression automatique ;
- aucun accès aux saisons, horaires, tarifs ou devis ;
- IDs dupliqués refusés ;
- import FAQ parc et import connaissances IA fusionnés dans le même stockage.

## Compatibilité

- WordPress : inchangé ;
- PHP : inchangé, minimum 7.4 ;
- shortcodes FAQ : inchangés ;
- Contact Form 7 : inchangé ;
- données FAQ déjà enregistrées : conservées.

## Vérifications attendues avant fusion

- syntaxe PHP ;
- contrats FAQ ;
- test runtime FAQ ;
- validation JavaScript ;
- WordPress Plugin Check ;
- paquet de production ;
- test manuel : catégorie « Règles du parc » non visible dans les filtres principaux ;
- test manuel : accordéon secondaire fermé au chargement ;
- test manuel : recherche d’une question secondaire ouvre le bloc ;
- test manuel : import `SIN-COM-*` n’écrase aucune fiche FAQ existante.
