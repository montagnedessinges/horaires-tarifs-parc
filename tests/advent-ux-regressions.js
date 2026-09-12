const fs = require('fs');
const path = require('path');
const { JSDOM } = require('jsdom');

const root = process.env.PLUGIN_ROOT || path.resolve(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'assets', 'advent-ux.js'), 'utf8');

function assert(condition, message) {
    if (!condition) {
        console.error('FAIL:', message);
        process.exit(1);
    }
    console.log('OK:', message);
}

function createDom(language, introText) {
    const dom = new JSDOM(`<!doctype html><html><head></head><body>
        <div class="parcs-ht-advent" data-advent-root data-campaign-id="2026" data-language="${language}">
            <div data-advent-participation-panel>
                <p class="original-intro">${introText}</p>
                <p><a class="parcs-ht-advent-link" href="https://example.test/reglement">Règlement</a></p>
            </div>
            <div class="parcs-ht-advent-grid">
                <button type="button" data-advent-day data-content-id="jour-01" class="is-today">1</button>
                <button type="button" data-advent-day data-content-id="jour-02">2</button>
            </div>
            <div data-advent-detail>
                <p class="parcs-ht-advent-partner"><strong>Partenaire</strong> Partenaire Test</p>
            </div>
        </div>
    </body></html>`, {
        url: 'https://example.test/calendrier',
        runScripts: 'outside-only',
        pretendToBeVisual: true,
    });

    dom.window.ParcsHTAdventUX = {
        campaigns: {
            '2026': {
                participateText: 'Texte français campagne',
                dailyText: 'Texte quotidien français',
                mysteryText: 'Texte Mystère français',
                rulesUrl: 'https://example.test/reglement',
                rulesLabel: 'Règlement',
                facebookUrl: 'https://facebook.com/parc',
                instagramUrl: 'https://instagram.com/parc/',
                contents: {
                    'jour-01': {
                        day: 1,
                        facebookUrl: 'https://facebook.com/post-jour-1',
                        instagramUrl: '',
                        partnerName: 'Partenaire Test',
                        partnerUrl: 'https://instagram.com/partenaire-test/',
                    },
                    'jour-02': {
                        day: 2,
                        facebookUrl: '',
                        instagramUrl: 'https://instagram.com/post-jour-2/',
                        partnerName: '',
                        partnerUrl: '',
                    },
                },
            },
        },
    };

    dom.window.eval(source);
    dom.window.document.dispatchEvent(new dom.window.Event('DOMContentLoaded', { bubbles: true }));
    return dom;
}

(function testFrenchDailyLinksAndPartner() {
    const dom = createDom('fr', 'Introduction française conservée');
    const document = dom.window.document;
    const calendar = document.querySelector('[data-advent-root]');

    assert(document.querySelectorAll('.parcs-ht-advent-participation-card').length === 2, 'two participation cards are rendered');
    assert(document.querySelector('.parcs-ht-advent-participation-card h3').textContent === 'Jeu quotidien', 'daily card uses validated French title');
    assert(document.querySelector('.parcs-ht-advent-participation-card.is-mystery h3').textContent === 'Mystère de Noël', 'mystery card uses validated public name');
    assert(document.querySelector('.original-intro').textContent === 'Introduction française conservée', 'existing translated intro is preserved');

    const facebook = document.querySelector('[data-advent-ux-facebook]');
    const instagram = document.querySelector('[data-advent-ux-instagram]');
    assert(facebook.href === 'https://facebook.com/post-jour-1', 'Facebook button prefers exact current-day post');
    assert(instagram.href === 'https://instagram.com/parc/', 'Instagram button falls back to park account when day URL is empty');

    const partner = document.querySelector('.parcs-ht-advent-partner-link');
    assert(partner && partner.href === 'https://instagram.com/partenaire-test/', 'partner name becomes clickable with configured destination');
    assert(partner.target === '_blank', 'partner link opens in a new tab');

    const day2 = calendar.querySelector('[data-content-id="jour-02"]');
    day2.dispatchEvent(new dom.window.MouseEvent('click', { bubbles: true }));
    assert(calendar.dataset.adventUxContentId === 'jour-02', 'selected day is recorded before social links are refreshed; actual=' + String(calendar.dataset.adventUxContentId || ''));
    assert(facebook.href === 'https://facebook.com/parc/', 'Facebook button falls back to park account for selected day without exact post; actual=' + facebook.href);
    assert(instagram.href === 'https://instagram.com/post-jour-2/', 'Instagram button follows exact selected-day post when available; actual=' + instagram.href);
}());

(function testGermanDoesNotReceiveFrenchOverrides() {
    const dom = createDom('de', 'Übersetzte Einführung bleibt erhalten');
    const document = dom.window.document;
    const cards = document.querySelectorAll('.parcs-ht-advent-participation-card');

    assert(document.querySelector('.original-intro').textContent === 'Übersetzte Einführung bleibt erhalten', 'German core intro remains intact');
    assert(cards[0].querySelector('h3').textContent === 'Tägliches Spiel', 'German daily title is used');
    assert(cards[0].querySelector('p').textContent !== 'Texte quotidien français', 'French daily override does not leak into German page');
    assert(cards[1].querySelector('p').textContent !== 'Texte Mystère français', 'French mystery override does not leak into German page');
    assert(document.querySelector('[data-advent-ux-facebook]').textContent === 'Auf Facebook teilnehmen', 'German Facebook action label is used');
}());

console.log('Advent UX regressions: OK');
