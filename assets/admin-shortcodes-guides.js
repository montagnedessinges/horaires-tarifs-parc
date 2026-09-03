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

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', addGuideShortcodesRow);
    } else {
        addGuideShortcodesRow();
    }
}());
