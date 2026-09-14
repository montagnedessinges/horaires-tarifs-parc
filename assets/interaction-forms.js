(function () {
    'use strict';

    function text(value) {
        return String(value == null ? '' : value).trim();
    }

    function language(node) {
        var current = node && node.closest ? node.closest('[data-htp-lang],[data-guide-language],[data-language]') : null;
        var lang = current ? (current.getAttribute('data-htp-lang') || current.getAttribute('data-guide-language') || current.getAttribute('data-language')) : '';
        lang = text(lang).slice(0, 2).toLowerCase();
        if (lang === 'fr' || lang === 'en' || lang === 'de') return lang;
        lang = text(document.documentElement.getAttribute('lang')).slice(0, 2).toLowerCase();
        return lang === 'en' || lang === 'de' ? lang : 'fr';
    }

    function set(node, values) {
        if (!node || !node.setAttribute) return;
        Object.keys(values || {}).forEach(function (key) {
            var value = values[key];
            if (value === undefined || value === null || value === '') return;
            node.setAttribute('data-ga-' + key.replace(/_/g, '-'), String(value));
        });
    }

    function normalizeGroup(value) {
        var normalized = text(value).toLowerCase();
        if (normalized.normalize) normalized = normalized.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        if (normalized.indexOf('handicap') !== -1 || normalized.indexOf('disab') !== -1 || normalized.indexOf('behinder') !== -1) return 'disability_group';
        return normalized ? 'standard_group' : 'unknown';
    }

    function numberValue(form, name) {
        var field = form ? form.querySelector('[name="' + name + '"]') : null;
        var value = field ? Number(String(field.value || '').replace(',', '.')) : 0;
        return isFinite(value) && value > 0 ? value : 0;
    }

    function sizeBucket(form, type) {
        var total = type === 'disability_group'
            ? numberValue(form, 'nbrpersohandicape') + numberValue(form, 'nbraccompa')
            : numberValue(form, 'nbrenfants') + numberValue(form, 'nbradultes');
        if (total <= 0) return 'unknown';
        if (total <= 20) return '1_20';
        if (total <= 50) return '21_50';
        if (total <= 100) return '51_100';
        return '101_plus';
    }

    function updateQuote(root) {
        if (!root) return;
        var form = root.querySelector('.wpcf7 form, form.wpcf7-form');
        var accessDate = root.querySelector('.parcs-ht-quote-access-date');
        var visit = form ? form.querySelector('[name="visite"]') : null;
        var group = form ? form.querySelector('[name="groupedevis"]') : null;
        var date = text((visit && visit.value) || (accessDate && accessDate.value));
        var type = normalizeGroup(group ? group.value : '');
        var values = {
            module: 'group_quote',
            content_language: language(root),
            quote_type: type,
            visit_year: /^20\d{2}-\d{2}-\d{2}$/.test(date) ? date.slice(0, 4) : '',
            visit_month: /^20\d{2}-\d{2}-\d{2}$/.test(date) ? date.slice(0, 7) : '',
            group_size: form ? sizeBucket(form, type) : 'unknown'
        };
        set(root, values);
        if (accessDate) set(accessDate, Object.assign({ event: 'quote_date_selected' }, values));
        var formWrap = root.querySelector('.parcs-ht-quote-form');
        if (formWrap) set(formWrap, Object.assign({ view_event: 'quote_form_open' }, values));
        if (form) set(form, Object.assign({ success_event: 'generate_lead', source: 'quote_form' }, values));
    }

    function scanQuotes(root) {
        root.querySelectorAll('.parcs-ht-quote').forEach(function (quote) {
            set(quote, { module: 'group_quote', content_language: language(quote) });
            updateQuote(quote);
        });
    }

    function scanAlerts(root) {
        root.querySelectorAll('.parcs-ht-auto-modal, .parcs-ht-modal').forEach(function (modal) {
            var type = modal.classList.contains('parcs-ht-auto-modal') ? 'auto_popup' : 'shortcode_popup';
            set(modal, {
                view_event: 'alert_view',
                module: 'alert',
                content_type: 'alert',
                alert_type: type,
                source: type === 'auto_popup' ? 'site_popup' : 'shortcode_alert',
                content_language: language(modal)
            });
            modal.querySelectorAll('.parcs-ht-auto-link, .parcs-ht-button').forEach(function (link) {
                set(link, {
                    event: 'alert_cta_click',
                    module: 'alert',
                    content_type: 'alert',
                    alert_type: type,
                    source: type === 'auto_popup' ? 'site_popup' : 'shortcode_alert',
                    content_language: language(link)
                });
            });
        });
    }

    function selectedAdventContent(root) {
        var selected = root ? root.querySelector('[data-advent-day].is-selected') : null;
        return text(selected ? selected.getAttribute('data-content-id') : '');
    }

    function scanAdvent(root) {
        root.querySelectorAll('[data-advent-root]').forEach(function (advent) {
            var campaign = text(advent.getAttribute('data-campaign-id'));
            var lang = language(advent);
            set(advent, { module: 'advent', campaign_id: campaign, content_language: lang });

            advent.querySelectorAll('[data-advent-day]').forEach(function (button) {
                var match = text(button.textContent).match(/\d{1,2}/);
                set(button, {
                    event: 'advent_day_open',
                    module: 'advent',
                    campaign_id: campaign,
                    content_id: text(button.getAttribute('data-content-id')),
                    day_number: match ? Number(match[0]) : '',
                    content_language: lang
                });
            });

            advent.querySelectorAll('.parcs-ht-advent-social-links a').forEach(function (link) {
                var label = text(link.textContent).toLowerCase();
                set(link, {
                    event: 'advent_social_click',
                    module: 'advent',
                    platform: label.indexOf('instagram') !== -1 ? 'instagram' : (label.indexOf('facebook') !== -1 ? 'facebook' : 'social'),
                    campaign_id: campaign,
                    content_id: selectedAdventContent(advent),
                    content_language: lang
                });
            });

            advent.querySelectorAll('[data-advent-word-form]').forEach(function (form) {
                set(form, {
                    submit_event: 'advent_word_attempt',
                    module: 'advent',
                    campaign_id: campaign,
                    content_language: lang
                });
            });

            advent.querySelectorAll('.parcs-ht-advent-final.is-authorized').forEach(function (success) {
                set(success, {
                    view_event: 'advent_word_result',
                    module: 'advent',
                    result: 'success',
                    campaign_id: campaign,
                    content_language: lang
                });
            });

            advent.querySelectorAll('[data-advent-word-message]').forEach(function (message) {
                if (!text(message.textContent)) return;
                set(message, {
                    view_event: 'advent_word_result',
                    module: 'advent',
                    result: 'failure',
                    campaign_id: campaign,
                    content_language: lang
                });
            });

            advent.querySelectorAll('.parcs-ht-advent-final-form .wpcf7 form, .parcs-ht-advent-final-form form.wpcf7-form').forEach(function (form) {
                set(form, {
                    success_event: 'advent_entry_submit',
                    module: 'advent',
                    campaign_id: campaign,
                    content_language: lang
                });
            });
        });
    }

    function scan(root) {
        if (!root || !root.querySelectorAll) return;
        scanQuotes(root);
        scanAlerts(root);
        scanAdvent(root);
    }

    document.addEventListener('change', function (event) {
        var field = event.target && event.target.closest ? event.target : null;
        if (!field) return;
        var quote = field.closest('.parcs-ht-quote');
        if (quote) updateQuote(quote);
    }, true);

    scan(document);
    new MutationObserver(function () { scan(document); }).observe(document.documentElement, { childList: true, subtree: true });
}());
