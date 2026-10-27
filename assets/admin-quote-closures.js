(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        var panel = document.querySelector('[data-htp-closed-quote-policy]');
        if (!panel) return;
        var rows = panel.querySelector('[data-htp-closed-quote-rows]');
        var template = panel.querySelector('[data-htp-closed-quote-template]');
        var add = panel.querySelector('[data-htp-add-closed-range]');
        var title = panel.querySelector('[data-htp-closed-exception-title]');
        var explanation = panel.querySelector('[data-htp-closed-exception-info]');
        var nextIndex = rows.querySelectorAll('[data-htp-closed-range-row]').length;
        function refresh() {
            var checked = panel.querySelector('input[name="allow_closed_group_quotes"]:checked');
            var allow = !checked || checked.value === '1';
            title.textContent = allow ? 'Exceptions : dates ou périodes sans devis' : 'Exceptions : dates ou périodes où les devis sont autorisés';
            explanation.textContent = allow ?
                'Les devis restent autorisés les jours fermés, sauf aux dates ci-dessous. Pour une seule journée, laissez « Au » vide.' :
                'Les devis sont refusés les jours fermés, sauf aux dates ci-dessous. Pour une seule journée, laissez « Au » vide.';
        }
        panel.querySelectorAll('input[name="allow_closed_group_quotes"]').forEach(function (input) {
            input.addEventListener('change', refresh);
        });
        add.addEventListener('click', function () {
            if (rows.querySelectorAll('[data-htp-closed-range-row]').length >= 100) return;
            var fragment = template.content.cloneNode(true);
            fragment.querySelectorAll('[name]').forEach(function (input) {
                input.name = input.name.replace('__INDEX__', String(nextIndex));
            });
            nextIndex++;
            rows.appendChild(fragment);
            var latest = rows.lastElementChild;
            if (latest) latest.querySelector('input[type="date"]').focus();
        });
        rows.addEventListener('click', function (event) {
            if (!event.target.closest('[data-htp-remove-closed-range]')) return;
            var row = event.target.closest('[data-htp-closed-range-row]');
            if (row) row.remove();
        });
        refresh();
    });
}());
