# Audit 1.19.2 — FAQ & Contact

## Objectif

Finaliser la partie publique FAQ/Contact sans recréer un moteur de formulaire ni toucher aux données annuelles.

## Changements

- Le shortcode FAQ n’émet plus le faux en-tête visuel 1.19.0. Le titre et l’introduction sont rendus comme du texte simple et héritent du thème.
- Les shortcodes FAQ seuls restent compatibles : `[parc_faq]`, `[parc_faq_fr]`, `[parc_faq_en]`, `[parc_faq_de]`.
- Les nouveaux shortcodes `[parc_faq_contact]`, `[parc_faq_contact_fr]`, `[parc_faq_contact_en]`, `[parc_faq_contact_de]` ajoutent le formulaire après la FAQ.
- Gestion du parc > FAQ permet d’enregistrer les shortcodes Contact Form 7 FR/EN/DE et d’activer leur affichage.
- Contact Form 7 reste le moteur d’envoi des e-mails. L’extension ne crée pas un deuxième système d’envoi et ne stocke pas les messages visiteurs.
- Les réglages sont enregistrés dans l’option indépendante `parcs_ht_faq_contact`.

## Isolation

Aucune écriture de cette évolution ne vise `parcs_ht_settings`. Les saisons, horaires, tarifs, événements, groupes et devis ne sont pas modifiés.

## Compatibilité

- PHP 7.4 / 8.1 / 8.2 / 8.3.
- Contact Form 7 reste facultatif : si le shortcode n’existe pas, aucun texte de shortcode brut n’est affiché publiquement.
- L’attribut `titre="0"` reste compatible et masque aussi l’introduction texte simple.
