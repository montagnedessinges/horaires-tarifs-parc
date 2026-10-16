# Bons découverte scolaires (1.20.7)

Le module « Gestion du parc → Groupes → Bons découverte » permet d'afficher un bloc de demande, de vérifier le code postal puis de dévoiler un formulaire Contact Form 7 déjà créé. Il ne génère ni n'envoie aucun bon.

## Mise en place (à faire pour chaque parc)

1. Installer la release GitHub 1.20.7.
2. Ouvrir Gestion du parc → Groupes → Bons découverte.
3. Saisir manuellement les départements éligibles de CE parc, séparés par virgules (exemple : 67, 68). Deux chiffres métropolitains, ou trois chiffres pour les DROM. Une liste vide n'autorise aucune demande.
4. Régler si nécessaire la date de fin de l'offre ; laisser vide pour aucune fermeture automatique.
5. Créer un nouveau Contact Form 7 distinct des devis groupes avec le modèle ci-dessous et renseigner son shortcode dans les réglages du module.
6. Personnaliser les titres, descriptions, boutons, messages et conditions du bloc en français.
7. Tester, puis activer le bloc public. Chaque installation WordPress a ses propres réglages ; ne pas recopier les départements d'un parc à l'autre.

## Modèle Contact Form 7

Copier ce formulaire dans l'onglet Formulaire d'un nouveau CF7 :

    <div class="bon-decouverte-cf7">
      <p><label>Nom de l'établissement scolaire * [text* nom_etablissement]</label></p>
      <p><label>Commune * [text* commune_etablissement]</label></p>
      <p><label>Code postal vérifié * [text* bon_code_postal readonly]</label></p>
      <p><label>Nom et prénom * [text* nom_demandeur autocomplete:name]</label></p>
      <p><label>Votre fonction * [select* fonction include_blank "Enseignant(e)" "Direction" "Équipe pédagogique" "Autre"]</label></p>
      <p><label>Votre adresse e-mail * [email* email_demandeur autocomplete:email]</label></p>
      <p>[acceptance acceptation_confidentialite] J'ai pris connaissance de la politique de confidentialité et suis informé(e) que ces données servent au traitement de ma demande. [/acceptance]</p>
      <p>[submit "Envoyer ma demande"]</p>
    </div>

Le nom exact du champ [text* bon_code_postal readonly] est impératif. Le bloc WordPress lui transmet le code postal après contrôle AJAX ; le serveur revérifie le code lors de la soumission, et bloque un envoi CF7 manquant, invalide ou hors zone.

Dans l'onglet Mail, configurer la notification interne avec [nom_etablissement], [commune_etablissement], [bon_code_postal], [nom_demandeur], [fonction] et [email_demandeur].

Activer Mail (2), destinataire [email_demandeur], pour l'accusé de réception, SANS pièce jointe ni PDF. Exemple de message :

« Bonjour, nous avons bien reçu votre demande de bon découverte pour votre établissement. Notre équipe va vérifier les conditions de l'offre et l'historique des bons déjà utilisés. Nous reviendrons vers vous après vérification. À bientôt à la Montagne des Singes ! »

Adapter le nom du parc pour La Forêt des Singes. Un consentement aux futures prospections, si demandé, doit être séparé et facultatif.

## Afficher le bloc et lier Brevo

Ajouter le shortcode [parc_bon_decouverte] dans la page WordPress. Le module Bons découverte est en français uniquement.

L'enseignant vérifie son code postal puis le CF7 apparaît au même endroit, sans redirection. Le bouton de l'e-mail Brevo doit pointer vers cette page WordPress.

## Ce qui n'est pas automatique

- Un seul bon par établissement : vérification MANUELLE par l'équipe, qui doit également tenir compte des bons papier déjà distribués et des passages en caisse.
- Aucun bon envoyé automatiquement ; CF7 envoie uniquement une confirmation de réception et un mail interne.
- Aucun Google Sheet modifié ; l'analyse scolaire 2027+ et les rapprochements Brevo/Gmail/réservations constituent une étape distincte.
- Le module ne touche ni aux devis groupes, ni aux guides pédagogiques, ni aux données des autres campagnes.

## Vérifications avant lancement

Vérifier les codes postaux autorisés, hors zone, incorrects ; la version mobile ; les emails Mail et Mail (2) de CF7 ; et l'impossibilité d'envoyer sans code autorisé. La page et les CF7 des deux parcs doivent être configurés indépendamment.
