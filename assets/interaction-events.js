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

    function season() {
        var payload = window.ParcsHTPData || {};
        var settings = payload.settings || {};
        return text(settings.activeSeasonYear || (settings.general || {}).year);
    }

    function set(node, values) {
        if (!node || !node.setAttribute) return;
        Object.keys(values || {}).forEach(function (key) {
            var value = values[key];
            if (value === undefined || value === null || value === '') return;
            node.setAttribute('data-ga-' + key.replace(/_/g, '-'), String(value));
        });
    }

    function scanCalendar(root) {
        root.querySelectorAll('[data-htp-date]').forEach(function (day) {
            var date = text(day.getAttribute('data-htp-date'));
            set(day, {
                event: 'calendar_date_select',
                module: 'calendar',
                content_language: language(day),
                season_year: date.slice(0, 4),
                selected_month: date.slice(0, 7),
                day_status: day.classList.contains('is-open') ? 'open' : 'closed',
                has_event: day.classList.contains('is-special-event') ? 'yes' : 'no'
            });
        });

        root.querySelectorAll('.parcs-ht-event-link').forEach(function (link) {
            var selected = root.querySelector('[data-htp-date].is-selected');
            var date = text(selected ? selected.getAttribute('data-htp-date') : '');
            var note = link.closest('.parcs-ht-event-note');
            set(link, {
                event: 'event_cta_click',
                module: 'calendar',
                content_type: note && note.classList.contains('is-period') ? 'period' : 'event',
                source: 'calendar',
                content_language: language(link),
                season_year: date.slice(0, 4) || season(),
                selected_month: date.slice(0, 7)
            });
        });

        root.querySelectorAll('.parcs-ht-schedule-export-button').forEach(function (link) {
            set(link, {
                event: 'document_download',
                module: 'calendar',
                document_type: 'schedule',
                season_year: season(),
                content_language: language(link)
            });
        });
    }

    function scanTariffs(root) {
        root.querySelectorAll('[data-htp-tariff-tab]').forEach(function (tab) {
            set(tab, {
                event: 'tariff_section_select',
                module: 'tariffs',
                tariff_section: text(tab.getAttribute('data-htp-tariff-tab')),
                season_year: season(),
                content_language: language(tab)
            });
        });

        root.querySelectorAll('.parcs-ht-tariff-export-button').forEach(function (link) {
            set(link, {
                event: 'document_download',
                module: 'tariffs',
                document_type: 'tariffs',
                season_year: season(),
                content_language: language(link)
            });
        });

        root.querySelectorAll('.parcs-ht-actions .parcs-ht-button.is-primary').forEach(function (link) {
            set(link, {
                event: 'ticket_cta_click',
                module: 'tariffs',
                source: 'main_tariffs',
                season_year: season(),
                content_language: language(link)
            });
        });

        root.querySelectorAll('.parcs-ht-special-buy').forEach(function (link) {
            set(link, {
                event: 'special_offer_click',
                module: 'tariffs',
                content_type: 'offer',
                source: link.closest('.parcs-ht-group-tariffs-only') ? 'group_tariffs' : 'main_tariffs',
                season_year: season(),
                content_language: language(link)
            });
        });

        root.querySelectorAll('.parcs-ht-group-tariffs-only .parcs-ht-panel-actions .parcs-ht-button, .parcs-ht-tariff-panel[id$="-panel-groups"] .parcs-ht-panel-actions .parcs-ht-button').forEach(function (link) {
            set(link, {
                event: 'quote_cta_click',
                module: 'group_quote',
                source: link.closest('.parcs-ht-group-tariffs-only') ? 'group_tariffs' : 'main_tariffs',
                season_year: season(),
                content_language: language(link)
            });
        });
    }

    function scanGuides(root) {
        root.querySelectorAll('a[data-guide-track]').forEach(function (link) {
            var card = link.closest('[data-guide-card]');
            var action = text(link.getAttribute('data-guide-action'));
            set(link, {
                event: action === 'download' ? 'guide_download' : 'guide_view',
                module: 'pedagogical_guide',
                content_id: text(link.getAttribute('data-guide-id')),
                school_cycle: text(card ? card.getAttribute('data-cycle') : ''),
                content_language: text(link.getAttribute('data-guide-lang')) || language(link),
                season_year: text(link.getAttribute('data-guide-season')) || season()
            });
        });

        root.querySelectorAll('[data-guide-cycle],[data-guide-language]').forEach(function (button) {
            var isCycle = button.hasAttribute('data-guide-cycle');
            set(button, {
                event: 'guide_filter_select',
                module: 'pedagogical_guide',
                filter_type: isCycle ? 'cycle' : 'language',
                filter_value: text(button.getAttribute(isCycle ? 'data-guide-cycle' : 'data-guide-language')),
                content_language: language(button),
                season_year: season()
            });
        });
    }

    function scan(root) {
        if (!root || !root.querySelectorAll) return;
        root.querySelectorAll('[data-htp-component="calendar"]').forEach(scanCalendar);
        root.querySelectorAll('.parcs-ht-tariffs').forEach(scanTariffs);
        root.querySelectorAll('[data-htp-guides]').forEach(scanGuides);
    }

    scan(document);
    new MutationObserver(function () { scan(document); }).observe(document.documentElement, { childList: true, subtree: true });
}());
