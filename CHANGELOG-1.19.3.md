# Gestion du parc 1.19.3

## FAQ : une seule source de vérité

- Toutes les questions visiteurs destinées à la FAQ publique, y compris les règles de visite, proviennent désormais de la base FAQ importée par CSV.
- L’ancien accordéon « Règles de visite » de la couche IA & Google n’est plus ajouté sous Horaires & Tarifs.
- L’ancien bloc éditable de connaissances de l’écran IA & Google est remplacé visuellement par une information qui renvoie vers Gestion du parc → FAQ. Les anciennes données restent conservées en base pour compatibilité, sans être utilisées comme seconde source publique.
- Les fiches comme « Peut-on nourrir les singes ? » et « Peut-on toucher ou caresser les singes ? » doivent vivre dans l’onglet du parc et être importées avec le même CSV.

## FAQ + formulaire de contact

- Le shortcode FAQ + Contact affiche désormais un bouton « Nous écrire » après la FAQ.
- Le formulaire Contact Form 7 reste fermé jusqu’au clic puis s’ouvre sur la même page.
- En cas de recherche sans résultat, le bloc de contact reste directement disponible sous la FAQ.
- Le bouton n’est rendu que si l’intégration est activée, Contact Form 7 est disponible et un shortcode de formulaire existe pour la langue affichée.
- Contact Form 7 reste le seul moteur d’envoi d’e-mail ; aucune duplication de `wp_mail()` n’est ajoutée.

## Données et compatibilité

- Aucun changement de schéma des saisons, tarifs, horaires, devis ou groupes.
- Les options FAQ et Contact restent isolées des réglages annuels.
- Les données historiques `parcs_ht_ai_google` sont conservées.
