# Roadmap – prochaines évolutions à discuter

Ce fichier consigne les idées validées ou à débattre avant développement. Ne pas implémenter automatiquement ces points sans nouvelle validation utilisateur.

## Règle d’administration : repère interne obligatoire, contenu public facultatif
- Une période, un événement, une exception ou une offre doit toujours avoir un repère interne permettant de l’identifier facilement dans l’administration.
- Le repère interne est uniquement en français ; aucune traduction EN / DE n’est nécessaire.
- Le titre public n’est jamais obligatoire.
- Si aucun titre public n’est souhaité, l’élément peut tout de même être publié et affiché.
- Les traductions FR / EN / DE d’un titre public ne doivent être contrôlées que si ce titre public a réellement été saisi.
- Pour les périodes / événements, le champ de référence est le libellé interne.
- Pour les exceptions, le contexte / motif sert de repère obligatoire.

## Gestion explicite du statut d’une saison
- La sauvegarde et la publication doivent être deux actions différentes : enregistrer une saison ne doit jamais la publier automatiquement.
- Pour une saison en brouillon, afficher deux actions accessibles depuis tous les onglets : « Enregistrer le brouillon » et « Publier la saison ».
- « Enregistrer le brouillon » sauvegarde toute la configuration de la saison sans aucune visibilité publique.
- « Publier la saison » est une action explicite, avec confirmation, qui change son statut en publiée.
- Pour une saison déjà publiée, afficher « Enregistrer les modifications » et « Remettre en brouillon ».
- « Remettre en brouillon » désactive immédiatement la saison côté public sans supprimer ses horaires, tarifs, événements, exceptions, pop-up ou autres réglages.
- Une saison remise en brouillon peut être modifiée puis republiée ultérieurement sans perte de données.
- Les actions de sauvegarde / publication / remise en brouillon doivent rester visibles quel que soit l’onglet actif afin d’éviter de revenir dans « Parc & apparence ».
- Supprimer les libellés ambigus « Enregistrer cet onglet » et « Enregistrer tous les réglages » si la sauvegarde réelle porte sur toute la saison.
- Éviter une simple case « Afficher cette saison sur le site » mélangée aux réglages généraux : le statut de publication doit être une action distincte et volontaire.

## Publication indépendante des horaires et tarifs 2027 — à concevoir
- Besoin confirmé : une nouvelle saison peut avoir ses horaires connus avant ses tarifs.
- Publier les horaires 2027 ne doit pas obliger à publier les tarifs 2027.
- Une duplication de saison peut conserver les tarifs comme base de travail, mais les tarifs copiés doivent être considérés comme brouillons / à vérifier et ne jamais être présentés automatiquement comme tarifs de la nouvelle année.
- Prévoir un statut de publication tarifaire indépendant de celui des horaires.
- À étudier de préférence par catégorie : particuliers, réduits et groupes peuvent être publiés à des moments différents.
- Prévoir éventuellement une date de début d’affichage par catégorie afin que les tarifs particuliers 2027 puissent devenir publics seulement après la fermeture de la saison 2026.
- Si seuls les tarifs groupes 2027 sont connus, il doit être possible de publier uniquement la partie groupes et le parcours devis sans exposer de tarifs particuliers 2027.
- Si une catégorie tarifaire n’est pas publiée ou ne contient aucune ligne publique, ne pas afficher un onglet vide.
- L’affichage public doit toujours rendre explicite l’année des tarifs lorsqu’une page peut déjà présenter les horaires de l’année suivante.
- Le formulaire de devis groupe repose actuellement sur Contact Form 7 et n’utilise pas directement le tableau de tarifs de l’extension. À décider : conserver deux sources indépendantes ou créer à terme une source de vérité commune pour les tarifs groupes.
- Audit technique de référence : `AUDIT-2026-08-30-TARIFS-2027.md`.

## Devis groupes multilingues et PDF — à concevoir
- Les devis doivent pouvoir exister simultanément pour plusieurs années et plusieurs langues : FR, EN et DE.
- Une année doit idéalement avoir un seul jeu de tarifs groupes partagé par ses formulaires FR/EN/DE.
- Le formulaire 2026 français existant reste la base fonctionnelle à préserver pendant la migration.
- Le formulaire 2027 FR connu est `[contact-form-7 id="2b9aa49" title="Formulaire devis 2027"]`.
- Les identifiants des futurs formulaires 2026 EN/DE et 2027 EN/DE doivent être documentés dès leur création.
- Chaque formulaire de devis est également lié à une génération PDF via Contact Form 7 / extension associée. Le code/configuration exacts du PDF doivent être conservés dans la documentation GitHub dès qu'ils sont fournis ou identifiés.
- Prévoir un modèle PDF par langue lorsque le contenu diffère réellement selon le pays/langue, tout en mutualisant autant que possible les données métier communes.
- La traduction allemande ne doit pas reprendre automatiquement les informations uniquement utiles ou applicables aux visiteurs/structures françaises. Les différences FR/EN/DE doivent être explicitement documentées et validées.
- Lorsqu'un code, template, HTML, CSS, JavaScript, réglage CF7 ou configuration PDF situé dans le thème ou dans WordPress est modifié mais ne fait pas partie du dépôt de l'extension, conserver malgré tout dans GitHub une copie de référence ou une documentation suffisamment complète pour pouvoir le reconstruire.
- Pour chaque modification externe liée au module devis, consigner : date, emplacement WordPress/thème/plugin, version ou état avant modification si disponible, code/configuration de référence, modification effectuée, formulaires/années/langues concernés et raison de la modification.
- Objectif à terme : pouvoir reprendre le système devis/PDF depuis GitHub même si une future conversation n'a plus le contexte des modifications manuelles faites dans WordPress.
- Cette évolution nécessitera une nouvelle version de l'extension lorsqu'une partie fonctionnelle sera intégrée au plugin.

## Validation des doubles créneaux
- Le créneau 2 reste totalement facultatif pour les horaires classiques comme exceptionnels.
- Si ouverture 2 et fermeture 2 sont toutes les deux vides, aucune erreur ni mise en évidence ne doit apparaître.
- Une erreur « créneau 2 incomplet » ne doit apparaître que si un seul des deux champs est renseigné.
- Si les deux champs sont renseignés, la validation contrôle uniquement leur ordre et un éventuel chevauchement avec le créneau 1.

## Tarifs : plusieurs colonnes de prix
- Prévoir la possibilité d’ajouter une ou plusieurs colonnes tarifaires supplémentaires par catégorie.
- Cas d’usage prioritaire : différencier un tarif « Sur place » et un tarif « En ligne » pour une même ligne (ex. adulte).
- L’interface doit rester simple : une seule colonne si aucun tarif différencié n’est nécessaire, colonnes supplémentaires facultatives.
- Une colonne doit pouvoir être ajoutée, supprimée et donc affichée ou masquée selon les besoins de chaque saison.
- Les colonnes doivent être compatibles FR / EN / DE selon les langues publiques actives.
- À débattre : affichage mobile, ordre des colonnes, ancien prix éventuel et compatibilité PDF.

## Offres tarifaires temporaires
- Prévoir des offres ou billets spéciaux valables sur une période définie, par exemple une offre uniquement pendant le mois de juin.
- Une offre doit pouvoir regrouper plusieurs billets / lignes tarifaires, par exemple Adulte + Enfant, sous une même offre.
- Une offre doit distinguer clairement deux périodes indépendantes : période de vente et période de validité des billets.
- Exemple : billets utilisables du 1er au 30 juin mais vente arrêtée avant le 30 juin afin d’éviter une vente trop tardive.
- Une offre doit pouvoir avoir : repère interne, titre public facultatif, dates de vente, dates de validité, plusieurs lignes de prix, texte explicatif, langue(s), visibilité, canal de vente et éventuellement lien d’achat.
- L’offre ne doit pas remplacer les tarifs standards : elle s’affiche comme une offre complémentaire dans l’onglet tarifaire existant, sans créer un nouvel onglet public obligatoire.
- Une offre doit pouvoir être liée facultativement au moteur de pop-up existant afin d’éviter de saisir deux fois les mêmes dates et informations.
- À débattre : emplacement précis dans le tableau, badge visuel, priorité entre plusieurs offres et intégration PDF.

## Aperçu complet
- L’onglet Aperçu conserve la simulation date + heure existante.
- L’heure testée doit être explicitement interprétée : avant ouverture, ouvert, entre deux créneaux ou après fermeture, avec l’heure d’ouverture/réouverture/fermeture correspondante visible dans le diagnostic.
- La page d’accueil et le bloc « Aujourd’hui » reposent sur le même moteur partagé : l’aperçu ne doit afficher qu’un seul bloc « Affichage du jour » au lieu de deux rendus identiques.
- Il doit présenter dans un même endroit ce que verra le visiteur : affichage du jour, pop-up correspondant et tarifs.
- L’aperçu doit pouvoir afficher les tarifs et offres d’une saison brouillon sans les rendre visibles sur le site public.
- L’aperçu doit utiliser autant que possible le même moteur de rendu que le site public et ne pas constituer un second moteur indépendant.

## Préparation de l’année suivante
- Permettre de préparer une saison en brouillon sans effet sur le site public.
- La duplication d’une saison doit créer automatiquement la nouvelle saison en statut brouillon.
- La duplication doit conserver la structure des horaires, périodes, tarifs et autres réglages nécessaires, puis permettre de modifier les dates et valeurs de la nouvelle année.
- Toutes les modifications du brouillon doivent pouvoir être enregistrées autant de fois que nécessaire sans modifier la saison actuellement visible par le public.
- Une saison brouillon doit pouvoir être entièrement visualisée dans l’onglet Aperçu.
- Les dates mobiles ou événements ponctuels doivent être signalés « à vérifier » plutôt que décalés aveuglément.
- Une saison brouillon ne doit jamais être utilisée par le moteur public tant qu’elle n’est pas publiée.
- L’action « Publier » est l’étape explicite qui autorise cette saison à devenir visible au public selon ses dates.