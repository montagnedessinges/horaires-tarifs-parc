(function(){
  'use strict';

  var api=window.ParcsHTP||{},payload=window.ParcsHTPData||{},settings=payload.settings||{},timezone=settings.timezone||'Europe/Paris';
  if(!api.resolveAnyDay)return;

  function pad(v){return String(v).length<2?'0'+v:String(v);}
  function mins(v){if(!v)return 0;var p=String(v).split(':');return Number(p[0])*60+Number(p[1]);}
  function nowLocal(){var p=new Intl.DateTimeFormat('fr-CA',{timeZone:timezone,year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',hourCycle:'h23'}).formatToParts(new Date()),o={};p.forEach(function(x){o[x.type]=x.value;});return{date:o.year+'-'+o.month+'-'+o.day,minutes:Number(o.hour)*60+Number(o.minute)};}
  function slots(status){return status&&status.slots&&status.slots.length?status.slots:(status&&status.openTime&&status.closeTime?[{open:status.openTime,close:status.closeTime}]:[]);}
  function headerTime(v,lang){if(!v)return'';var p=String(v).split(':').map(Number),h=p[0],m=p[1];if(lang==='fr')return h+'h'+(m?pad(m):'');if(lang==='de')return h+(m?':'+pad(m):'')+' Uhr';var s=h>=12?'PM':'AM';return(h%12||12)+(m?':'+pad(m):'')+' '+s;}
  function addDays(date,n){var p=date.split('-').map(Number),d=new Date(Date.UTC(p[0],p[1]-1,p[2],12));d.setUTCDate(d.getUTCDate()+n);return d.getUTCFullYear()+'-'+pad(d.getUTCMonth()+1)+'-'+pad(d.getUTCDate());}
  function labels(lang){return lang==='en'?{open:'OPEN',before:'Opens at ',gap:'Reopens at ',closed:'Closed for today',tomorrow:'See you tomorrow!',next:'Next opening'}:lang==='de'?{open:'GEÖFFNET',before:'Öffnung um ',gap:'Wieder geöffnet um ',closed:'Für heute geschlossen',tomorrow:'Bis morgen!',next:'Nächste Öffnung'}:{open:'OUVERT',before:'Ouverture à ',gap:'Réouverture à ',closed:'Fermé pour aujourd’hui',tomorrow:'À demain !',next:'Prochaine ouverture'};}
  function phase(status,current){var list=slots(status),i;if(!status||!status.open||!list.length)return{type:'closed',index:-1};for(i=0;i<list.length;i++){if(current>=mins(list[i].open)&&current<mins(list[i].close))return{type:'open',index:i};if(current<mins(list[i].open))return{type:i===0?'before':'gap',index:i};}return{type:'after',index:-1};}
  function nextOpening(date){return typeof api.nextOpeningAcrossSeasons==='function'?api.nextOpeningAcrossSeasons(date):null;}
  function statusValue(status,current,date,lang){var l=labels(lang),p=phase(status,current),list=slots(status);if(p.type==='open')return{value:l.open,isOpen:true,phase:p.type};if(p.type==='before')return{value:l.before+headerTime(list[0].open,lang),isOpen:false,phase:p.type};if(p.type==='gap')return{value:l.gap+headerTime(list[p.index].open,lang),isOpen:false,phase:p.type};var next=nextOpening(date);if(next)return{value:next.date===addDays(date,1)?l.tomorrow:l.next,isOpen:false,phase:'after'};return{value:l.closed,isOpen:false,phase:'after'};}
  function applyStateClasses(root,state){root.classList.toggle('is-open',state.isOpen);root.classList.toggle('is-closed',!state.isOpen);root.classList.toggle('is-before-open',state.phase==='before');root.classList.toggle('is-between-slots',state.phase==='gap');root.classList.toggle('is-after-close',state.phase==='after');}
  function apply(root,state){root.textContent=state.value;applyStateClasses(root,state);}
  function isStaticOpenLabel(text){text=String(text||'').trim().toLowerCase();return text==='ouvert'||text==='open'||text==='geöffnet';}
  function syncVisualLabel(hourRoot,state){
    var ancestor=hourRoot.parentElement,depth=0;
    while(ancestor&&depth<6){
      var candidates=ancestor.querySelectorAll('strong,h1,h2,h3,h4,h5,h6,.elementor-heading-title,.elementor-widget-text-editor p,.elementor-widget-text-editor span');
      for(var i=0;i<candidates.length;i++){
        var el=candidates[i];
        if(el===hourRoot||hourRoot.contains(el)||el.closest('[data-htp-component="header-hour"]')===hourRoot)continue;
        if(el.hasAttribute('data-htp-component'))continue;
        if(isStaticOpenLabel(el.textContent)){
          el.textContent=state.value;
          el.setAttribute('data-htp-synced-status','1');
          return;
        }
        if(el.getAttribute('data-htp-synced-status')==='1'){
          el.textContent=state.value;
          return;
        }
      }
      ancestor=ancestor.parentElement;depth++;
    }
  }
  function refresh(){
    var now=nowLocal(),status=api.resolveAnyDay(now.date);
    document.querySelectorAll('[data-htp-component="header-status"]').forEach(function(root){var lang=root.getAttribute('data-htp-lang')||'fr';apply(root,statusValue(status,now.minutes,now.date,lang));});
    document.querySelectorAll('[data-htp-component="header-hour"]').forEach(function(root){var lang=root.getAttribute('data-htp-lang')||'fr',state=statusValue(status,now.minutes,now.date,lang);applyStateClasses(root,state);syncVisualLabel(root,state);});
    document.querySelectorAll('[data-htp-component="home-opening"]').forEach(function(root){var target=root.querySelector('[data-htp-home-status]');if(!target)return;var lang=root.getAttribute('data-htp-lang')||'fr',state=statusValue(status,now.minutes,now.date,lang);target.textContent=state.value;applyStateClasses(root,state);});
  }

  window.ParcsHTPStatusSync={phase:phase,statusValue:statusValue};
  function boot(){refresh();setInterval(refresh,30000);}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',function(){setTimeout(boot,0);});else setTimeout(boot,0);
}());
