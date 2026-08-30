# Script devis groupes 2026 — version proposée

Date : 30/08/2026

## Objet

Remplacer le bloc historique de calcul du devis 2026 dans le fichier externe au dépôt `assets/js/script.js` du thème WordPress par un moteur unique, limité aux formulaires 2026 FR / EN / DE.

Cette proposition ne modifie pas l'extension `Horaires & Tarifs Parc` et ne nécessite donc pas de nouvelle version de l'extension. Elle concerne uniquement le JavaScript du thème.

## Base stable avant modification

La base historique connue reste documentée dans `AUDIT-2026-08-30-TARIFS-2027.md` :

- formulaire 2026 FR : ID `806`, ancien sélecteur `#wpcf7-f806-o1` ;
- tarif enfant : 6,00 € ;
- tarif adulte payant : 8,50 € ;
- un adulte gratuit par tranche complète de 10 enfants ;
- tarif personne en situation de handicap : 6,00 € ;
- tarif accompagnateur : 6,00 €.

Le fichier complet actuel `assets/js/script.js` du thème n'est pas versionné dans ce dépôt. Ne pas remplacer tout le fichier par le bloc ci-dessous : remplacer uniquement l'ancien bloc de calcul des devis après avoir conservé une copie complète du fichier actuel.

## Formulaires 2026 concernés

- FR : `806`
- EN : `cbb3f62`
- DE : `cfc327b`

Les trois formulaires utilisent les mêmes noms techniques de champs CF7.

## Version proposée

```js
(function ($) {
    'use strict';

    var DEVIS_2026 = [
        {
            selector: '[id^="wpcf7-f806-"]',
            schoolValue: 'Groupe',
            disabilityValue: 'Groupe en situation de handicap'
        },
        {
            selector: '[id^="wpcf7-fcbb3f62-"]',
            schoolValue: 'Group',
            disabilityValue: 'Group with people with disabilities'
        },
        {
            selector: '[id^="wpcf7-fcfc327b-"]',
            schoolValue: 'Gruppe',
            disabilityValue: 'Gruppe mit Menschen mit Behinderung'
        }
    ];

    var TARIFS_2026 = {
        enfant: 6,
        adulte: 8.50,
        handicap: 6,
        accompagnateur: 6
    };

    function nombre($form, selector) {
        var value = String($form.find(selector).first().val() || '').replace(',', '.');
        var number = parseFloat(value);
        return Number.isFinite(number) && number > 0 ? number : 0;
    }

    function euros(value) {
        return value.toFixed(2).replace('.', ',') + ' €';
    }

    function setValue($form, selector, value) {
        $form.find(selector).val(value);
    }

    function calculGroupe($form) {
        var enfants = nombre($form, '.nbrenfants');
        var adultes = nombre($form, '.nbradultes');
        var adultesGratuits = Math.min(adultes, Math.floor(enfants / 10));
        var adultesPayants = Math.max(0, adultes - adultesGratuits);
        var totalEnfants = enfants * TARIFS_2026.enfant;
        var totalAdultes = adultesPayants * TARIFS_2026.adulte;
        var total = totalEnfants + totalAdultes;

        setValue($form, '.nbradultgratuit', adultesGratuits);
        setValue($form, '.nbradultpayant', adultesPayants);
        setValue($form, '.nbrprixenfants', euros(totalEnfants));
        setValue($form, '.nbrprixadultes', euros(totalAdultes));
        setValue($form, '.totalprixscolaire', euros(total));
    }

    function calculHandicap($form) {
        var personnes = nombre($form, '.nbrpersohandicape');
        var accompagnateurs = nombre($form, '.nbraccompa');
        var total = (personnes * TARIFS_2026.handicap) + (accompagnateurs * TARIFS_2026.accompagnateur);

        setValue($form, '.totalprixhandicape', euros(total));
    }

    function viderGroupe($form) {
        setValue($form, '.nbrenfants', '');
        setValue($form, '.nbradultes', '');
        setValue($form, '.nbradultgratuit', '0');
        setValue($form, '.nbradultpayant', '0');
        setValue($form, '.nbrprixenfants', '');
        setValue($form, '.nbrprixadultes', '');
        setValue($form, '.totalprixscolaire', '');
    }

    function viderHandicap($form) {
        setValue($form, '.nbrpersohandicape', '');
        setValue($form, '.nbraccompa', '');
        setValue($form, '.totalprixhandicape', '');
    }

    function synchroniserType($form, config) {
        var type = $form.find('[name="groupedevis"]').val();

        if (type === config.schoolValue) {
            viderHandicap($form);
            calculGroupe($form);
        } else if (type === config.disabilityValue) {
            viderGroupe($form);
            calculHandicap($form);
        } else {
            viderGroupe($form);
            viderHandicap($form);
        }
    }

    function initialiser($wrapper, config) {
        var $form = $wrapper.find('form').first();

        if (!$form.length || $form.data('devis-2026-init')) {
            return;
        }

        $form.data('devis-2026-init', true);

        $form.find('.nbradultgratuit, .nbradultpayant, .nbrprixenfants, .nbrprixadultes, .totalprixscolaire, .totalprixhandicape')
            .prop('readonly', true);

        $form.on('input.devis2026 change.devis2026', '.nbrenfants, .nbradultes', function () {
            calculGroupe($form);
        });

        $form.on('input.devis2026 change.devis2026', '.nbrpersohandicape, .nbraccompa', function () {
            calculHandicap($form);
        });

        $form.on('change.devis2026', '[name="groupedevis"]', function () {
            synchroniserType($form, config);
        });

        synchroniserType($form, config);
    }

    $(function () {
        DEVIS_2026.forEach(function (config) {
            $(config.selector).each(function () {
                initialiser($(this), config);
            });
        });
    });

})(jQuery);
```

## Points de vigilance avant mise en production

1. Conserver une copie intégrale du `assets/js/script.js` actuel du thème avant remplacement du bloc devis.
2. Ne pas supprimer les autres fonctions du thème contenues dans ce fichier.
3. Tester séparément FR / EN / DE : groupe standard, groupe handicap, changement de type de groupe, zéro participant et 10 / 20 / 30 enfants.
4. Vérifier que les identifiants HTML générés par CF7 commencent bien par `wpcf7-f806-`, `wpcf7-fcbb3f62-` et `wpcf7-fcfc327b-`.
5. Le script conserve volontairement le format `12,00 €` dans les champs calculés pour rester compatible avec les modèles PDF actuels. Les modèles d'e-mail ne doivent donc pas ajouter un second symbole `€` après ces balises calculées.
6. La sécurité tarifaire reste côté navigateur : un visiteur techniquement avancé peut modifier une valeur avant envoi. À terme, recalculer les montants côté serveur si les montants envoyés deviennent une source comptable autoritative.
7. Ne pas brancher le formulaire 2027 sur ce bloc tant que les tarifs 2027 et la stratégie annuelle ne sont pas validés.
