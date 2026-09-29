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
        var empty = root.querySelector('[data-htp-faq-empty]');
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

            if (empty) empty.hidden = visibleCount !== 0;
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

    function boot() {
        document.querySelectorAll('[data-htp-faq]').forEach(init);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
}());
