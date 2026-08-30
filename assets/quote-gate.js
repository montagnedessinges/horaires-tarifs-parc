(function ($) {
    'use strict';
    var config = window.ParcsHTQuoteGate || null;
    if (!config) return;
    var visitField = String(config.visitField || 'visite');
    function contactHtml(value) {
        value = String(value || '').trim();
        if (!value) return '';
        var href = value.indexOf('@') > 0 && value.indexOf('://') < 0 ? 'mailto:' + value : value;
        return ' <a href="' + $('<div>').text(href).html() + '">' + $('<div>').text(value).html() + '</a>';
    }
    function fieldSelector(name) { return '[name="' + String(name).replace(/"/g, '\\"') + '"]'; }
    function labels(lang) {
        if (lang === 'de') return { title: 'Besuchsdatum', intro: 'Wählen Sie zuerst Ihr Besuchsdatum, um das vollständige Anfrageformular zu öffnen.' };
        if (lang === 'en') return { title: 'Visit date', intro: 'First choose your visit date to open the full quote form.' };
        return { title: 'Date de visite', intro: 'Choisissez d’abord votre date de visite pour accéder au formulaire complet de devis.' };
    }
    function init($quote) {
        if (!$quote.length || $quote.data('parcsHtGateReady')) return;
        var $wrap = $quote.find('.parcs-ht-quote-form-wrap').first();
        var $formBox = $wrap.find('.parcs-ht-quote-form').first();
        var $form = $formBox.find('.wpcf7 form, form.wpcf7-form').first();
        if (!$wrap.length || !$formBox.length || !$form.length) return;
        var $cf7Date = $form.find(fieldSelector(visitField)).first();
        if (!$cf7Date.length) return;
        $quote.data('parcsHtGateReady', true).addClass('parcs-ht-gate-active');
        var lang = String($quote.data('htp-lang') || 'fr');
        var text = labels(lang);
        var $gate = $('<div class="parcs-ht-quote-access" data-htp-quote-access></div>');
        $gate.append($('<div class="parcs-ht-quote-access-title"></div>').text(text.title));
        $gate.append($('<p class="parcs-ht-quote-access-intro"></p>').text(text.intro));
        var $input = $('<input class="parcs-ht-quote-access-date" type="date">');
        var $status = $('<div class="parcs-ht-quote-gate-message" role="status" aria-live="polite"></div>');
        $gate.append($input, $status);
        $formBox.before($gate);
        var syncing = false;
        function message(textValue, contact, kind) {
            if (!textValue) { $status.empty().hide(); return; }
            $status.attr('data-kind', kind || '').html($('<div>').text(textValue).html() + contactHtml(contact)).show();
        }
        function closeForm() { $quote.removeClass('parcs-ht-gate-open'); }
        function openForm(date, data) {
            syncing = true;
            $cf7Date.val(date).trigger('change');
            syncing = false;
            $quote.addClass('parcs-ht-gate-open');
            if (data.closed && config.closedEnabled) message(config.closedMessage, config.closedContact, 'closed');
            else message('', '', '');
        }
        function check(date) {
            date = String(date || '');
            if (!/^20\d{2}-\d{2}-\d{2}$/.test(date)) { closeForm(); message('', '', ''); return; }
            closeForm();
            $.post(config.ajaxUrl, { action: 'parcs_ht_quote_date_status', nonce: config.nonce, date: date })
                .done(function (response) {
                    var data = response && response.success ? response.data : null;
                    if (!data || !data.tariffs) {
                        closeForm();
                        message(config.unavailableEnabled ? config.unavailableMessage : '', config.unavailableContact, 'unavailable');
                        return;
                    }
                    openForm(date, data);
                })
                .fail(function () {
                    closeForm();
                    message(config.unavailableEnabled ? config.unavailableMessage : 'Impossible de vérifier cette date pour le moment.', config.unavailableContact, 'error');
                });
        }
        $input.on('change', function () { check($input.val()); });
        $cf7Date.on('change', function () {
            if (syncing) return;
            var date = String($cf7Date.val() || '');
            $input.val(date);
            check(date);
        });
        if ($cf7Date.val()) $input.val($cf7Date.val());
        check($input.val());
    }
    function scan() { $('.parcs-ht-quote').each(function () { init($(this)); }); }
    $(scan);
    document.addEventListener('wpcf7init', scan);
})(jQuery);
