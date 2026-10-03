# FAQ + Contact — 1.19.5

## Principe

La FAQ est la source principale de réponse aux visiteurs. Le formulaire de contact ne doit pas devenir une seconde FAQ ni un système d’orientation qui recopie les réponses existantes.

Dans les shortcodes `[parc_faq_contact]`, `[parc_faq_contact_fr]`, `[parc_faq_contact_en]` et `[parc_faq_contact_de]` :

1. la FAQ est affichée en premier ;
2. le bloc « Vous n’avez pas trouvé votre réponse ? » reste placé après la FAQ ;
3. le formulaire Contact Form 7 est masqué par défaut ;
4. le visiteur doit cliquer volontairement sur « Je n’ai pas trouvé ma réponse » pour afficher le formulaire ;
5. une recherche sans résultat rend le bloc de contact disponible, sans ouvrir automatiquement le formulaire.

## Contact Form 7

Contact Form 7 reste le seul moteur d’envoi des messages et conserve ses destinataires et réglages habituels. L’extension ne stocke pas les messages et ne crée pas un second moteur d’e-mail.

Les cas nécessitant un traitement spécifique dans le formulaire, comme une demande de lot / tombola, peuvent rester gérés dans Contact Form 7 et ses champs conditionnels. Les réponses générales (emploi, stage, recherche, groupes, horaires, tarifs, etc.) doivent être enrichies dans la FAQ plutôt que dupliquées dans le formulaire.

## Administration

Dans `Gestion du parc > FAQ`, l’activation du bloc de contact est présentée comme un contact « en dernier recours ». Les shortcodes Contact Form 7 FR / EN / DE restent configurables séparément.
