# Base de référence — Devis groupes 2026 FR

Date d'archivage : 30/08/2026

Ce fichier conserve l'état de base du formulaire Contact Form 7 français 2026 et de ses e-mails avant toute correction, traduction ou adaptation. Ne pas remplacer cette base par une version future : ajouter les évolutions séparément afin de conserver un retour arrière fiable.

## 1. Formulaire CF7 français — code source de référence

```html
<div class="flex-xbetween">

  <div class="cp full">
    <label for="groupedevis">Votre groupe</label>
    [select* groupedevis include_blank "Groupe" "Groupe en situation de handicap"]
  </div>

  [group group-scolaire]
  <div class="cp"><label>Nombre d'enfants (3 à 18 ans)</label>[number nbrenfants class:nbrenfants min:0]</div>
  <div class="cp"><label>Nombre d'adultes</label>[number nbradultes class:nbradultes min:0]</div>
  <div class="cp"><label>Nombre d'adultes gratuits</label>[text nbradultgratuit class:nbradultgratuit "0"]</div>
  <div class="cp"><label>Nombre d'adultes payants</label>[text nbradultpayant class:nbradultpayant "0"]</div>
  <div class="cp"><label>Total billets enfants</label>[text nbrprixenfants class:nbrprixenfants]</div>
  <div class="cp"><label>Total billets adultes</label>[text nbrprixadultes class:nbrprixadultes]</div>
  <div class="cp full"><label>Total Global TTC</label>[text totalprixscolaire class:totalprixscolaire]</div>
  [/group]

  [group group-handicape]
  <div class="cp"><label>Nombre de personnes handicapées</label>[number nbrpersohandicape class:nbrpersohandicape min:0]</div>
  <div class="cp"><label>Nombre d'accompagnateurs</label>[number nbraccompa class:nbraccompa min:0]</div>
  <div class="cp full"><label>Total Global TTC</label>[text totalprixhandicape class:totalprixhandicape readonly]</div>
  [/group]

  <div class="cp full"><label>Votre organisme*</label>[text* organisme]</div>
  <div class="cp"><label>Nom du responsable*</label>[text* nom]</div>
  <div class="cp"><label>Email*</label>[email* emailform]</div>
  <div class="cp"><label>Téléphone*</label>[tel* telephone]</div>
  <div class="cp"><label>Adresse*</label>[text* adresse]</div>
  <div class="cp"><label>Code postal</label>[text postal]</div>
  <div class="cp"><label>Ville</label>[text city]</div>
  <div class="cp"><label>Pays</label>[text country]</div>
  <div class="cp"><label>Date de visite*</label>[date* visite]</div>

  <div class="cp full">
  [acceptance acceptance-319]
  J’accepte la politique de confidentialité.
  [/acceptance]
  </div>

  <div class="cp full">
    <div class="wp-txt-center">
      [submit "Envoyer"]
    </div>
  </div>

</div>
```

## 2. E-mail interne reçu par l'équipe — base FR 2026

```html
<p><strong>NOUVELLE DEMANDE DE DEVIS – Montagne des Singes</strong></p>

<p>Une nouvelle demande a été envoyée depuis le site.</p>

<hr>

<p><strong>Type de groupe :</strong> [groupedevis]</p>

<p><strong>📅 Date de visite demandée :</strong> [visite]</p>

<hr>

<p><strong>📋 Informations du groupe :</strong></p>

<p>
Enfants : [nbrenfants]<br>
Adultes : [nbradultes]<br>
Adultes gratuits : [nbradultgratuit]<br>
Adultes payants : [nbradultpayant]<br>
Total billets enfants : [nbrprixenfants] €<br>
Total billets adultes : [nbrprixadultes] €<br>
Personnes en situation de handicap : [nbrpersohandicape]<br>
Accompagnateurs : [nbraccompa]<br>
<strong>Total TTC : [totalprixscolaire][totalprixhandicape] €</strong>
</p>

<hr>

<p><strong>📍 Coordonnées du demandeur :</strong></p>

<p>
Organisme : [organisme]<br>
Nom du contact : [nom]<br>
Email : [emailform]<br>
Téléphone : [telephone]<br>
Adresse : [adresse]<br>
Code postal : [postal]<br>
Ville : [city]<br>
Pays : [country]
</p>

<hr>

<p><strong>⚠️ Informations importantes :</strong></p>

<ul>
<li>Le devis doit être renvoyé signé avec la mention « Bon pour accord » pour valider la réservation.</li>
<li>Le groupe devra venir avec le devis imprimé le jour de la visite.</li>
<li>La facturation est réalisée selon le nombre réel de participants présents.</li>
<li>Les groupes peuvent régler par voucher, bon de commande, Chorus Pro ou sur place.</li>
<li>Prévoir tous les documents nécessaires à la facturation afin de gagner du temps à l’accueil.</li>
</ul>

<hr>

<p><strong>Message généré automatiquement – Montagne des Singes</strong></p>
```

## 3. E-mail client — base FR 2026

```html
<div style="font-family:Arial,Helvetica,sans-serif;background:#f4f4f4;padding:20px;">
  <div style="max-width:680px;margin:0 auto;background:#ffffff;padding:25px;color:#333333;line-height:1.5;">

    <!-- LOGO -->
    <div style="text-align:center;margin-bottom:20px;">
      <img
        src="https://www.montagnedessinges.com/wp-content/uploads/2026/08/MDS_horizontal_noir.svg"
        alt="La Montagne des Singes"
        width="150"
        style="display:inline-block;width:150px;max-width:150px;height:auto;"
      >
    </div>

    <p>Bonjour,</p>

    <p>
      Merci pour votre demande. Votre devis est joint à cet e-mail.
    </p>

    <!-- ACTION À FAIRE -->
    <div style="background:#fff4f1;padding:15px;border-left:4px solid #ef7658;margin:20px 0;">

      <p style="margin:0 0 8px 0;">
        <strong>⚠️ Votre réservation n’est pas encore confirmée</strong>
      </p>

      <p style="margin:0;">
        Signez votre devis avec la mention
        <strong>« Bon pour accord »</strong> et renvoyez-le à
        <strong>info@montagnedessinges.com</strong>.
      </p>

    </div>

    <!-- JOUR DE LA VISITE -->
    <div style="background:#f5f5f5;padding:15px;margin:20px 0;">

      <p style="margin:0 0 8px 0;">
        <strong>📅 Le jour de votre visite</strong>
      </p>

      <p style="margin:0;">
        Pensez à apporter <strong>votre devis imprimé et signé</strong>,
        ainsi que votre bon de commande, voucher ou document administratif si nécessaire.
      </p>

    </div>

    <p style="font-size:14px;">
      La facturation est réalisée selon le nombre réel de participants présents le jour de la visite.
      Il n’est pas nécessaire de nous prévenir en cas de changement d’effectif.
    </p>

    <hr style="margin:25px 0;border:0;border-top:1px solid #dddddd;">

    <!-- RÉCAPITULATIF -->
    <p><strong>📋 Récapitulatif</strong></p>

    <p>
      <strong>Groupe :</strong> [groupedevis]<br>
      <strong>Date :</strong> [visite]<br>
      <strong>Organisme :</strong> [organisme]<br>
      <strong>Contact :</strong> [nom]<br>
      <strong>E-mail :</strong> [emailform]<br>
      <strong>Téléphone :</strong> [telephone]
    </p>

    <p>
      Enfants : [nbrenfants]<br>
      Adultes : [nbradultes]<br>
      Adultes gratuits : [nbradultgratuit]<br>
      Adultes payants : [nbradultpayant]<br>
      Personnes en situation de handicap : [nbrpersohandicape]<br>
      Accompagnateurs : [nbraccompa]<br>
      <strong>Total TTC : [totalprixscolaire][totalprixhandicape] €</strong>
    </p>

    <hr style="margin:25px 0;border:0;border-top:1px solid #dddddd;">

    <!-- LIENS -->
    <p style="margin-bottom:8px;"><strong>Préparer votre visite</strong></p>

    <p style="margin:0;">
      📚
      <a href="https://www.montagnedessinges.com/ressources-pedagogiques-ecoles-periscolaire/"
         style="color:#006b57;font-weight:bold;">
        Ressources pédagogiques
      </a>
      &nbsp;&nbsp;•&nbsp;&nbsp;
      ⏰
      <a href="https://www.montagnedessinges.com/infos-pratiques/horaires-et-tarifs-2/"
         style="color:#006b57;font-weight:bold;">
        Horaires et tarifs
      </a>
    </p>

  </div>
</div>
```

## 4. Éléments encore à archiver avant traduction/correction

- paramètres CF7 des champs e-mail (destinataire, expéditeur, objet, en-têtes supplémentaires) si nécessaires à la reprise ;
- code/configuration de génération du PDF ;
- modèle PDF complet ;
- nom du plugin ou module CF7 utilisé pour générer le PDF ;
- éventuels réglages conditionnels liés au PDF ;
- shortcode exact du formulaire FR 2026 si disponible.

## Règle de retour arrière

Cette base doit rester disponible même après les corrections. En cas de régression, elle sert à reconstruire le fonctionnement français 2026 tel qu'il existait avant les traductions EN/DE.