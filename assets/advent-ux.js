(function () {
    'use strict';

    var data = window.ParcsHTAdventUX || {};
    var campaigns = data.campaigns || {};

    function addStyles() {
        if (document.getElementById('parcs-ht-advent-ux-css')) return;
        var style = document.createElement('style');
        style.id = 'parcs-ht-advent-ux-css';
        style.textContent = [
            '.parcs-ht-advent-participation-intro{margin:0 0 14px}',
            '.parcs-ht-advent-participation-layout{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-top:14px}',
            '.parcs-ht-advent-participation-card{min-width:0;padding:16px;border:1px solid color-mix(in srgb,var(--htp-advent-secondary,currentColor) 22%,transparent);border-radius:12px;background:var(--htp-advent-secondary-bg,transparent)}',
            '.parcs-ht-advent-participation-card.is-mystery{border-color:color-mix(in srgb,var(--htp-advent-special,currentColor) 28%,transparent);background:var(--htp-advent-special-bg,transparent)}',
            '.parcs-ht-advent-participation-card h3{margin:0 0 9px;font-size:1.08em}',
            '.parcs-ht-advent-participation-card p{margin:0 0 10px}',
            '.parcs-ht-advent-participation-card ul{margin:8px 0 0;padding-left:20px}',
            '.parcs-ht-advent-participation-card li+li{margin-top:5px}',
            '.parcs-ht-advent-social-actions{display:flex;flex-wrap:wrap;gap:9px;margin-top:14px}',
            '.parcs-ht-advent-social-button{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:8px 14px;border-radius:999px;background:var(--htp-advent-primary,currentColor);color:#fff!important;font-weight:750;text-decoration:none!important}',
            '.parcs-ht-advent-social-button[hidden]{display:none!important}',
            '.parcs-ht-advent-participation-rules{margin:14px 0 0;text-align:center}',
            '.parcs-ht-advent-partner-link{font-weight:750;text-decoration:underline;text-underline-offset:2px}',
            '.parcs-ht-advent-partner-more{display:inline-block;margin-left:8px;font-size:.92em}',
            '@media(max-width:720px){.parcs-ht-advent-participation-layout{grid-template-columns:1fr}.parcs-ht-advent-social-actions{flex-direction:column}.parcs-ht-advent-social-button{width:100%}}'
        ].join('');
        document.head.appendChild(style);
    }

    function language(root) {
        var lang = String(root.getAttribute('data-language') || '').toLowerCase();
        return lang === 'en' || lang === 'de' ? lang : 'fr';
    }

    function labels(lang) {
        if (lang === 'en') return {
            daily: 'Daily game', mystery: 'Christmas Mystery', facebook: 'Enter on Facebook', instagram: 'Enter on Instagram',
            dailyFallback: 'Open the new day, find the prize and answer the question on Facebook or Instagram.',
            answer: 'Post the correct answer in a comment.', mention: 'Mention someone you would like to visit with.', follow: 'Follow the park and, when applicable, the partner of the day.',
            mysteryFallback: 'Some visuals hide a letter and a number. The number gives the letter position in the final word.', final: 'Keep your clues: on 24 December, enter the reconstructed word on this page to access the final prize form.',
            partner: 'View partner'
        };
        if (lang === 'de') return {
            daily: 'Tägliches Spiel', mystery: 'Weihnachtsrätsel', facebook: 'Auf Facebook teilnehmen', instagram: 'Auf Instagram teilnehmen',
            dailyFallback: 'Öffnen Sie das neue Türchen, entdecken Sie den Gewinn und beantworten Sie die Frage auf Facebook oder Instagram.',
            answer: 'Schreiben Sie die richtige Antwort in einen Kommentar.', mention: 'Erwähnen Sie eine Person, mit der Sie den Park besuchen möchten.', follow: 'Folgen Sie dem Park und gegebenenfalls dem Partner des Tages.',
            mysteryFallback: 'In einigen Motiven sind ein Buchstabe und eine Zahl versteckt. Die Zahl gibt die Position des Buchstabens im Lösungswort an.', final: 'Bewahren Sie Ihre Hinweise auf: Am 24. Dezember können Sie das rekonstruierte Wort auf dieser Seite eingeben und das Formular für den Hauptgewinn öffnen.',
            partner: 'Partner ansehen'
        };
        return {
            daily: 'Jeu quotidien', mystery: 'Mystère de Noël', facebook: 'Participer sur Facebook', instagram: 'Participer sur Instagram',
            dailyFallback: 'Ouvrez chaque jour la nouvelle case, découvrez le lot et répondez à la question sur Facebook ou Instagram.',
            answer: 'Donnez la bonne réponse en commentaire.', mention: 'Mentionnez une personne avec qui vous aimeriez venir.', follow: 'Suivez le compte du parc et, lorsqu’il y en a un, celui du partenaire du jour.',
            mysteryFallback: 'Certains visuels cachent une lettre accompagnée d’un chiffre. Le chiffre indique la position de la lettre dans le mot final.', final: 'Gardez bien vos indices : le 24 décembre, entrez le mot reconstitué sur cette page pour accéder au formulaire du grand lot.',
            partner: 'Voir le partenaire'
        };
    }

    function element(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (typeof text === 'string' && text !== '') node.textContent = text;
        return node;
    }

    function selectedContentId(root) {
        if (root.dataset.adventUxContentId) return root.dataset.adventUxContentId;
        var today = root.querySelector('[data-advent-day].is-today:not([disabled])');
        return today ? String(today.getAttribute('data-content-id') || '') : '';
    }

    function socialUrls(root, campaign) {
        var contentId = selectedContentId(root);
        var content = contentId && campaign.contents ? campaign.contents[contentId] : null;
        return {
            facebook: content && content.facebookUrl ? content.facebookUrl : (campaign.facebookUrl || ''),
            instagram: content && content.instagramUrl ? content.instagramUrl : (campaign.instagramUrl || '')
        };
    }

    function updateSocialButtons(root, campaign) {
        var urls = socialUrls(root, campaign);
        var facebook = root.querySelector('[data-advent-ux-facebook]');
        var instagram = root.querySelector('[data-advent-ux-instagram]');
        if (facebook) {
            facebook.hidden = !urls.facebook;
            if (urls.facebook) facebook.href = urls.facebook;
        }
        if (instagram) {
            instagram.hidden = !urls.instagram;
            if (urls.instagram) instagram.href = urls.instagram;
        }
    }

    function enhanceParticipation(root, campaign) {
        var panel = root.querySelector('[data-advent-participation-panel]');
        if (!panel || panel.dataset.adventUxReady === '1') return;
        panel.dataset.adventUxReady = '1';

        var lang = language(root);
        var text = labels(lang);
        var existingRules = panel.querySelector('a.parcs-ht-advent-link');
        var rulesLabel = existingRules ? existingRules.textContent.trim() : (campaign.rulesLabel || '');
        var rulesUrl = existingRules ? existingRules.getAttribute('href') : (campaign.rulesUrl || '');

        panel.innerHTML = '';

        if (campaign.participateText) {
            panel.appendChild(element('p', 'parcs-ht-advent-participation-intro', campaign.participateText));
        }

        var layout = element('div', 'parcs-ht-advent-participation-layout');
        var daily = element('section', 'parcs-ht-advent-participation-card');
        daily.appendChild(element('h3', '', text.daily));
        daily.appendChild(element('p', '', campaign.dailyText || text.dailyFallback));
        var list = element('ul');
        [text.answer, text.mention, text.follow].forEach(function (item) {
            list.appendChild(element('li', '', item));
        });
        daily.appendChild(list);

        var actions = element('div', 'parcs-ht-advent-social-actions');
        var facebook = element('a', 'parcs-ht-advent-social-button', text.facebook);
        facebook.setAttribute('data-advent-ux-facebook', '');
        facebook.target = '_blank';
        facebook.rel = 'noopener noreferrer';
        actions.appendChild(facebook);
        var instagram = element('a', 'parcs-ht-advent-social-button', text.instagram);
        instagram.setAttribute('data-advent-ux-instagram', '');
        instagram.target = '_blank';
        instagram.rel = 'noopener noreferrer';
        actions.appendChild(instagram);
        daily.appendChild(actions);

        var mystery = element('section', 'parcs-ht-advent-participation-card is-mystery');
        mystery.appendChild(element('h3', '', text.mystery));
        mystery.appendChild(element('p', '', campaign.mysteryText || text.mysteryFallback));
        mystery.appendChild(element('p', '', text.final));

        layout.appendChild(daily);
        layout.appendChild(mystery);
        panel.appendChild(layout);

        if (rulesUrl && rulesLabel) {
            var rules = element('p', 'parcs-ht-advent-participation-rules');
            var link = element('a', 'parcs-ht-advent-link', rulesLabel);
            link.href = rulesUrl;
            rules.appendChild(link);
            panel.appendChild(rules);
        }

        updateSocialButtons(root, campaign);
    }

    function enhancePartner(root, campaign) {
        var contentId = selectedContentId(root);
        if (!contentId || !campaign.contents || !campaign.contents[contentId]) return;
        var content = campaign.contents[contentId];
        if (!content.partnerUrl || !content.partnerName) return;
        var box = root.querySelector('.parcs-ht-advent-partner');
        if (!box || box.querySelector('.parcs-ht-advent-partner-link')) return;

        var replaced = false;
        Array.prototype.slice.call(box.childNodes).forEach(function (node) {
            if (replaced || node.nodeType !== 3) return;
            if (String(node.textContent || '').trim() !== String(content.partnerName).trim()) return;
            var spacer = document.createTextNode(' ');
            var link = element('a', 'parcs-ht-advent-partner-link', content.partnerName);
            link.href = content.partnerUrl;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            node.parentNode.insertBefore(spacer, node);
            node.parentNode.insertBefore(link, node);
            node.parentNode.removeChild(node);
            replaced = true;
        });

        if (!replaced) {
            var lang = language(root);
            var more = element('a', 'parcs-ht-advent-partner-link parcs-ht-advent-partner-more', labels(lang).partner);
            more.href = content.partnerUrl;
            more.target = '_blank';
            more.rel = 'noopener noreferrer';
            box.appendChild(more);
        }
    }

    function enhanceRoot(root) {
        if (!root || root.dataset.adventUxRoot === '1') return;
        var campaignId = String(root.getAttribute('data-campaign-id') || '');
        var campaign = campaigns[campaignId];
        if (!campaign) return;
        root.dataset.adventUxRoot = '1';

        enhanceParticipation(root, campaign);

        root.addEventListener('click', function (event) {
            var button = event.target.closest('[data-advent-day]');
            if (!button || button.disabled) return;
            root.dataset.adventUxContentId = String(button.getAttribute('data-content-id') || '');
            updateSocialButtons(root, campaign);
        }, true);

        var detail = root.querySelector('[data-advent-detail]');
        if (detail) {
            var observer = new MutationObserver(function () {
                enhancePartner(root, campaign);
            });
            observer.observe(detail, {childList:true, subtree:true});
            enhancePartner(root, campaign);
        }
    }

    function boot() {
        addStyles();
        document.querySelectorAll('[data-advent-root]').forEach(enhanceRoot);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
}());
