(function () {
    'use strict';

    function normalize(value) {
        var text = String(value || '').toLowerCase();
        if (text.normalize) text = text.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        return text.replace(/\s+/g, ' ').trim();
    }

    function init(root) {
        if (!root || root.dataset.htpFaqReady === '1') return;
        root.dataset.htpFaqReady = '1';

        var input = root.querySelector('[data-htp-faq-search]');
        var categoryButtons = Array.prototype.slice.call(root.querySelectorAll('[data-htp-faq-category]'));
        var items = Array.prototype.slice.call(root.querySelectorAll('[data-htp-faq-item]'));
        var groups = Array.prototype.slice.call(root.querySelectorAll('[data-htp-faq-group]'));
        var secondarySections = Array.prototype.slice.call(root.querySelectorAll('[data-htp-faq-secondary]'));
        var empty = root.querySelector('[data-htp-faq-empty]');
        var contact = root.nextElementSibling && root.nextElementSibling.matches('[data-htp-faq-contact]') ? root.nextElementSibling : null;
        var activeCategory = '';

        function refresh() {
            var query = input ? normalize(input.value) : '';
            var visibleCount = 0;

            items.forEach(function (item) {
                var category = String(item.getAttribute('data-category') || '');
                var haystack = normalize(item.getAttribute('data-search') || '');
                var matchesCategory = !activeCategory || category === activeCategory;
                var matchesSearch = !query || haystack.indexOf(query) !== -1;
                var visible = matchesCategory && matchesSearch;
                item.hidden = !visible;
                if (visible) visibleCount += 1;
            });

            groups.forEach(function (group) {
                var hasVisible = !!group.querySelector('[data-htp-faq-item]:not([hidden])');
                group.hidden = !hasVisible;
            });

            secondarySections.forEach(function (section) {
                var hasVisible = !!section.querySelector('[data-htp-faq-item]:not([hidden])');
                section.hidden = activeCategory !== '' || (query !== '' && !hasVisible);
                if (query !== '' && hasVisible) section.open = true;
            });

            if (empty) empty.hidden = visibleCount !== 0;
            if (contact) contact.classList.toggle('is-search-fallback', query !== '' && visibleCount === 0);
        }

        categoryButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                activeCategory = String(button.getAttribute('data-htp-faq-category') || '');
                categoryButtons.forEach(function (candidate) {
                    var active = candidate === button;
                    candidate.classList.toggle('is-active', active);
                    candidate.setAttribute('aria-pressed', active ? 'true' : 'false');
                });
                refresh();
            });
        });

        if (input) input.addEventListener('input', refresh);
        refresh();
    }

    function initContact(section) {
        if (!section || section.dataset.htpFaqContactReady === '1') return;
        section.dataset.htpFaqContactReady = '1';
        var button = section.querySelector('[data-htp-faq-contact-toggle]');
        var form = section.querySelector('[data-htp-faq-contact-form]');
        if (!button || !form) return;

        button.addEventListener('click', function () {
            var open = button.getAttribute('aria-expanded') === 'true';
            var nextOpen = !open;
            button.setAttribute('aria-expanded', nextOpen ? 'true' : 'false');
            form.hidden = !nextOpen;
            button.textContent = nextOpen ? String(button.getAttribute('data-close-label') || '') : String(button.getAttribute('data-open-label') || '');
            if (nextOpen) {
                var firstField = form.querySelector('input:not([type="hidden"]), select, textarea, button');
                if (firstField && typeof firstField.focus === 'function') firstField.focus();
            }
        });
    }

    function boot() {
        document.querySelectorAll('[data-htp-faq]').forEach(init);
        document.querySelectorAll('[data-htp-faq-contact]').forEach(initContact);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
}());
