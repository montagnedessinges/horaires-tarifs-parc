(function () {
    'use strict';

    function addGuideShortcodesRow() {
        var section = document.getElementById('htp-shortcodes');
        if (!section) return;
        var table = section.querySelector('table tbody');
        if (!table) return;
        if (section.textContent.indexOf('[parc_guides_pedagogiques_fr]') !== -1) return;

        var row = document.createElement('tr');
        row.innerHTML = '<th>Guides pédagogiques</th>' +
            '<td><code>[parc_guides_pedagogiques_fr]</code></td>' +
            '<td><code>[parc_guides_pedagogiques_en]</code></td>' +
            '<td><code>[parc_guides_pedagogiques_de]</code></td>';
        table.appendChild(row);
    }

    function protectGuideVisibilitySave() {
        var form = document.querySelector('form input[name="action"][value="parcs_ht_save_pedagogical_guides"]');
        form = form ? form.form : null;
        if (!form || form.dataset.htpGuideVisibilitySave === '1') return;
        form.dataset.htpGuideVisibilitySave = '1';

        form.addEventListener('submit', function () {
            form.querySelectorAll('input[type="checkbox"][name$="[enabled]"]').forEach(function (checkbox) {
                var previous = checkbox.previousElementSibling;
                if (previous && previous.matches('input[type="hidden"][data-htp-guide-enabled-fallback]') && previous.name === checkbox.name) {
                    return;
                }
                var hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = checkbox.name;
                hidden.value = '0';
                hidden.setAttribute('data-htp-guide-enabled-fallback', '1');
                checkbox.parentNode.insertBefore(hidden, checkbox);
            });
        }, true);
    }

    function init() {
        addGuideShortcodesRow();
        protectGuideVisibilitySave();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
