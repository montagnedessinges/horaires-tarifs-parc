(function () {
    'use strict';

    var data = window.ParcsHTAdventUXAdmin || {};
    var campaigns = data.campaigns || {};

    function addStyles() {
        if (document.getElementById('parcs-ht-advent-ux-admin-css')) return;
        var style = document.createElement('style');
        style.id = 'parcs-ht-advent-ux-admin-css';
        style.textContent = [
            '.htp-advent-popup-config{grid-column:1/-1;margin-top:6px;padding:14px;border:1px solid rgba(0,0,0,.14);border-radius:10px;background:transparent}',
            '.htp-advent-popup-config>label:first-child{display:flex;align-items:center;gap:9px;font-weight:750}',
            '.htp-advent-popup-config-fields{margin-top:14px}',
            '.htp-advent-popup-config-fields[hidden]{display:none!important}',
            '.htp-advent-popup-config .description{margin:6px 0 0}',
            '.htp-advent-popup-check{display:flex;align-items:center;gap:8px;margin-top:8px}',
            '.htp-advent-popup-config textarea{width:100%}'
        ].join('');
        document.head.appendChild(style);
    }

    function defaultConfig() {
        return {
            enabled: '0',
            title_fr: '',
            message_fr: '',
            start: '',
            end: '',
            show_button: '1',
            button_label_fr: 'Découvrir la case',
            button_url: ''
        };
    }

    function configFor(campaignId, contentId) {
        var campaign = campaigns[campaignId] || {};
        var contents = campaign.contents || {};
        return Object.assign(defaultConfig(), contents[contentId] || {});
    }

    function input(type, name, value) {
        var node = document.createElement('input');
        node.type = type;
        node.name = name;
        node.value = value || '';
        return node;
    }

    function field(labelText, control) {
        var label = document.createElement('label');
        label.className = 'htp-advent-field';
        var span = document.createElement('span');
        span.textContent = labelText;
        label.appendChild(span);
        label.appendChild(control);
        return label;
    }

    function checkbox(name, checked, labelText, marker) {
        var wrap = document.createElement('label');
        wrap.className = marker || 'htp-advent-popup-check';
        var hidden = input('hidden', name, '0');
        var box = input('checkbox', name, '1');
        box.checked = !!checked;
        wrap.appendChild(hidden);
        wrap.appendChild(box);
        wrap.appendChild(document.createTextNode(labelText));
        return {wrap:wrap, box:box};
    }

    function injectForm(form) {
        if (!form || form.dataset.adventPopupReady === '1') return;
        var action = form.querySelector('input[name="action"]');
        if (!action || action.value !== 'parcs_ht_advent_save_content') return;
        var type = form.querySelector('[name="content[type_contenu]"]');
        if (!type || String(type.value).toUpperCase() !== 'JOUR') return;
        var campaignInput = form.querySelector('input[name="campaign_id"]');
        var contentInput = form.querySelector('[name="content[contenu_id]"]');
        if (!campaignInput || !contentInput || !contentInput.value) return;

        form.dataset.adventPopupReady = '1';
        var campaignId = String(campaignInput.value || '');
        var contentId = String(contentInput.value || '');
        var cfg = configFor(campaignId, contentId);

        var section = document.createElement('div');
        section.className = 'htp-advent-popup-config';

        var enable = checkbox('advent_popup[enabled]', cfg.enabled === '1', 'Activer un pop-up pour cette journée');
        section.appendChild(enable.wrap);

        var help = document.createElement('p');
        help.className = 'description';
        help.textContent = 'Le pop-up réutilise le moteur général des alertes de l’extension. Les réglages ci-dessous restent masqués tant qu’il n’est pas activé.';
        section.appendChild(help);

        var fields = document.createElement('div');
        fields.className = 'htp-advent-popup-config-fields htp-advent-grid-fields';
        fields.hidden = !enable.box.checked;

        fields.appendChild(field('Titre du pop-up', input('text', 'advent_popup[title_fr]', cfg.title_fr)));

        var message = document.createElement('textarea');
        message.name = 'advent_popup[message_fr]';
        message.rows = 4;
        message.value = cfg.message_fr || '';
        fields.appendChild(field('Message', message));

        var start = input('datetime-local', 'advent_popup[start]', cfg.start);
        fields.appendChild(field('Début spécifique — facultatif', start));
        var end = input('datetime-local', 'advent_popup[end]', cfg.end);
        fields.appendChild(field('Fin spécifique — facultatif', end));

        var datesHelp = document.createElement('p');
        datesHelp.className = 'description htp-advent-field-wide';
        datesHelp.textContent = 'Si le début reste vide, le pop-up reprend automatiquement la date et l’heure d’ouverture de la journée. Si la fin reste vide, il s’arrête à 23 h 59 le même jour.';
        fields.appendChild(datesHelp);

        var showButton = checkbox('advent_popup[show_button]', cfg.show_button === '1', 'Afficher un bouton dans le pop-up');
        fields.appendChild(showButton.wrap);
        fields.appendChild(field('Texte du bouton', input('text', 'advent_popup[button_label_fr]', cfg.button_label_fr)));
        fields.appendChild(field('URL du bouton — facultatif', input('url', 'advent_popup[button_url]', cfg.button_url)));

        var buttonHelp = document.createElement('p');
        buttonHelp.className = 'description htp-advent-field-wide';
        buttonHelp.textContent = 'Si l’URL reste vide, le bouton renvoie vers la page du Calendrier de l’Avent configurée dans la campagne.';
        fields.appendChild(buttonHelp);

        section.appendChild(fields);
        enable.box.addEventListener('change', function () {
            fields.hidden = !enable.box.checked;
        });

        var anchor = form.querySelector('.htp-advent-copy-grid');
        if (anchor && anchor.parentNode) anchor.parentNode.insertBefore(section, anchor);
        else form.appendChild(section);
    }

    function scan() {
        document.querySelectorAll('form').forEach(injectForm);
    }

    function boot() {
        addStyles();
        scan();
        var observer = new MutationObserver(scan);
        observer.observe(document.body, {childList:true, subtree:true});
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
}());
