'use strict';
const fs = require('fs');
const path = require('path');
const assert = require('node:assert/strict');
const { JSDOM } = require('jsdom');
const jquery = require('jquery');
const root = process.env.PLUGIN_ROOT || path.join(__dirname,'..');
const read = name => fs.readFileSync(path.join(root,'assets',name),'utf8');

async function testQuoteGate() {
    const dom = new JSDOM('<section class="parcs-ht-quote" data-htp-lang="fr"><div class="parcs-ht-quote-form-wrap">' +
        '<div class="parcs-ht-quote-form"><div class="wpcf7"><form><input type="date" name="visite">' +
        '<input name="groupedevis" value="Groupe"><button type="submit">Générer</button></form></div></div></div></section>',
        { runScripts:'outside-only' });
    const w = dom.window;
    const $ = jquery(w);
    w.jQuery = $;
    w.ParcsHTQuoteGate = {
        ajaxUrl:'/wp-admin/admin-ajax.php',nonce:'demo',visitField:'visite',groupField:'groupedevis',
        closedEnabled:true,closedMessage:'Avertissement fermeture',closedContact:'',
        blockedMessage:'Le parc est fermé et nous n’accueillons pas de groupes à cette date.',
        unavailableEnabled:true,unavailableMessage:'Tarifs indisponibles',unavailableContact:''
    };
    const log=[];
    $.post=function(url,payload){
        log.push({url,payload});
        const d=$.Deferred();
        const date=payload.date;
        if(date==='2026-12-11')d.resolve({success:true,data:{allowed:false,closed:true,tariffs:true}});
        else if(date==='2026-12-12')d.resolve({success:true,data:{allowed:true,closed:true,tariffs:true}});
        else if(date==='2026-12-10')d.resolve({success:true,data:{allowed:true,closed:false,tariffs:true}});
        else d.resolve({success:true,data:{allowed:true,closed:true,tariffs:false}});
        return d.promise();
    };
    w.eval(read('quote-gate.js'));
    await new Promise(resolve=>setTimeout(resolve,25));
    const quote=$('.parcs-ht-quote');
    const input=$('.parcs-ht-quote-access-date');
    assert.equal(input.length,1,'date selector is present');
    assert(!quote.hasClass('parcs-ht-gate-open'),'form hidden until a date is selected');

    input.val('2026-12-11').trigger('change');
    assert(!quote.hasClass('parcs-ht-gate-open'),'closed and denied date must hide form');
    assert.equal($('.parcs-ht-quote-gate-message').attr('data-kind'),'blocked');
    assert.match($('.parcs-ht-quote-gate-message').text(),/n’accueillons pas de groupes/);

    input.val('2026-12-10').trigger('change');
    assert(quote.hasClass('parcs-ht-gate-open'),'open afternoon date must enable form');
    assert.equal($('.parcs-ht-quote-gate-message').text(),'','unnecessary warning is cleared');

    input.val('2026-12-12').trigger('change');
    assert(quote.hasClass('parcs-ht-gate-open'),'allowed closed date must enable form');
    assert.match($('.parcs-ht-quote-gate-message').text(),/Avertissement fermeture/);

    input.val('2027-01-01').trigger('change');
    assert(!quote.hasClass('parcs-ht-gate-open'),'unpublished tariffs must still prevent a quote');
    assert.match($('.parcs-ht-quote-gate-message').text(),/Tarifs indisponibles/);
    assert.equal(log.length,4,'one ajax check per selected date');
    w.close();
}

async function testAdmin() {
    const dom = new JSDOM('<div data-htp-closed-quote-policy>' +
        '<label><input type="radio" name="allow_closed_group_quotes" value="1" checked>Oui</label>' +
        '<label><input type="radio" name="allow_closed_group_quotes" value="0">Non</label>' +
        '<h4 data-htp-closed-exception-title></h4><p data-htp-closed-exception-info></p>' +
        '<div data-htp-closed-quote-rows></div><button data-htp-add-closed-range>Ajouter</button>' +
        '<template data-htp-closed-quote-template><div data-htp-closed-range-row>' +
        '<input name="closed_date_exceptions[__INDEX__][start]" type="date">' +
        '<input name="closed_date_exceptions[__INDEX__][end]" type="date">' +
        '<button data-htp-remove-closed-range>Supprimer</button></div></template></div>',
        {runScripts:'outside-only'});
    const w=dom.window;
    w.eval(read('admin-quote-closures.js'));
    w.document.dispatchEvent(new w.Event('DOMContentLoaded'));
    assert.match(w.document.querySelector('[data-htp-closed-exception-title]').textContent,/sans devis/);
    const add=w.document.querySelector('[data-htp-add-closed-range]');
    add.click();
    add.click();
    const rows=w.document.querySelectorAll('[data-htp-closed-range-row]');
    assert.equal(rows.length,2);
    assert.notEqual(rows[0].querySelector('input').name,rows[1].querySelector('input').name,'unique indexes');
    w.document.querySelector('input[value="0"]').checked=true;
    w.document.querySelector('input[value="0"]').dispatchEvent(new w.Event('change'));
    assert.match(w.document.querySelector('[data-htp-closed-exception-title]').textContent,/autorisés/);
    rows[0].querySelector('button').click();
    assert.equal(w.document.querySelectorAll('[data-htp-closed-range-row]').length,1);
    w.close();
}


function testAdminSparseIndices() {
    const dom = new JSDOM('<div data-htp-closed-quote-policy>' +
        '<label><input type="radio" name="allow_closed_group_quotes" value="1" checked>Oui</label>' +
        '<h4 data-htp-closed-exception-title></h4><p data-htp-closed-exception-info></p>' +
        '<div data-htp-closed-quote-rows>' +
        '<div data-htp-closed-range-row><input name="closed_date_exceptions[0][start]" type="date"><button data-htp-remove-closed-range>Supprimer</button></div>' +
        '<div data-htp-closed-range-row><input name="closed_date_exceptions[2][start]" type="date"><button data-htp-remove-closed-range>Supprimer</button></div>' +
        '</div><button data-htp-add-closed-range>Ajouter</button>' +
        '<template data-htp-closed-quote-template><div data-htp-closed-range-row>' +
        '<input name="closed_date_exceptions[__INDEX__][start]" type="date">' +
        '<button data-htp-remove-closed-range>Supprimer</button></div></template></div>',
        { runScripts:'outside-only' });
    const w = dom.window;
    w.eval(read('admin-quote-closures.js'));
    w.document.dispatchEvent(new w.Event('DOMContentLoaded'));
    const button = w.document.querySelector('[data-htp-add-closed-range]');
    button.click();
    let rows = [...w.document.querySelectorAll('[data-htp-closed-range-row]')];
    assert.equal(rows[2].querySelector('input').name,'closed_date_exceptions[3][start]',
        'the next index must exceed existing sparse indexes');
    rows[1].querySelector('button').click();
    button.click();
    rows = [...w.document.querySelectorAll('[data-htp-closed-range-row]')];
    assert.equal(rows[2].querySelector('input').name,'closed_date_exceptions[4][start]',
        'a remove-then-add must not overwrite another interval');
    assert.equal(new Set(rows.map(row=>row.querySelector('input').name)).size, rows.length,
        'all submitted range names must be unique');
    w.close();
}

(async()=>{await testQuoteGate();await testAdmin();testAdminSparseIndices();console.log('OK: sélection bloquée, autorisée, tarif indisponible et périodes administrables.');})()
.catch(error=>{console.error(error);process.exit(1)});
