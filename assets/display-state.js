(function(){
  'use strict';
  var api=window.ParcsHTP||{},payload=window.ParcsHTPData||{},settings=payload.settings||{};
  function pad(v){return String(v).length<2?'0'+v:String(v);}
  function mins(v){if(!v)return 0;var p=String(v).split(':');return Number(p[0])*60+Number(p[1]);}
  function fromMins(v){v=Math.max(0,Number(v)||0);return pad(Math.floor(v/60))+':'+pad(v%60);}
  function slots(status){return status&&status.slots&&status.slots.length?status.slots:(status&&status.openTime&&status.closeTime?[{open:status.openTime,close:status.closeTime}]:[]);}
  function timeLabel(v,lang){if(!v)return'';var p=String(v).split(':').map(Number),h=p[0],m=p[1];if(lang==='fr')return h+'h'+(m?pad(m):'');if(lang==='de')return h+(m?':'+pad(m):'')+' Uhr';var s=h>=12?'PM':'AM';return(h%12||12)+(m?':'+pad(m):'')+' '+s;}
  function dateLabel(v,lang){
    if(!v)return'';
    var p=String(v).split('-').map(Number),d=new Date(Date.UTC(p[0],p[1]-1,p[2],12));
    if(Number.isNaN(d.getTime()))return String(v);
    var locale=lang==='de'?'de-DE':lang==='en'?'en-GB':'fr-FR';
    return new Intl.DateTimeFormat(locale,{day:'numeric',month:'long',timeZone:'UTC'}).format(d);
  }
  function phase(status,current){var list=slots(status),i;if(!status||!status.open||!list.length)return{type:'closed',index:-1};for(i=0;i<list.length;i++){if(current>=mins(list[i].open)&&current<mins(list[i].close))return{type:'open',index:i};if(current<mins(list[i].open))return{type:i===0?'before':'gap',index:i};}return{type:'after',index:-1};}
  function source(status){return status?(status.exception||status.period||status.source||{}):{};}
  function slotOffset(status,index){var row=source(status),general=(settings.general||{}),value='';
    if(index===0)value=row.last_entry_minutes_slot1;
    else value=row.last_entry_minutes_slot2;
    if(value===''||value===undefined||value===null){
      if(index===1&&row.last_entry_minutes2!==undefined)value=row.last_entry_minutes2;
      else value=row.last_entry_minutes;
    }
    if(value===''||value===undefined||value===null)value=general.last_entry_minutes;
    return Math.max(0,Number(value)||0);
  }
  function lastEntry(status,index){var list=slots(status);if(!list[index])return'';return fromMins(mins(list[index].close)-slotOffset(status,index));}
  function ranges(status,lang){return slots(status).map(function(s){return timeLabel(s.open,lang)+'–'+timeLabel(s.close,lang);}).join(' / ');}
  function remainingRanges(status,current,lang){return slots(status).filter(function(s){return current<mins(s.close);}).map(function(s){return timeLabel(s.open,lang)+'–'+timeLabel(s.close,lang);}).join(' / ');}
  function labels(lang){return lang==='en'?{open:'OPEN',before:'Opens at ',gap:'Reopens at ',closed:'Closed for today',tomorrow:'See you tomorrow!',next:'Next opening',entry:'Last admission: '}:lang==='de'?{open:'GEÖFFNET',before:'Öffnung um ',gap:'Wieder geöffnet um ',closed:'Für heute geschlossen',tomorrow:'Bis morgen!',next:'Nächste Öffnung',entry:'Letzter Einlass: '}:{open:'OUVERT',before:'Ouverture à ',gap:'Réouverture à ',closed:'Fermé pour aujourd’hui',tomorrow:'À demain !',next:'Prochaine ouverture',entry:'Dernière entrée : '};}
  function addDays(date,n){var p=String(date).split('-').map(Number),d=new Date(Date.UTC(p[0],p[1]-1,p[2],12));d.setUTCDate(d.getUTCDate()+n);return d.getUTCFullYear()+'-'+pad(d.getUTCMonth()+1)+'-'+pad(d.getUTCDate());}
  function nextOpening(date){return typeof api.nextOpeningAcrossSeasons==='function'?api.nextOpeningAcrossSeasons(date):null;}
  function state(date,time,lang,useAnyDay){lang=lang||'fr';var status=(useAnyDay!==false&&typeof api.resolveAnyDay==='function')?api.resolveAnyDay(date):(typeof api.resolveDay==='function'?api.resolveDay(date):null);if(!status)return null;var current=mins(time),p=phase(status,current),list=slots(status),l=labels(lang),out={date:date,time:time,status:status,phase:p,statusText:'',hoursText:'',lastEntryText:'',isOpen:false,ruleText:''};
    if(!status.open){out.statusText='FERMÉ';out.hoursText='';}
    else if(p.type==='open'){out.statusText=l.open;out.hoursText=remainingRanges(status,current,lang)||ranges(status,lang);out.isOpen=true;var le=lastEntry(status,p.index);out.lastEntryText=le?l.entry+timeLabel(le,lang):'';}
    else if(p.type==='before'){out.statusText=l.before+timeLabel(list[0].open,lang);out.hoursText=ranges(status,lang);}
    else if(p.type==='gap'){out.statusText=l.gap+timeLabel(list[p.index].open,lang);out.hoursText=remainingRanges(status,current,lang);}
    else{var next=nextOpening(date);if(next){var opening=timeLabel(((next.status||{}).openTime)||((slots(next.status)[0]||{}).open),lang),isTomorrow=next.date===addDays(date,1);out.statusText=isTomorrow?l.tomorrow:l.next;out.hoursText=isTomorrow?l.before+opening:dateLabel(next.date,lang)+(opening?' · '+opening:'');}else out.statusText=l.closed;}
    if(status.exceptional)out.ruleText=status.type==='closed'?'Fermeture exceptionnelle prioritaire':'Horaire exceptionnel prioritaire';else if(status.open)out.ruleText='Horaire classique';else out.ruleText='Aucun horaire applicable';
    return out;
  }
  window.ParcsHTPDisplayState={mins:mins,slots:slots,phase:phase,lastEntry:lastEntry,slotOffset:slotOffset,timeLabel:timeLabel,dateLabel:dateLabel,ranges:ranges,state:state};
}());