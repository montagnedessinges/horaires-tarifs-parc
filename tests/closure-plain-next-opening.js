'use strict';

const assert = require('assert').strict;
const { JSDOM } = require('jsdom');
const dom = new JSDOM('<!doctype html><html><body><section data-htp-component="calendar" data-htp-lang="fr">' +
  '<div data-htp-year-list></div><span data-htp-calendar-kicker></span><span data-htp-calendar-title></span>' +
  '<span data-htp-legend-hours></span><span data-htp-legend-closed></span><span data-htp-legend-event></span>' +
  '<div data-htp-month-list></div><button data-htp-prev></button><button data-htp-next></button>' +
  '<div data-htp-calendar-grid></div><div data-htp-month-summary></div>' +
  '<div data-htp-period-legends></div><div data-htp-day-detail></div>' +
  '</section></body></html>', { url: 'https://example.test/' });

global.window = dom.window;
global.document = dom.window.document;
global.requestAnimationFrame = function(callback) { callback(); };
dom.window.Element.prototype.scrollIntoView = function() {};

const daily = ['1', '2', '3', '4', '5', '6', '7'];
const regular = function(start, end, open, close) {
  return [{ enabled:'1', start:start, end:end, weekdays:daily, open:open, close:close }];
};
window.ParcsHTPData = {
  settings: {
    timezone:'Europe/Paris', activeSeasonYear:'2026', general:{},
    seasons: {
      '2026': {
        season_start:'2026-03-21', season_end:'2026-11-11',
        regularPeriods:regular('2026-03-21','2026-11-11','10:00','17:00'),
        schoolHolidays:[],specialPeriods:[],publicHolidays:[],domainRules:[],
        exceptions:[
          {enabled:'1',type:'closed',start:'2026-11-09',end:'2026-11-09',priority:'100',show_public_marker:'1'},
          {enabled:'1',type:'closed',closure_display:'plain',start:'2026-11-11',end:'2026-11-11',priority:'100',show_public_marker:'1'}
        ]
      },
      '2027': {
        season_start:'2027-03-20',season_end:'2027-10-31',
        regularPeriods:regular('2027-03-20','2027-10-31','09:30','17:00'),
        schoolHolidays:[],specialPeriods:[],publicHolidays:[],domainRules:[],exceptions:[]
      }
    }
  },
  dictionary: {fr:{
    today:'Aujourd’hui',calendar:'Calendrier',closed:'Fermé',closedToday:'Fermé aujourd’hui',
    exceptionalHours:'Horaires exceptionnels',exceptionalClosure:'Fermeture exceptionnelle',
    nextOpening:'Prochaine ouverture : {date} à {time}',selectDate:'Choisissez une date.',
    monthHours:'Horaires du mois : {hours}',lastEntry:'Dernière entrée à {time}'
  }}
};
require('../assets/frontend.js');
require('../assets/display-state.js');
const api = window.ParcsHTP;
const display = window.ParcsHTPDisplayState;

assert.equal(api.resolveAnyDay('2026-11-11').open,false,'Fermé reste fermé dans le moteur');
assert.equal(api.resolveAnyDay('2026-11-11').type,'closed','Fermé utilise le type métier closed');
assert.equal(api.resolveDay('2026-11-09').open,false,'Fermeture exceptionnelle reste fermée');
assert.equal(api.resolveAnyDay('2026-11-10').open,true,'jour sans exception reste ouvert');
assert.equal(api.nextOpeningAcrossSeasons('2026-11-11').date,'2027-03-20','recherche à travers les saisons');
assert.equal(api.nextOpeningAcrossSeasons('2026-11-12').date,'2027-03-20','hors saison: prochaine ouverture 2027');
assert(api.dayAria('2026-11-11',api.resolveDay('2026-11-11'),'fr').includes('Fermé'),'libellé lecteur écran simple');
assert(!api.dayAria('2026-11-11',api.resolveDay('2026-11-11'),'fr').includes('Fermeture exceptionnelle'),'pas de libellé exceptionnel sur Fermé');
assert(api.dayAria('2026-11-09',api.resolveDay('2026-11-09'),'fr').includes('Fermeture exceptionnelle'),'ancien libellé conservé');

const plainState = display.state('2026-11-11','12:00','fr');
assert.equal(plainState.statusText,'Fermé','statut public neutre');
assert(plainState.hoursText.includes('samedi 20 mars 2027'),'année et jour présents pour la prochaine ouverture');
assert(!plainState.hoursText.includes('exceptionnelle'),'aucune mention exceptionnelle');
assert.equal(plainState.exceptionText,'','pas de doublon exceptionnel');
assert.equal(display.state('2026-11-09','12:00','fr').statusText,'Fermeture exceptionnelle','ancien statut conservé');

document.dispatchEvent(new dom.window.Event('DOMContentLoaded'));
const root = document.querySelector('[data-htp-component="calendar"]');
root._htpSelectMonth('2026-11');
const plainDay = root.querySelector('[data-htp-date="2026-11-11"]');
assert(plainDay.querySelector('.parcs-ht-symbol.is-closed'),'la croix reste sur Fermé');
plainDay.click();
let detail = root.querySelector('[data-htp-day-detail]').textContent;
assert(detail.includes('Fermé'),'le détail indique Fermé');
assert(!detail.includes('Fermeture exceptionnelle'),'le détail n’affiche pas fermeture exceptionnelle');
assert(detail.includes('samedi 20 mars 2027'),'le détail trouve la saison suivante avec année et jour');
assert(detail.includes('9 h 30'),'l’heure de la prochaine ouverture est présente');

root.querySelector('[data-htp-date="2026-11-09"]').click();
detail = root.querySelector('[data-htp-day-detail]').textContent;
assert(detail.includes('Fermeture exceptionnelle'),'le détail conserve l’ancien mode');
assert(root.querySelector('[data-htp-date="2026-11-09"] .parcs-ht-symbol.is-closed'),'croix exceptionnelle conservée');
assert.equal(root.querySelector('[data-htp-legend-closed]').textContent,'Fermé','légende des croix neutre');

root.querySelector('[data-htp-date="2026-11-12"]').click();
detail = root.querySelector('[data-htp-day-detail]').textContent;
assert(detail.includes('samedi 20 mars 2027'),'une journée naturellement fermée annonce aussi la prochaine ouverture');

const fs = require('fs');
const path = require('path');
const project = process.env.PLUGIN_ROOT || path.join(__dirname,'..');
const php = fs.readFileSync(path.join(project,'includes/class-parcs-ht-admin.php'),'utf8');
const panel = fs.readFileSync(path.join(project,'includes/class-parcs-ht-admin-periods.php'),'utf8');
assert(php.includes("array('closed','closed_plain')"),'le moteur de sauvegarde garde une fermeture réelle');
assert(php.includes("'closure_display'=>$closure_display"),'le mode public est persisté');
assert(panel.includes("'closed_plain'=>'Fermé'"),'le nouveau choix existe dans le panneau principal');
console.log('OK: fermetures ordinaires, anciennes exceptions, réouverture intersaisons et affichage calendrier.');
