(function ($) {
    'use strict';
    var config = window.ParcsHTQuoteGate || null;
    if (!config) return;

    function contactHtml(value) {
        value = String(value || '').trim();
        if (!value) return '';
        var href = value.indexOf('@') > 0 && value.indexOf('://') < 0 ? 'mailto:' + value : value;
        return ' <a href="' + $('<div>').text(href).html() + '">' + $('<div>').text(value).html() + '</a>';
    }
    function box($form) {
        var $box = $form.find('.parcs-ht-quote-gate-message');
        if (!$box.length) {
            $box = $('<div class="parcs-ht-quote-gate-message" role="status" aria-live="polite"></div>');
            $form.find('[name="visite"]').first().closest('.cp').after($box);
        }
        return $box;
    }
    function parts($form) {
        var $date = $form.find('[name="visite"]').first();
        var $datePart = $date.closest('.cp');
        return { date: $date, datePart: $datePart, others: $form.children().not($datePart).not('.parcs-ht-quote-gate-message').not('input[type="hidden"]') };
    }
    function showMessage($form, text, contact, kind) {
        var $box = box($form);
        if (!text) { $box.empty().hide(); return; }
        $box.attr('data-kind', kind || '').html($('<div>').text(text).html() + contactHtml(contact)).show();
    }
    function init($form) {
        if (!$form.length || $form.data('parcsHtGateReady')) return;
        if (!$form.find('[name="visite"]').length || !$form.find('[name="groupedevis"]').length) return;
        $form.data('parcsHtGateReady', true);
        var p = parts($form);
        p.others.hide();
        box($form).hide();

        function check() {
            var date = String(p.date.val() || '');
            if (!/^20\d{2}-\d{2}-\d{2}$/.test(date)) { p.others.hide(); showMessage($form, '', '', ''); return; }
            $.post(config.ajaxUrl, { action:'parcs_ht_quote_date_status', nonce:config.nonce, date:date }).done(function (response) {
                var data = response && response.success ? response.data : null;
                if (!data || !data.tariffs) {
                    p.others.hide();
                    showMessage($form, config.unavailableEnabled ? config.unavailableMessage : '', config.unavailableContact, 'unavailable');
                    return;
                }
                p.others.show();
                if (data.closed && config.closedEnabled) showMessage($form, config.closedMessage, config.closedContact, 'closed');
                else showMessage($form, '', '', '');
                p.date.trigger('parcsHtQuoteDateReady');
            }).fail(function () {
                p.others.hide();
                showMessage($form, config.unavailableEnabled ? config.unavailableMessage : 'Impossible de vérifier cette date pour le moment.' , config.unavailableContact, 'error');
            });
        }
        p.date.on('change', check);
        check();
    }
    function scan() { $('.wpcf7 form, form.wpcf7-form').each(function () { init($(this)); }); }
    $(scan);
    document.addEventListener('wpcf7init', scan);
})(jQuery);
