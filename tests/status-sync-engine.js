'use strict';

var currentStatus=null;
global.window={
  ParcsHTP:{
    resolveAnyDay:function(){return currentStatus;},
    resolveDay:function(){return currentStatus;},
    nextOpeningAcrossSeasons:function(){return {date:'2026-08-28',status:{open:true,openTime:'10:00',closeTime:'18:00',slots:[{open:'10:00',close:'18:00'}]}};}
  },
  ParcsHTPData:{settings:{timezone:'Europe/Paris',general:{last_entry_minutes:'45'}}}
};
global.document={readyState:'loading',addEventListener:function(){},querySelectorAll:function(){return[];}};

require('../assets/display-state.js');
require('../assets/status-sync.js');

function assert(condition,message){if(!condition)throw new Error(message);}
var display=window.ParcsHTPDisplayState;
var api=window.ParcsHTPStatusSync;
assert(display&&typeof display.state==='function','le moteur partagé doit être testable');
assert(api&&typeof api.state==='function','la synchronisation publique doit exposer le moteur partagé');

var exceptional={open:true,exceptional:true,type:'hours',openTime:'09:00',closeTime:'17:30',slots:[{open:'09:00',close:'17:30'}],exception:{last_entry_minutes_slot1:'30'}};
currentStatus=exceptional;
var before=api.state('2026-08-27','08:30','fr');
assert(before.phase.type==='before'&&!before.isOpen,'avant un horaire exceptionnel le parc ne doit pas être marqué ouvert');
assert(before.statusText==='Ouverture à 9h','avant ouverture exceptionnelle le libellé doit annoncer 9h');
var during=api.state('2026-08-27','10:00','fr');
assert(during.phase.type==='open'&&during.isOpen,'pendant un horaire exceptionnel le parc doit être ouvert');
assert(during.statusText==='OUVERT','pendant le créneau le statut doit être OUVERT');
var after=api.state('2026-08-27','17:42','fr');
assert(after.phase.type==='after'&&!after.isOpen,'après la fermeture exceptionnelle le parc ne doit plus être marqué ouvert');
assert(after.statusText==='À demain !','après la fermeture le statut doit annoncer le lendemain');

var splitExceptional={open:true,exceptional:true,type:'hours',slots:[{open:'10:00',close:'12:00'},{open:'13:00',close:'17:30'}],exception:{last_entry_minutes_slot1:'30',last_entry_minutes_slot2:'30'}};
currentStatus=splitExceptional;
var gap=api.state('2026-08-27','12:30','fr');
assert(gap.phase.type==='gap'&&!gap.isOpen,'entre deux créneaux exceptionnels le parc ne doit pas être marqué ouvert');
assert(gap.statusText==='Réouverture à 13h','entre deux créneaux le statut doit annoncer la réouverture');

process.stdout.write('Tests de statut dynamique réussis.\n');
