# Devis groupes 2026 — version allemande

Date : 30/08/2026
Statut : brouillon de traduction à intégrer dans Contact Form 7 après validation.

Formulaire CF7 concerné :

- titre : `Formulaire devis DE`
- shortcode : `[contact-form-7 id="cfc327b" title="Formulaire devis DE"]`
- identifiant : `cfc327b`
- année : 2026
- langue : allemand

La base française d'origine reste archivée séparément dans `BASE-DEVIS-GROUPES-2026-FR.md` et ne doit pas être remplacée.

## Formulaire CF7 allemand proposé

```html
<div class="flex-xbetween">

  <div class="cp full">
    <label for="groupedevis">Ihre Gruppe</label>
    [select* groupedevis include_blank "Gruppe" "Gruppe mit Menschen mit Behinderung"]
  </div>

  [group group-scolaire]
  <div class="cp"><label>Anzahl der Kinder (3 bis 18 Jahre)</label>[number nbrenfants class:nbrenfants min:0]</div>
  <div class="cp"><label>Anzahl der Erwachsenen</label>[number nbradultes class:nbradultes min:0]</div>
  <div class="cp"><label>Anzahl der Erwachsenen mit freiem Eintritt</label>[text nbradultgratuit class:nbradultgratuit "0"]</div>
  <div class="cp"><label>Anzahl der zahlenden Erwachsenen</label>[text nbradultpayant class:nbradultpayant "0"]</div>
  <div class="cp"><label>Gesamtpreis Kinder</label>[text nbrprixenfants class:nbrprixenfants]</div>
  <div class="cp"><label>Gesamtpreis Erwachsene</label>[text nbrprixadultes class:nbrprixadultes]</div>
  <div class="cp full"><label>Gesamtbetrag inkl. MwSt.</label>[text totalprixscolaire class:totalprixscolaire]</div>
  [/group]

  [group group-handicape]
  <div class="cp"><label>Anzahl der Menschen mit Behinderung</label>[number nbrpersohandicape class:nbrpersohandicape min:0]</div>
  <div class="cp"><label>Anzahl der Begleitpersonen</label>[number nbraccompa class:nbraccompa min:0]</div>
  <div class="cp full"><label>Gesamtbetrag inkl. MwSt.</label>[text totalprixhandicape class:totalprixhandicape readonly]</div>
  [/group]

  <div class="cp full"><label>Ihre Einrichtung / Organisation*</label>[text* organisme]</div>
  <div class="cp"><label>Name der verantwortlichen Person*</label>[text* nom]</div>
  <div class="cp"><label>E-Mail*</label>[email* emailform]</div>
  <div class="cp"><label>Telefon*</label>[tel* telephone]</div>
  <div class="cp"><label>Adresse*</label>[text* adresse]</div>
  <div class="cp"><label>Postleitzahl</label>[text postal]</div>
  <div class="cp"><label>Ort</label>[text city]</div>
  <div class="cp"><label>Land</label>[text country]</div>
  <div class="cp"><label>Besuchsdatum*</label>[date* visite]</div>

  <div class="cp full">
  [acceptance acceptance-319]
  Ich akzeptiere die Datenschutzerklärung.
  [/acceptance]
  </div>

  <div class="cp full">
    <div class="wp-txt-center">
      [submit "Senden"]
    </div>
  </div>

</div>
```

## Champs conditionnels allemands

À configurer dans Conditional Fields for Contact Form 7 :

```text
show [group-scolaire] if [groupedevis] equals "Gruppe"
show [group-handicape] if [groupedevis] equals "Gruppe mit Menschen mit Behinderung"
```

## Règles techniques conservées

- Tous les noms de champs CF7 restent identiques à la version française : `groupedevis`, `nbrenfants`, `nbradultes`, `nbradultgratuit`, `nbradultpayant`, `nbrprixenfants`, `nbrprixadultes`, `totalprixscolaire`, `nbrpersohandicape`, `nbraccompa`, `totalprixhandicape`, `organisme`, `nom`, `emailform`, `telephone`, `adresse`, `postal`, `city`, `country`, `visite`.
- Les noms des groupes conditionnels restent `group-scolaire` et `group-handicape` afin de ne pas multiplier les différences techniques entre langues.
- Seuls les libellés publics et les valeurs visibles de `groupedevis` sont traduits.
- Comme les conditions utilisent la valeur textuelle exacte de `groupedevis`, les deux règles conditionnelles allemandes ci-dessus sont obligatoires si ces valeurs traduites sont utilisées.
- Les calculs tarifaires du thème doivent être adaptés pour reconnaître le formulaire DE `cfc327b` sans dupliquer inutilement la logique de calcul.

## À faire ensuite

Après validation du formulaire :

- préparer l'e-mail interne lié au formulaire DE ;
- préparer l'e-mail client DE ;
- préparer le PDF DE en partant de la base française archivée ;
- retirer ou adapter les éléments administratifs exclusivement français dans le PDF allemand, notamment Chorus Pro / SIRET / code service si leur présence n'est pas utile ;
- documenter toute modification du JavaScript du thème nécessaire au calcul des tarifs du formulaire DE.