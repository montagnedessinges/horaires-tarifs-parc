(function(){
  'use strict';
  var api=window.ParcsHTP||{},display=window.ParcsHTPDisplayState||{},payload=window.ParcsHTPData||{},settings=payload.settings||{},timezone=settings.timezone||'Europe/Paris';
  if(!api.resolveAnyDay||!display.state)return;

  function nowLocal(){
    var p=new Intl.DateTimeFormat('fr-CA',{timeZone:timezone,year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',hourCycle:'h23'}).formatToParts(new Date()),o={};
    p.forEach(function(x){o[x.type]=x.value;});
    return{date:o.year+'-'+o.month+'-'+o.day,time:o.hour+':'+o.minute};
  }

  function applyStateClasses(root,state){
    root.classList.toggle('is-open',!!state.isOpen);
    root.classList.toggle('is-closed',!state.isOpen);
    root.classList.toggle('is-before-open',state.phase.type==='before');
    root.classList.toggle('is-between-slots',state.phase.type==='gap');
    root.classList.toggle('is-after-close',state.phase.type==='after');
  }

  function refresh(){
    var now=nowLocal(),langs={};
    document.querySelectorAll('[data-htp-component]').forEach(function(root){
      var lang=root.getAttribute('data-htp-lang')||'fr';
      if(!langs[lang])langs[lang]=display.state(now.date,now.time,lang,true);
      var state=langs[lang];
      if(!state)return;
      var component=root.getAttribute('data-htp-component');

      if(component==='header-status'){
        root.textContent=state.statusText;
        applyStateClasses(root,state);
        return;
      }

      if(component==='header-hour'){
        var primary=state.hoursText||state.statusText,secondary=state.lastEntryText||'';
        var main=root.querySelector('.parcs-ht-header-hour-main'),entry=root.querySelector('.parcs-ht-header-hour-detail');
        if(!main){root.textContent='';main=document.createElement('span');main.className='parcs-ht-header-hour-main';root.appendChild(main);}
        main.textContent=primary;
        if(secondary&&!entry){entry=document.createElement('span');entry.className='parcs-ht-header-hour-detail';root.appendChild(entry);}
        if(entry){entry.textContent=secondary;entry.hidden=!secondary;}
        applyStateClasses(root,state);
        return;
      }

      if(component==='home-opening'){
        var statusEl=root.querySelector('[data-htp-home-status]'),hoursEl=root.querySelector('[data-htp-home-hours]'),lastEl=root.querySelector('[data-htp-home-last-entry]');
        if(statusEl)statusEl.textContent=state.statusText;
        if(hoursEl)hoursEl.textContent=state.hoursText||'';
        if(lastEl){lastEl.textContent=state.lastEntryText||'';lastEl.hidden=!state.lastEntryText;}
        applyStateClasses(root,state);
        return;
      }

      if(component==='today'){
        var statusTarget=root.querySelector('[data-htp-today-status]'),detail=root.querySelector('[data-htp-today-detail]');
        if(statusTarget){statusTarget.textContent=state.statusText;applyStateClasses(statusTarget,state);}
        if(detail){
          var parts=[];
          if(state.hoursText)parts.push(state.hoursText);
          if(state.lastEntryText)parts.push(state.lastEntryText);
          if(state.exceptionText)parts.push(state.exceptionText);
          detail.textContent=parts.join(' · ');
        }
        applyStateClasses(root,state);
      }
    });
  }

  window.ParcsHTPStatusSync={refresh:refresh,state:function(date,time,lang){return display.state(date,time,lang||'fr',true);}};
  function boot(){refresh();setInterval(refresh,30000);}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',function(){setTimeout(boot,0);});else setTimeout(boot,0);
}());
