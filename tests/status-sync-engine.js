'use strict';

global.window={
  ParcsHTP:{
    resolveAnyDay:function(){return null;},
    nextOpeningAcrossSeasons:function(date){
      return {date:'2026-08-28',status:{open:true,openTime:'10:00',closeTime:'18:00',slots:[{open:'10:00',close:'18:00'}]}};
    }
  },
  ParcsHTPData:{settings:{timezone:'Europe/Paris'}}
};
global.document={readyState:'loading',addEventListener:function(){},querySelectorAll:function(){return[];}};

require('../assets/status-sync.js');

function assert(condition,message){if(!condition)throw new Error(message);}
var api=window.ParcsHTPStatusSync;
assert(api&&typeof api.statusValue==='function','le moteur de statut doit être testable');

var exceptional={
  open:true,
  exceptional:true,
  type:'hours',
  openTime:'09:00',
  closeTime:'17:30',
  slots:[{open:'09:00',close:'17:30'}]
};

var before=api.statusValue(exceptional,8*60+30,'2026-08-27','fr');
assert(before.phase==='before'&&!before.isOpen,'avant un horaire exceptionnel le parc ne doit pas être marqué ouvert');
assert(before.value==='Ouverture à 9h','avant ouverture exceptionnelle le libellé doit annoncer 9h');

var during=api.statusValue(exceptional,10*60,'2026-08-27','fr');
assert(during.phase==='open'&&during.isOpen,'pendant un horaire exceptionnel le parc doit être ouvert');
assert(during.value==='OUVERT','pendant le créneau le statut doit être OUVERT');

var after=api.statusValue(exceptional,17*60+42,'2026-08-27','fr');
assert(after.phase==='after'&&!after.isOpen,'après la fermeture exceptionnelle le parc ne doit plus être marqué ouvert');
assert(after.value==='À demain !','après la fermeture le statut doit annoncer le lendemain');

var splitExceptional={
  open:true,
  exceptional:true,
  type:'hours',
  slots:[{open:'10:00',close:'12:00'},{open:'13:00',close:'17:30'}]
};
var gap=api.statusValue(splitExceptional,12*60+30,'2026-08-27','fr');
assert(gap.phase==='gap'&&!gap.isOpen,'entre deux créneaux exceptionnels le parc ne doit pas être marqué ouvert');
assert(gap.value==='Réouverture à 13h','entre deux créneaux le statut doit annoncer la réouverture');

process.stdout.write('Tests de statut dynamique réussis.\n');
