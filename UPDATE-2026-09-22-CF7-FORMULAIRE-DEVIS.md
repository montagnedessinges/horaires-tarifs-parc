# Mise à jour CF7 — formulaire devis groupes FR — 22/09/2026

Cette mise à jour concerne uniquement le formulaire CF7. Le PDF actuellement en production reste inchangé pour le moment ; sa future évolution conditionnelle est suivie dans l’issue #63.

Flux conservé : **CF7 → Gestion du parc → Send PDF for Contact Form 7 → mPDF**.

## Formulaire CF7 de référence

```html
<div class="flex-xbetween">

  [hidden devisannee]
  [hidden tarifenfant]
  [hidden tarifadulte]
  [hidden tarifhandicap]
  [hidden tarifaccompagnateur]

  <div class="cp full">
    <label>Votre groupe*</label>
    [select* groupedevis include_blank "Groupe" "Groupe en situation de handicap"]
  </div>

  [group group-scolaire clear_on_hide]

  <div class="cp"><label>Nombre d'enfants (3 à 18 ans)</label>[number nbrenfants class:nbrenfants min:0]</div>
  <div class="cp"><label>Nombre d'adultes</label>[number nbradultes class:nbradultes min:0]</div>
  <div class="cp"><label>Nombre d'adultes gratuits</label>[text nbradultgratuit class:nbradultgratuit "0"]</div>
  <div class="cp"><label>Nombre d'adultes payants</label>[text nbradultpayant class:nbradultpayant "0"]</div>
  <div class="cp"><label>Total billets enfants</label>[text nbrprixenfants class:nbrprixenfants]</div>
  <div class="cp"><label>Total billets adultes</label>[text nbrprixadultes class:nbrprixadultes]</div>
  <div class="cp full"><label>Total Global TTC</label>[text totalprixscolaire class:totalprixscolaire]</div>

  <div class="cp full">
    <label>Tranche(s) d'âge des enfants</label>
    [checkbox ageenfants use_label_element "3-5 ans" "6-10 ans" "11-14 ans" "15-18 ans"]
    <small>Plusieurs choix possibles.</small>
  </div>

  [/group]

  [group group-handicape clear_on_hide]

  <div class="cp"><label>Nombre de personnes en situation de handicap</label>[number nbrpersohandicape class:nbrpersohandicape min:0]</div>
  <div class="cp"><label>Nombre d'accompagnateurs</label>[number nbraccompa class:nbraccompa min:0]</div>
  <div class="cp full"><label>Total Global TTC</label>[text totalprixhandicape class:totalprixhandicape readonly]</div>

  [/group]

  <div class="cp full"><label>Votre organisme*</label>[text* organisme]</div>
  <div class="cp"><label>Nom du contact*</label>[text* nom]</div>
  <div class="cp"><label>Email*</label>[email* emailform]</div>
  <div class="cp"><label>Téléphone*</label>[tel* telephone]</div>
  <div class="cp full"><label>Adresse*</label>[text* adresse]</div>
  <div class="cp"><label>Code postal</label>[text postal]</div>
  <div class="cp"><label>Ville</label>[text city]</div>
  <div class="cp"><label>Pays</label>[text country]</div>

  <div class="cp"><label>Date de visite*</label>[date* visite]</div>
  <div class="cp"><label>Heure d'arrivée prévue*</label>[text* heurevisite placeholder "Ex. : 10h00"]</div>

  <div class="cp full">
    <p><strong>Responsable du groupe le jour de la visite</strong><br>
    Si vous connaissez déjà la personne responsable le jour de la visite, vous pouvez renseigner ses coordonnées. Sinon, vous pourrez les compléter ultérieurement sur le devis.</p>
  </div>

  <div class="cp"><label>Nom du responsable le jour de la visite</label>[text responsablejour_nom]</div>
  <div class="cp"><label>Téléphone du responsable le jour de la visite</label>[tel responsablejour_tel]</div>

  <div class="cp full">
    <label>Langue(s) du groupe</label>
    [checkbox langues use_label_element default:1 "Français" "Allemand" "Anglais" "Autre"]
    <small>Plusieurs choix possibles.</small>
  </div>

  [group group-langue-autre clear_on_hide]
  <div class="cp full"><label>Précisez la ou les autres langues</label>[text langueautre]</div>
  [/group]

  <div class="cp full">
    <label>Moyen de paiement prévu*</label>
    [radio moyenpaiement use_label_element "Paiement sur place" "Paiement différé"]
  </div>

  [group group-paiement-differe clear_on_hide]

  <div class="cp full">
    <label>Type de paiement différé*</label>
    [radio typediffere use_label_element "Voucher" "Bon de commande" "Chorus Pro"]
  </div>

  [group group-chorus clear_on_hide]

  <div class="cp full">
    <p><strong>Informations pour la facturation via Chorus Pro</strong><br>
    Si vous connaissez ces informations, vous pouvez les renseigner maintenant. Sinon, laissez les champs vides : ils pourront être complétés ultérieurement sur le devis.</p>
  </div>

  <div class="cp"><label>N° SIRET de facturation</label>[text siretfacturation maxlength:14]</div>
  <div class="cp"><label>Code service</label>[text codeservice]</div>
  <div class="cp full"><label>Référence d'engagement / N° de bon de commande</label>[text referenceengagement]</div>

  [/group]
  [/group]

  <div class="cp full">
    [acceptance acceptance-319]
    J’accepte la politique de confidentialité.
    [/acceptance]
  </div>

  <div class="cp full"><div class="wp-txt-center">[submit "Générer mon devis"]</div></div>

</div>
```

## Conditional Fields for CF7 — mode texte

```text
show [group-scolaire] if [groupedevis] equals "Groupe"
show [group-handicape] if [groupedevis] equals "Groupe en situation de handicap"
show [group-langue-autre] if [langues] equals "Autre"
show [group-paiement-differe] if [moyenpaiement] equals "Paiement différé"
show [group-chorus] if [typediffere] equals "Chorus Pro"
```

## Compatibilité

Les champs de calcul historiques restent inchangés (`visite`, `groupedevis`, `devisannee`, tarifs, quantités et totaux). Cette mise à jour ne modifie donc pas le moteur actuel de calcul des devis groupes.

Nouveaux champs documentés : `ageenfants`, `heurevisite`, `responsablejour_nom`, `responsablejour_tel`, `langues`, `langueautre`, `moyenpaiement`, `typediffere`, `siretfacturation`, `codeservice`, `referenceengagement`.

Le PDF reste sur l’ancien code tant que l’issue #63 n’est pas développée et testée.
