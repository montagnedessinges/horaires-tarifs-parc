(function () {
  'use strict';

  var api = window.ParcsHTP || {};
  var payload = window.ParcsHTPData || {};
  var settings = payload.settings || {};
  var dictionaries = payload.dictionary || {};
  var timezone = settings.timezone || 'Europe/Paris';

  if (!api.resolveDay || !api.resolveAnyDay) return;

  function dict(language) { return dictionaries[language] || dictionaries.fr || {}; }
  function pad(value) { return String(value).length < 2 ? '0' + value : String(value); }
  function minutes(value) { if (!value) return 0; var parts=String(value).split(':'); return Number(parts[0])*60+Number(parts[1]); }
  function fromMinutes(value) { value=((value%1440)+1440)%1440; return pad(Math.floor(value/60))+':'+pad(value%60); }
  function text(template, values) {
    var output=template||'';
    Object.keys(values||{}).forEach(function(key){output=output.replace(new RegExp('\\{'+key+'\\}','g'),values[key]);});
    return output;
  }
  function nowLocal() {
    var parts=new Intl.DateTimeFormat('fr-CA',{timeZone:timezone,year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',hourCycle:'h23'}).formatToParts(new Date());
    var values={};parts.forEach(function(part){values[part.type]=part.value;});
    return {date:values.year+'-'+values.month+'-'+values.day,minutes:Number(values.hour)*60+Number(values.minute)};
  }
  function headerTime(value,language) {
    if(!value)return '';
    var parts=String(value).split(':').map(Number),h=parts[0],m=parts[1];
    if(language==='fr')return h+'h'+(m?pad(m):'');
    if(language==='de')return h+(m?':'+pad(m):'')+' Uhr';
    var suffix=h>=12?'PM':'AM',display=h%12||12;
    return display+(m?':'+pad(m):'')+' '+suffix;
  }
  function longTime(value,language) {
    if(!value)return '';
    var parts=String(value).split(':').map(Number),h=parts[0],m=parts[1];
    if(language==='fr')return h+' h'+(m?' '+pad(m):'');
    return headerTime(value,language);
  }
  function source(status) { return status && (status.period || status.exception) ? (status.period || status.exception) : {}; }
  function slots(status) { return status && status.slots && status.slots.length ? status.slots : (status&&status.openTime&&status.closeTime?[{open:status.openTime,close:status.closeTime}]:[]); }
  function slotOffset(status,index) {
    var row=source(status),key='last_entry_minutes_slot'+String(index+1);
    var value=Object.prototype.hasOwnProperty.call(row,key)?String(row[key] == null ? '' : row[key]):'';
    if(value==='')value=String(row.last_entry_minutes == null ? '' : row.last_entry_minutes);
    if(value==='')value=String((settings.general||{}).last_entry_minutes||0);
    return Number(value||0);
  }
  function slotLastEntry(status,index) {
    var list=slots(status),slot=list[index];
    if(!slot||!slot.close)return '';
    return fromMinutes(minutes(slot.close)-slotOffset(status,index));
  }
  function slotRange(slot,language) {
    if(!slot)return '';
    return language==='fr' ? headerTime(slot.open,language)+'–'+headerTime(slot.close,language) : headerTime(slot.open,language)+' – '+headerTime(slot.close,language);
  }
  function ranges(list,language) { return (list||[]).map(function(slot){return slotRange(slot,language);}).join(' / '); }
  function allRanges(status,language) { return ranges(slots(status),language); }
  function activeIndex(status,nowMinutes) {
    var list=slots(status);for(var i=0;i<list.length;i++)if(nowMinutes>=minutes(list[i].open)&&nowMinutes<minutes(list[i].close))return i;return -1;
  }
  function nextIndex(status,nowMinutes) {
    var list=slots(status);for(var i=0;i<list.length;i++)if(nowMinutes<minutes(list[i].open))return i;return -1;
  }
  function relevantSlots(status,nowMinutes) {
    var list=slots(status);if(!list.length)return [];
    // Avant et pendant le premier créneau : afficher la journée complète.
    if(nowMinutes<minutes(list[0].close))return list.slice();
    // Après le premier créneau : ne conserver que les créneaux encore utiles.
    return list.filter(function(slot){return nowMinutes<minutes(slot.close);});
  }
  function focusIndex(status,nowMinutes) {
    var active=activeIndex(status,nowMinutes);if(active>=0)return active;
    return nextIndex(status,nowMinutes);
  }
  function lastEntryLabel(status,index,language,compact) {
    if(index<0)return '';
    var d=dict(language),value=slotLastEntry(status,index);if(!value)return '';
    return text((compact?(d.lastEntryCompact||d.lastEntry):d.lastEntry)||'Dernière entrée : {time}',{time:compact?headerTime(value,language):longTime(value,language)});
  }
  function allLastEntriesLabel(status,language) {
    var list=slots(status),values=[];
    for(var i=0;i<list.length;i++){var value=slotLastEntry(status,i);if(value)values.push(longTime(value,language));}
    if(!values.length)return '';
    if(values.length===1)return text(dict(language).lastEntry||'Dernière entrée : {time}',{time:values[0]});
    if(language==='en')return 'Last admissions: '+values.join(' · ');
    if(language==='de')return 'Letzte Einlässe: '+values.join(' · ');
    return 'Dernières entrées : '+values.join(' · ');
  }

  function updateToday(root) {
    var language=root.getAttribute('data-htp-lang')||'fr',now=nowLocal(),status=api.resolveDay(now.date);
    if(!status||!status.open)return;
    var detail=root.querySelector('[data-htp-today-detail]');if(!detail)return;
    var visible=relevantSlots(status,now.minutes);if(!visible.length)return;
    var bits=[ranges(visible,language)],focus=focusIndex(status,now.minutes),last=lastEntryLabel(status,focus,language,false);
    if(last)bits.push(last);
    if(status.exceptional&&status.exception&&String(status.exception.show_public_marker)!=='0'){
      var d=dict(language);bits.push(d.exceptionalHours||'Horaires exceptionnels');
      var context=status.exception.context||{},contextText=context[language]||context.fr||'';if(contextText)bits.push(contextText);
    }
    var value=bits.join(' · ');if(detail.textContent!==value)detail.textContent=value;
  }

  function updateHome(root) {
    var language=root.getAttribute('data-htp-lang')||'fr',now=nowLocal(),status=api.resolveAnyDay(now.date);
    if(!status||!status.open)return;
    var hours=root.querySelector('[data-htp-home-hours]'),last=root.querySelector('[data-htp-home-last-entry]');if(!hours||!last)return;
    var visible=relevantSlots(status,now.minutes);if(!visible.length)return;
    var hoursValue=ranges(visible,language);if(hours.textContent!==hoursValue)hours.textContent=hoursValue;
    var focus=focusIndex(status,now.minutes),lastValue=lastEntryLabel(status,focus,language,true);
    if(last.textContent!==lastValue)last.textContent=lastValue;
    last.hidden=!lastValue;
  }

  function updateHeaderHour(root) {
    var language=root.getAttribute('data-htp-lang')||'fr',now=nowLocal(),status=api.resolveAnyDay(now.date);
    if(!status||!status.open)return;
    var visible=relevantSlots(status,now.minutes);if(!visible.length)return;
    var main=root.querySelector('.parcs-ht-header-hour-main'),detail=root.querySelector('.parcs-ht-header-hour-detail');
    if(main){var mainValue=ranges(visible,language);if(main.textContent!==mainValue)main.textContent=mainValue;}
    var focus=focusIndex(status,now.minutes),lastValue=lastEntryLabel(status,focus,language,true);
    if(lastValue){
      if(!detail){detail=document.createElement('span');detail.className='parcs-ht-header-hour-detail';root.appendChild(detail);}
      if(detail.textContent!==lastValue)detail.textContent=lastValue;
    }else if(detail){detail.remove();}
  }

  function selectedDate(root) {
    var selected=root.querySelector('.parcs-ht-day.is-selected[data-htp-date]');
    return selected?selected.getAttribute('data-htp-date'):'';
  }
  function updateDayDetail(root) {
    var date=selectedDate(root);if(!date)return;
    var language=root.getAttribute('data-htp-lang')||'fr',status=api.resolveDay(date);if(!status||!status.open)return;
    var box=root.querySelector('[data-htp-day-detail]');if(!box)return;
    var hours=box.querySelector('.parcs-ht-day-hours'),last=box.querySelector('.parcs-ht-day-last');
    if(hours){var hoursValue=allRanges(status,language);if(hours.textContent!==hoursValue)hours.textContent=hoursValue;}
    var lastValue=allLastEntriesLabel(status,language);
    if(last){if(last.textContent!==lastValue)last.textContent=lastValue;}
    else if(lastValue){last=document.createElement('p');last.className='parcs-ht-day-last';last.textContent=lastValue;box.insertBefore(last,hours?hours.nextSibling:box.firstChild);}
  }

  function monthKey(root) {
    var active=root.querySelector('[data-htp-month][aria-selected="true"]');
    return active?active.getAttribute('data-htp-month'):'';
  }
  function isoDate(year,month,day){return year+'-'+pad(month)+'-'+pad(day);}
  function weekday(date){var p=date.split('-').map(Number),d=new Date(Date.UTC(p[0],p[1]-1,p[2],12));var w=d.getUTCDay();return w===0?7:w;}
  function sameNumbers(a,b){if(a.length!==b.length)return false;for(var i=0;i<a.length;i++)if(a[i]!==b[i])return false;return true;}
  function dateScope(days,year,month,daysCount,language){
    var all=[],weekdays=[],weekends=[];
    for(var d=1;d<=daysCount;d++){all.push(d);var w=weekday(isoDate(year,month,d));if(w>=6)weekends.push(d);else weekdays.push(d);}
    if(sameNumbers(days,all))return language==='en'?'all month':(language==='de'?'ganzer Monat':'tout le mois');
    if(sameNumbers(days,weekends))return language==='en'?'weekends':(language==='de'?'Wochenenden':'week-ends');
    if(sameNumbers(days,weekdays))return language==='en'?'Monday to Friday':(language==='de'?'Montag bis Freitag':'du lundi au vendredi');
    var parts=[],start=days[0],previous=days[0];
    for(var i=1;i<=days.length;i++){
      var current=days[i];
      if(current===previous+1){previous=current;continue;}
      parts.push(start===previous?String(start):String(start)+'–'+String(previous));
      start=current;previous=current;
    }
    return parts.join(', ');
  }
  function monthlySummary(root) {
    var key=monthKey(root);if(!key)return;
    var language=root.getAttribute('data-htp-lang')||'fr',year=Number(key.slice(0,4)),month=Number(key.slice(5,7)),daysCount=new Date(Date.UTC(year,month,0)).getUTCDate();
    var groups=[],lookup={};
    for(var day=1;day<=daysCount;day++){
      var status=api.resolveDay(isoDate(year,month,day));if(!status||!status.open)continue;
      var label=allRanges(status,language);
      if(!Object.prototype.hasOwnProperty.call(lookup,label)){lookup[label]=groups.length;groups.push({label:label,days:[]});}
      groups[lookup[label]].days.push(day);
    }
    var host=root.querySelector('[data-htp-month-summary]');if(!host)return;
    if(!groups.length){var closed=dict(language).closed||'Fermé';if(host.textContent!==closed)host.textContent=closed;return;}
    var entries=groups.map(function(group){return group.label+' ('+dateScope(group.days,year,month,daysCount,language)+')';});
    var d=dict(language),value=text(d.monthHours||'Horaires du mois : {hours}',{hours:entries.join(' · ')});
    if(host.textContent!==value)host.textContent=value;
  }

  function updateCalendar(root){monthlySummary(root);updateDayDetail(root);}
  function refreshAll(){
    document.querySelectorAll('[data-htp-component="today"]').forEach(updateToday);
    document.querySelectorAll('[data-htp-component="home-opening"]').forEach(updateHome);
    document.querySelectorAll('[data-htp-component="header-hour"]').forEach(updateHeaderHour);
    document.querySelectorAll('[data-htp-component="calendar"]').forEach(updateCalendar);
  }

  function boot(){
    refreshAll();
    document.querySelectorAll('[data-htp-component="calendar"]').forEach(function(root){
      var queued=false;
      var observer=new MutationObserver(function(){
        if(queued)return;queued=true;
        requestAnimationFrame(function(){queued=false;updateCalendar(root);});
      });
      observer.observe(root,{childList:true,subtree:true,attributes:true,attributeFilter:['aria-selected','class']});
      root.addEventListener('click',function(){setTimeout(function(){updateCalendar(root);},0);});
    });
  }

  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',function(){setTimeout(boot,0);});
  else setTimeout(boot,0);
}());
