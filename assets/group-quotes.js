(function ($) {
    'use strict';

    var config = window.ParcsHTGroupQuotes || null;
    if (!config || !config.visitField || !config.groupField) return;

    function number($form, selector) {
        var raw = String($form.find(selector).first().val() || '').replace(',', '.');
        var value = parseFloat(raw);
        return isNaN(value) || value < 0 ? 0 : value;
    }

    function euro(value) {
        return Number(value).toFixed(2).replace('.', ',') + ' €';
    }

    function set($form, selector, value) {
        $form.find(selector).val(value);
    }

    function visitYear($form) {
        var value = String($form.find('[name="' + config.visitField + '"]').val() || '');
        var match = value.match(/^(20\d{2})-/);
        return match ? match[1] : '';
    }

    function season($form) {
        var year = visitYear($form);
        if (!year || !config.seasons || !config.seasons[year]) return null;
        var row = config.seasons[year];
        if (String(row.published || '0') !== '1') return null;
        return row;
    }

    function messageBox($form) {
        var $box = $form.find('.parcs-ht-quote-message');
        if (!$box.length) {
            $box = $('<p class="parcs-ht-quote-message" role="status" aria-live="polite"></p>');
            $form.find('[name="' + config.visitField + '"]').first().after($box);
        }
        return $box;
    }

    function setPdfFields($form, year, row) {
        set($form, '[name="devisannee"]', year || '');
        set($form, '[name="tarifenfant"]', row ? euro(parseFloat(row.child || 0)) : '');
        set($form, '[name="tarifadulte"]', row ? euro(parseFloat(row.adult || 0)) : '');
        set($form, '[name="tarifhandicap"]', row ? euro(parseFloat(row.disability || 0)) : '');
        set($form, '[name="tarifaccompagnateur"]', row ? euro(parseFloat(row.companion || 0)) : '');
    }

    function clearCalculated($form) {
        set($form, '.nbradultgratuit', '0');
        set($form, '.nbradultpayant', '0');
        set($form, '.nbrprixenfants', '');
        set($form, '.nbrprixadultes', '');
        set($form, '.totalprixscolaire', '');
        set($form, '.totalprixhandicape', '');
        setPdfFields($form, '', null);
    }

    function currentSeason($form) {
        var year = visitYear($form);
        var row = season($form);
        var $box = messageBox($form);
        if (!year) {
            $box.text('');
            setPdfFields($form, '', null);
            return null;
        }
        if (!row) {
            $box.text(config.unavailableMessage || 'Tarifs indisponibles pour cette année.');
            clearCalculated($form);
            return null;
        }
        $box.text('');
        setPdfFields($form, year, row);
        return row;
    }

    function school($form) {
        var row = currentSeason($form);
        if (!row) return;
        var children = number($form, '.nbrenfants');
        var adults = number($form, '.nbradultes');
        var ratio = Math.max(1, parseInt(row.free_adult_children, 10) || 10);
        var freeAdults = Math.min(adults, Math.floor(children / ratio));
        var payingAdults = Math.max(0, adults - freeAdults);
        var childrenTotal = children * parseFloat(row.child || 0);
        var adultsTotal = payingAdults * parseFloat(row.adult || 0);
        set($form, '.nbradultgratuit', freeAdults);
        set($form, '.nbradultpayant', payingAdults);
        set($form, '.nbrprixenfants', euro(childrenTotal));
        set($form, '.nbrprixadultes', euro(adultsTotal));
        set($form, '.totalprixscolaire', euro(childrenTotal + adultsTotal));
    }

    function disability($form) {
        var row = currentSeason($form);
        if (!row) return;
        var people = number($form, '.nbrpersohandicape');
        var companions = number($form, '.nbraccompa');
        var total = people * parseFloat(row.disability || 0) + companions * parseFloat(row.companion || 0);
        set($form, '.totalprixhandicape', euro(total));
    }

    function sync($form) {
        var type = String($form.find('[name="' + config.groupField + '"]').val() || '');
        if (type === config.schoolValue) {
            set($form, '.totalprixhandicape', '');
            school($form);
        } else if (type === config.disabilityValue) {
            set($form, '.nbradultgratuit', '0');
            set($form, '.nbradultpayant', '0');
            set($form, '.nbrprixenfants', '');
            set($form, '.nbrprixadultes', '');
            set($form, '.totalprixscolaire', '');
            disability($form);
        } else {
            clearCalculated($form);
            currentSeason($form);
        }
    }

    function isQuoteForm($form) {
        return $form.find('[name="' + config.visitField + '"]').length > 0 &&
            $form.find('[name="' + config.groupField + '"]').length > 0 &&
            ($form.find('.nbrenfants,.nbradultes,.nbrpersohandicape,.nbraccompa').length > 0);
    }

    function init($form) {
        if (!$form.length || !isQuoteForm($form) || $form.data('parcsHtQuoteReady')) return;
        $form.data('parcsHtQuoteReady', true);
        $form.find('.nbradultgratuit,.nbradultpayant,.nbrprixenfants,.nbrprixadultes,.totalprixscolaire,.totalprixhandicape').prop('readonly', true);
        $form.on('input change', '.nbrenfants,.nbradultes', function () { school($form); });
        $form.on('input change', '.nbrpersohandicape,.nbraccompa', function () { disability($form); });
        $form.on('change', '[name="' + config.visitField + '"],[name="' + config.groupField + '"]', function () { sync($form); });
        sync($form);
    }

    function scan() {
        $('.wpcf7 form, form.wpcf7-form').each(function () {
            init($(this));
        });
    }

    $(scan);
    document.addEventListener('wpcf7init', scan);
})(jQuery);
