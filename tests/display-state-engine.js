const fs=require('fs'),vm=require('vm');
const code=fs.readFileSync('assets/display-state.js','utf8');
const status={open:true,exceptional:false,type:'regular',slots:[{open:'10:00',close:'12:00'},{open:'13:00',close:'17:30'}],period:{last_entry_minutes:'45',last_entry_minutes_slot1:'30',last_entry_minutes_slot2:'30'}};
let nextOpening=null;
const sandbox={window:{ParcsHTPData:{settings:{general:{last_entry_minutes:'45'}}},ParcsHTP:{resolveAnyDay:()=>status,resolveDay:()=>status,nextOpeningAcrossSeasons:()=>nextOpening}},console,Intl,Date};
vm.createContext(sandbox);vm.runInContext(code,sandbox);
const api=sandbox.window.ParcsHTPDisplayState;
function assert(cond,msg){if(!cond)throw new Error(msg);}
assert(api.lastEntry(status,0)==='11:30','créneau 1 doit utiliser last_entry_minutes_slot1=30');
assert(api.lastEntry(status,1)==='17:00','créneau 2 doit utiliser last_entry_minutes_slot2=30');
let s=api.state('2026-09-02','10:00','fr',true);assert(s.statusText==='OUVERT','10h doit être ouvert');assert(s.lastEntryText.indexOf('11h30')!==-1,'aperçu/public doit afficher 11h30');
s=api.state('2026-09-02','12:30','fr',true);assert(s.phase.type==='gap','12h30 doit être entre les créneaux');assert(s.statusText.indexOf('13h')!==-1,'12h30 doit annoncer la réouverture à 13h');
s=api.state('2026-09-02','14:00','fr',true);assert(s.lastEntryText.indexOf('17h')!==-1,'créneau 2 doit afficher 17h00');
nextOpening={date:'2026-09-03',status:{open:true,openTime:'10:00',closeTime:'17:30',slots:[{open:'10:00',close:'17:30'}]}};
s=api.state('2026-09-02','20:09','fr',true);assert(s.statusText==='À demain !','après fermeture, demain doit être annoncé');assert(s.hoursText==='Ouverture à 10h','demain ne doit jamais afficher une date ISO brute');assert(s.hoursText.indexOf('2026-')===-1,'aucune date ISO ne doit être visible pour demain');
nextOpening={date:'2026-09-05',status:{open:true,openTime:'10:00',closeTime:'17:30',slots:[{open:'10:00',close:'17:30'}]}};
s=api.state('2026-09-02','20:09','fr',true);assert(s.statusText==='Prochaine ouverture','une ouverture plus éloignée doit utiliser le libellé générique');assert(s.hoursText.indexOf('5 septembre')!==-1,'une date éloignée doit être lisible en français');assert(s.hoursText.indexOf('2026-09-05')===-1,'une date ISO brute ne doit pas être affichée publiquement');
console.log('display-state-engine: OK');
