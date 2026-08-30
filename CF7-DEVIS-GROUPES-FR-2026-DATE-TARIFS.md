# Formulaire CF7 Devis groupes FR — version fournie le 30/08/2026

Cette copie est conservée séparément de `BASE-DEVIS-GROUPES-2026-FR.md`, qui reste la base stable avant modification.

Objectif de cette version : conserver le formulaire français existant et ajouter les champs techniques nécessaires au moteur de tarifs piloté par la date. Le shortcode CF7 utilisé dans l’administration de l’extension reste modifiable. Les versions EN/DE ne sont pas considérées comme corrigées/validées à ce stade.

```html
<div class="flex-xbetween">

  [hidden devisannee]
  [hidden tarifenfant]
  [hidden tarifadulte]
  [hidden tarifhandicap]
  [hidden tarifaccompagnateur]

  <div class="cp full">
    <label for="groupedevis">Votre groupe</label>
    [select* groupedevis include_blank "Groupe" "Groupe en situation de handicap"]
  </div>

  [group group-scolaire]

  <div class="cp">
    <label>Nombre d'enfants (3 à 18 ans)</label>
    [number nbrenfants class:nbrenfants min:0]
  </div>

  <div class="cp">
    <label>Nombre d'adultes</label>
    [number nbradultes class:nbradultes min:0]
  </div>

  <div class="cp">
    <label>Nombre d'adultes gratuits</label>
    [text nbradultgratuit class:nbradultgratuit "0"]
  </div>

  <div class="cp">
    <label>Nombre d'adultes payants</label>
    [text nbradultpayant class:nbradultpayant "0"]
  </div>

  <div class="cp">
    <label>Total billets enfants</label>
    [text nbrprixenfants class:nbrprixenfants]
  </div>

  <div class="cp">
    <label>Total billets adultes</label>
    [text nbrprixadultes class:nbrprixadultes]
  </div>

  <div class="cp full">
    <label>Total Global TTC</label>
    [text totalprixscolaire class:totalprixscolaire]
  </div>

  [/group]

  [group group-handicape]

  <div class="cp">
    <label>Nombre de personnes handicapées</label>
    [number nbrpersohandicape class:nbrpersohandicape min:0]
  </div>

  <div class="cp">
    <label>Nombre d'accompagnateurs</label>
    [number nbraccompa class:nbraccompa min:0]
  </div>

  <div class="cp full">
    <label>Total Global TTC</label>
    [text totalprixhandicape class:totalprixhandicape readonly]
  </div>

  [/group]

  <div class="cp full">
    <label>Votre organisme*</label>
    [text* organisme]
  </div>

  <div class="cp">
    <label>Nom du responsable*</label>
    [text* nom]
  </div>

  <div class="cp">
    <label>Email*</label>
    [email* emailform]
  </div>

  <div class="cp">
    <label>Téléphone*</label>
    [tel* telephone]
  </div>

  <div class="cp">
    <label>Adresse*</label>
    [text* adresse]
  </div>

  <div class="cp">
    <label>Code postal</label>
    [text postal]
  </div>

  <div class="cp">
    <label>Ville</label>
    [text city]
  </div>

  <div class="cp">
    <label>Pays</label>
    [text country]
  </div>

  <div class="cp">
    <label>Date de visite*</label>
    [date* visite]
  </div>

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

## Règle d’intégration validée

- Ne pas modifier les textes/blocs existants de la page Devis groupe.
- Le titre configuré au-dessus du formulaire reste visible avant le choix de la date.
- À l’emplacement du formulaire, afficher d’abord le contrôle de date du plugin.
- Si une grille tarifaire est publiée pour l’année : afficher le CF7 configuré et recopier la date dans `[visite]`.
- Si le parc est fermé mais que la grille existe : afficher le message de fermeture et autoriser quand même l’accès au formulaire.
- Si la grille n’existe pas : ne pas afficher le formulaire et afficher le message d’indisponibilité configuré.
- Le moteur calcule les tarifs à partir de l’année de `[visite]` et réécrit côté serveur les champs techniques et totaux.
- Pour le moment, le formulaire FR est la référence de test. EN/DE seront adaptés et validés ultérieurement.
