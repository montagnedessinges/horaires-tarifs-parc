(function () {
    'use strict';
    var root = document.querySelector('.htp-faq-admin');
    if (!root) return;
    root.querySelectorAll('[data-faq-copy]').forEach(function (button) {
        button.addEventListener('click', function () {
            var field = document.getElementById(button.getAttribute('data-faq-copy'));
            var status = root.querySelector('[data-faq-copy-status]');
            if (!field || !status) return;
            function fallback() {
                field.focus();
                field.select();
                status.textContent = 'Texte sélectionné : utilisez Ctrl+C ou Cmd+C pour le copier.';
            }
            if (!navigator.clipboard || !navigator.clipboard.writeText) { fallback(); return; }
            navigator.clipboard.writeText(field.value).then(function () {
                status.textContent = 'Copié dans le presse-papiers.';
            }, fallback);
        });
    });
}());
