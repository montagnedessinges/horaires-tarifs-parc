'use strict';

global.window={ParcsHTPData:{settings:{timezone:'Europe/Paris',activeSeasonYear:'2026',general:{accent_color:'#ef7b5b',last_entry_minutes:'45'},seasons:{'2026':{
  season_start:'2026-03-01',season_end:'2026-11-30',schoolHolidays:[],specialPeriods:[],publicHolidays:[],
  regularPeriods:[
    {enabled:'1',start:'2026-03-01',end:'2026-09-30',weekdays:['1','2','3','4','5','6','7'],open:'10:00',close:'18:00'},
    {enabled:'1',start:'2026-10-01',end:'2026-10-23',weekdays:['1','2','3','4','5','6','7'],open:'13:00',close:'17:00'},
    {enabled:'1',start:'2026-10-24',end:'2026-11-01',weekdays:['1','2','3','4','5','6','7'],open:'10:00',close:'12:00',open2:'14:00',close2:'18:00'},
    {enabled:'1',start:'2026-11-02',end:'2026-11-30',weekdays:['1','2','3','4','5','6','7'],open:'10:00',close:'17:00'}
  ],
  exceptions:[{enabled:'1',type:'closed',start:'2026-11-02',end:'2026-11-06',priority:'30'}],
  domainRules:[{enabled:'1',start:'2026-03-01',end:'2026-11-30',weekdays:['1','2','3','4','5','6','7'],exclude_weekends:'0',exclude_school_holidays:'0',exclude_public_holidays:'0'}]
}}},dictionary:{fr:{monthHours:'Horaires du mois : {hours}',closed:'Fermé'}}}};
global.document={readyState:'loading',addEventListener:function(){},querySelectorAll:function(){return[];},body:{classList:{add:function(){},remove:function(){}}}};
require('../assets/frontend.js');

function assert(condition,message){if(!condition){throw new Error(message);}}
assert(window.ParcsHTP.resolveDay('2026-06-15').open,'un jour régulier doit être ouvert');
assert(!window.ParcsHTP.resolveDay('2026-11-03').open,'une fermeture exceptionnelle doit fermer la journée');
assert(window.ParcsHTP.domainRule('2026-11-03')===null,'aucun accès limité ne doit apparaître un jour fermé');
assert(!window.ParcsHTP.resolveDay('2026-12-15').open,'un jour hors saison doit être fermé');
var split=window.ParcsHTP.resolveDay('2026-10-27');
assert(split.open&&split.slots.length===2,'le 27 octobre doit conserver ses deux créneaux');
assert(window.ParcsHTP.dayRanges(split,'fr')==='10 h–12 h / 14 h–18 h','les deux créneaux doivent être affichés intégralement');
assert(window.ParcsHTP.lastEntryTime(split)==='17:15','la dernière entrée doit être calculée depuis la fermeture du dernier créneau');
var october=window.ParcsHTP.monthSummary('2026-10','fr');
assert(october.indexOf('13 h–17 h')!==-1&&october.indexOf('10 h–12 h / 14 h–18 h')!==-1,'le résumé mensuel doit lister tous les horaires distincts');
assert(window.ParcsHTP.dayAria('2026-10-27',split,'fr').indexOf('10 h–12 h / 14 h–18 h')!==-1,'le libellé accessible doit annoncer les deux créneaux');
process.stdout.write('Tests métier JavaScript réussis.\n');
