(function(){
  'use strict';

  var payload=window.ParcsHTPData||{},settings=payload.settings||{},dicts=payload.dictionary||{},engine=window.ParcsHTP||{};
  if(!engine.resolveDay||!engine.resolveAnyDay)return;
  var timezone=settings.timezone||'Europe/Paris';

  function d(lang){return dicts[lang]||dicts.fr||{};}
  function pad(v){return String(v).length<2?'0'+v:String(v);}
  function mins(t){if(!t)return 0;var p=String(t).split(':').map(Number);return p[0]*60+p[1];}
  function fromMins(v){v=((v%1440)+1440)%1440;return pad(Math.floor(v/60))+':'+pad(v%60);}
  function now(){var p=new Intl.DateTimeFormat('fr-CA',{timeZone:timezone,year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',hourCycle:'h23'}).formatToParts(new Date()),o={};p.forEach(function(x){o[x.type]=x.value;});return{date:o.year+'-'+o.month+'-'+o.day,minutes:Number(o.hour)*60+Number(o.minute)};}
  function labelTime(value,lang){if(!value)return'';var p=String(value).split(':').map(Number),h=p[0],m=p[1];if(lang==='en'){var s=h>=12?'PM':'AM';return(h%12||12)+(m?':'+pad(m):'')+' '+s;}if(lang==='de')return h+(m?':'+pad(m):'')+' Uhr';return h+'h'+(m?pad(m):'');}
  function slots(status){return status&&status.slots&&status.slots.length?status.slots:[{open:status.openTime,close:status.closeTime}];}
  function source(status){return(status&&status.exception)||(status&&status.period)||{};}
  function offset(status,index){var row=source(status),value='';if(index===1)value=String(row.last_entry_minutes2||'');if(value===''&&index===1)value=String(row.last_entry_minutes||'');if(value===''&&index===0)value=String(row.last_entry_minutes||'');if(value==='')value=String(((settings.general||{}).last_entry_minutes)||'0');return Number(value||0);}
  function lastFor(status,index){var list=slots(status),slot=list[index];if(!slot||!slot.close)return'';return fromMins(mins(slot.close)-offset(status,index));}
  function range(slot,lang){return labelTime(slot.open,lang)+(lang==='fr'?'–':' – ')+labelTime(slot.close,lang);}
  function ranges(list,lang){return list.map(function(s){return range(s,lang);}).join(' / ');}
  function indexNow(status,current){var list=slots(status);for(var i=0;i<list.length;i++)if(current>=mins(list[i].open)&&current<mins(list[i].close))return i;return-1;}
  function nextIndex(status,current){var list=slots(status);for(var i=0;i<list.length;i++)if(current<mins(list[i].open))return i;return-1;}
  function adaptive(status,current){var list=slots(status),active=indexNow(status,current),upcoming=nextIndex(status,current);if(active===0)return{list:list,index:0};if(active>0)return{list:list.slice(active),index:active};if(upcoming===0)return{list:list,index:0};if(upcoming>0)return{list:list.slice(upcoming),index:upcoming};return{list:[],index:-1};}
  function lastLabel(status,index,lang,compact){var t=lastFor(status,index);if(!t)return'';var dic=d(lang);var tpl=compact?(dic.lastEntryCompact||dic.lastEntry):(dic.lastEntry||dic.lastEntryCompact);return String(tpl||'').replace('{time}',labelTime(t,lang));}
  function translated(v,lang){if(!v||typeof v!=='object')return typeof v==='string'?v:'';return v[lang]||v.fr||'';}

  function patchHome(root){var lang=root.getAttribute('data-htp-lang')||'fr',n=now(),status=engine.resolveAnyDay(n.date);if(!status||!status.open)return;var view=adaptive(status,n.minutes);if(!view.list.length)return;var hours=root.querySelector('[data-htp-home-hours]'),last=root.querySelector('[data-htp-home-last-entry]');if(hours)hours.textContent=ranges(view.list,lang);if(last){last.textContent=lastLabel(status,view.index,lang,true);last.hidden=!last.textContent;}}

  function patchHeader(root){var lang=root.getAttribute('data-htp-lang')||'fr',n=now(),status=engine.resolveAnyDay(n.date);if(!status||!status.open)return;var view=adaptive(status,n.minutes);if(!view.list.length)return;var main=root.querySelector('.parcs-ht-header-hour-main'),detail=root.querySelector('.parcs-ht-header-hour-detail');if(main)main.textContent=ranges(view.list,lang);if(detail){detail.textContent=lastLabel(status,view.index,lang,true);detail.hidden=!detail.textContent;}}

  function patchToday(root){var lang=root.getAttribute('data-htp-lang')||'fr',n=now(),status=engine.resolveDay(n.date),detail=root.querySelector('[data-htp-today-detail]');if(!detail||!status||!status.open)return;var view=adaptive(status,n.minutes);if(!view.list.length)return;var bits=[ranges(view.list,lang),lastLabel(status,view.index,lang,false)];if(status.exceptional&&status.exception&&String(status.exception.show_public_marker)!=='0'){bits.push(d(lang).exceptionalHours||'');var context=translated(status.exception.context,lang);if(context)bits.push(context);}detail.textContent=bits.filter(Boolean).join(' · ');}

  function allLastEntries(status,lang){var list=slots(status),times=[];for(var i=0;i<list.length;i++)times.push(labelTime(lastFor(status,i),lang));times=times.filter(Boolean);if(!times.length)return'';if(times.length===1)return lastLabel(status,0,lang,false);if(lang==='en')return'Last admissions: '+times.join(' / ');if(lang==='de')return'Letzte Einlässe: '+times.join(' / ');return'Dernières entrées : '+times.join(' / ');}
  function patchDayDetail(root,date){var lang=root.getAttribute('data-htp-lang')||'fr',status=engine.resolveDay(date),last=root.querySelector('[data-htp-day-detail] .parcs-ht-day-last');if(last&&status&&status.open)last.textContent=allLastEntries(status,lang);}

  function monthDays(year,month){return new Date(Date.UTC(year,month,0)).getUTCDate();}
  function iso(y,m,day){return y+'-'+pad(m)+'-'+pad(day);}
  function compactRuns(days){if(!days.length)return'';var out=[],start=days[0],prev=days[0];for(var i=1;i<=days.length;i++){var cur=days[i];if(cur===prev+1){prev=cur;continue;}out.push(start===prev?String(start):start+'–'+prev);start=cur;prev=cur;}return out.join(', ');}
  function detailedMonth(monthKey,lang){var y=Number(monthKey.slice(0,4)),m=Number(monthKey.slice(5,7)),groups={},order=[];for(var day=1;day<=monthDays(y,m);day++){var status=engine.resolveDay(iso(y,m,day));if(!status||!status.open)continue;var key=ranges(slots(status),lang);if(!groups[key]){groups[key]=[];order.push(key);}groups[key].push(day);}if(!order.length)return d(lang).closed||'';var parts=order.map(function(key){var dates=compactRuns(groups[key]);if(lang==='en')return dates+': '+key;if(lang==='de')return dates+': '+key;return dates+' : '+key;});var title=lang==='en'?'Opening hours this month: ':lang==='de'?'Öffnungszeiten in diesem Monat: ':'Horaires du mois : ';return title+parts.join(' · ');}
  function selectedMonth(root){var b=root.querySelector('[data-htp-month][aria-selected="true"]');return b?b.getAttribute('data-htp-month'):'';}
  function patchMonth(root){var key=selectedMonth(root),host=root.querySelector('[data-htp-month-summary]');if(!key||!host)return;var lang=root.getAttribute('data-htp-lang')||'fr',value=detailedMonth(key,lang);if(host.textContent!==value)host.textContent=value;}

  function patchCalendar(root){patchMonth(root);var selected=root.querySelector('[data-htp-date].is-selected');if(selected)patchDayDetail(root,selected.getAttribute('data-htp-date'));
    root.addEventListener('click',function(e){var day=e.target.closest('[data-htp-date]'),month=e.target.closest('[data-htp-month]');if(day)setTimeout(function(){patchDayDetail(root,day.getAttribute('data-htp-date'));},0);if(month)setTimeout(function(){patchMonth(root);var s=root.querySelector('[data-htp-date].is-selected');if(s)patchDayDetail(root,s.getAttribute('data-htp-date'));},0);});
    var host=root.querySelector('[data-htp-month-summary]');if(host)new MutationObserver(function(){setTimeout(function(){patchMonth(root);},0);}).observe(host,{childList:true,characterData:true,subtree:true});
  }

  function boot(){document.querySelectorAll('[data-htp-component="home-opening"]').forEach(patchHome);document.querySelectorAll('[data-htp-component="header-hour"]').forEach(patchHeader);document.querySelectorAll('[data-htp-component="today"]').forEach(patchToday);document.querySelectorAll('[data-htp-component="calendar"]').forEach(patchCalendar);}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
}());
