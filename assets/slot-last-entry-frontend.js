(function(){
  'use strict';

  var api=window.ParcsHTP||{},payload=window.ParcsHTPData||{},settings=payload.settings||{},dicts=payload.dictionary||{},timezone=settings.timezone||'Europe/Paris';
  if(!api.resolveDay||!api.resolveAnyDay)return;

  function dict(lang){return dicts[lang]||dicts.fr||{};}
  function pad(v){return String(v).length<2?'0'+v:String(v);}
  function mins(v){if(!v)return 0;var p=String(v).split(':');return Number(p[0])*60+Number(p[1]);}
  function fromMins(v){v=((v%1440)+1440)%1440;return pad(Math.floor(v/60))+':'+pad(v%60);}
  function headerTime(v,lang){if(!v)return'';var p=String(v).split(':').map(Number),h=p[0],m=p[1];if(lang==='fr')return h+'h'+(m?pad(m):'');if(lang==='de')return h+(m?':'+pad(m):'')+' Uhr';var s=h>=12?'PM':'AM';return(h%12||12)+(m?':'+pad(m):'')+' '+s;}
  function longTime(v,lang){if(!v)return'';var p=String(v).split(':').map(Number);if(lang==='fr')return p[0]+' h'+(p[1]?' '+pad(p[1]):'');return headerTime(v,lang);}
  function text(tpl,values){var out=tpl||'';Object.keys(values||{}).forEach(function(k){out=out.replace(new RegExp('\\{'+k+'\\}','g'),values[k]);});return out;}
  function slots(status){return status&&status.slots&&status.slots.length?status.slots:(status&&status.openTime&&status.closeTime?[{open:status.openTime,close:status.closeTime}]:[]);}
  function source(status){return status&&(status.exception||status.period)?(status.exception||status.period):{};}
  function slotOffset(status,index){var row=source(status),key='last_entry_minutes_slot'+String(index+1),value=Object.prototype.hasOwnProperty.call(row,key)?String(row[key]==null?'':row[key]):'';if(value==='')value=String(row.last_entry_minutes==null?'':row.last_entry_minutes);if(value==='')value=String((settings.general||{}).last_entry_minutes||0);return Number(value||0);}
  function slotLast(status,index){var list=slots(status),slot=list[index];return slot&&slot.close?fromMins(mins(slot.close)-slotOffset(status,index)):'';}
  function slotRange(slot,lang){return headerTime(slot.open,lang)+(lang==='fr'?'–':' – ')+headerTime(slot.close,lang);}
  function ranges(list,lang){return(list||[]).map(function(s){return slotRange(s,lang);}).join(' / ');}
  function lastLabel(status,index,lang,compact){if(index<0)return'';var value=slotLast(status,index);if(!value)return'';var d=dict(lang),tpl=compact?(d.lastEntryCompact||d.lastEntry):(d.lastEntry||d.lastEntryCompact);return text(tpl||'Dernière entrée : {time}',{time:compact?headerTime(value,lang):longTime(value,lang)});}
  function allLastLabel(status,lang){var list=slots(status),values=[];for(var i=0;i<list.length;i++){var v=slotLast(status,i);if(v)values.push(longTime(v,lang));}if(!values.length)return'';if(values.length===1)return lastLabel(status,0,lang,false);if(lang==='en')return'Last admissions: '+values.join(' · ');if(lang==='de')return'Letzte Einlässe: '+values.join(' · ');return'Dernières entrées : '+values.join(' · ');}
  function setText(node,value){if(node&&node.textContent!==value)node.textContent=value;}
  function selectedDate(root){var s=root.querySelector('.parcs-ht-day.is-selected[data-htp-date]');return s?s.getAttribute('data-htp-date'):'';}
  function updateDayDetail(root){var date=selectedDate(root);if(!date)return;var lang=root.getAttribute('data-htp-lang')||'fr',status=api.resolveDay(date);if(!status||!status.open)return;var box=root.querySelector('[data-htp-day-detail]');if(!box)return;var hours=box.querySelector('.parcs-ht-day-hours'),last=box.querySelector('.parcs-ht-day-last');setText(hours,ranges(slots(status),lang));var value=allLastLabel(status,lang);if(last)setText(last,value);else if(value){last=document.createElement('p');last.className='parcs-ht-day-last';last.textContent=value;box.insertBefore(last,hours?hours.nextSibling:box.firstChild);}}
  function monthKey(root){var a=root.querySelector('[data-htp-month][aria-selected="true"]');return a?a.getAttribute('data-htp-month'):'';}
  function iso(y,m,d){return y+'-'+pad(m)+'-'+pad(d);}
  function weekday(date){var p=date.split('-').map(Number),d=new Date(Date.UTC(p[0],p[1]-1,p[2],12)),w=d.getUTCDay();return w===0?7:w;}
  function same(a,b){if(a.length!==b.length)return false;for(var i=0;i<a.length;i++)if(a[i]!==b[i])return false;return true;}
  function scope(days,y,m,count,lang){var all=[],weekdays=[],weekends=[];for(var d=1;d<=count;d++){all.push(d);(weekday(iso(y,m,d))>=6?weekends:weekdays).push(d);}if(same(days,all))return lang==='en'?'all month':lang==='de'?'ganzer Monat':'tout le mois';if(same(days,weekends))return lang==='en'?'weekends':lang==='de'?'Wochenenden':'week-ends';if(same(days,weekdays))return lang==='en'?'Monday to Friday':lang==='de'?'Montag bis Freitag':'du lundi au vendredi';var out=[],start=days[0],prev=days[0];for(var i=1;i<=days.length;i++){var cur=days[i];if(cur===prev+1){prev=cur;continue;}out.push(start===prev?String(start):start+'–'+prev);start=cur;prev=cur;}return out.join(', ');}
  function updateMonth(root){var key=monthKey(root);if(!key)return;var lang=root.getAttribute('data-htp-lang')||'fr',y=Number(key.slice(0,4)),m=Number(key.slice(5,7)),count=new Date(Date.UTC(y,m,0)).getUTCDate(),groups=[],lookup={};for(var day=1;day<=count;day++){var status=api.resolveDay(iso(y,m,day));if(!status||!status.open)continue;var label=ranges(slots(status),lang);if(!Object.prototype.hasOwnProperty.call(lookup,label)){lookup[label]=groups.length;groups.push({label:label,days:[]});}groups[lookup[label]].days.push(day);}var host=root.querySelector('[data-htp-month-summary]');if(!host)return;if(!groups.length){setText(host,dict(lang).closed||'Fermé');return;}var entries=groups.map(function(g){return g.label+' ('+scope(g.days,y,m,count,lang)+')';});setText(host,text(dict(lang).monthHours||'Horaires du mois : {hours}',{hours:entries.join(' · ')}));}
  function updateCalendar(root){updateMonth(root);updateDayDetail(root);}
  function refresh(){document.querySelectorAll('[data-htp-component="calendar"]').forEach(updateCalendar);}
  function boot(){
    refresh();
    document.querySelectorAll('[data-htp-component="calendar"]').forEach(function(root){
      var queued=false,options={childList:true,subtree:true,attributes:true,attributeFilter:['aria-selected','class']};
      var observer=new MutationObserver(function(){
        if(queued)return;
        queued=true;
        requestAnimationFrame(function(){
          queued=false;
          // Nos propres écritures ne doivent pas relancer une observation du même calendrier.
          observer.disconnect();
          try{updateCalendar(root);}finally{observer.observe(root,options);}
        });
      });
      observer.observe(root,options);
    });
    setInterval(refresh,60000);
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',function(){setTimeout(boot,0);});else setTimeout(boot,0);
}());
