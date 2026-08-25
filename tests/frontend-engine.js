'use strict';

global.window={ParcsHTPData:{settings:{timezone:'Europe/Paris',activeSeasonYear:'2026',general:{accent_color:'#ef7b5b'},seasons:{'2026':{
  season_start:'2026-03-01',season_end:'2026-11-30',schoolHolidays:[],specialPeriods:[],publicHolidays:[],
  regularPeriods:[{enabled:'1',start:'2026-03-01',end:'2026-11-30',weekdays:['1','2','3','4','5','6','7'],open:'10:00',close:'18:00'}],
  exceptions:[{enabled:'1',type:'closed',start:'2026-11-02',end:'2026-11-06',priority:'30'}],
  domainRules:[{enabled:'1',start:'2026-03-01',end:'2026-11-30',weekdays:['1','2','3','4','5','6','7'],exclude_weekends:'0',exclude_school_holidays:'0',exclude_public_holidays:'0'}]
}}},dictionary:{fr:{}}}};
global.document={readyState:'loading',addEventListener:function(){},querySelectorAll:function(){return[];},body:{classList:{add:function(){},remove:function(){}}}};
require('../assets/frontend.js');

function assert(condition,message){if(!condition){throw new Error(message);}}
assert(window.ParcsHTP.resolveDay('2026-06-15').open,'un jour régulier doit être ouvert');
assert(!window.ParcsHTP.resolveDay('2026-11-03').open,'une fermeture exceptionnelle doit fermer la journée');
assert(window.ParcsHTP.domainRule('2026-11-03')===null,'aucun accès limité ne doit apparaître un jour fermé');
assert(!window.ParcsHTP.resolveDay('2026-12-15').open,'un jour hors saison doit être fermé');
process.stdout.write('Tests métier JavaScript réussis.\n');
