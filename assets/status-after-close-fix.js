(function () {
  'use strict';

  var api = window.ParcsHTP || {};
  var payload = window.ParcsHTPData || {};
  var settings = payload.settings || {};
  var dictionaries = payload.dictionary || {};
  var timezone = settings.timezone || 'Europe/Paris';

  if (!api.resolveAnyDay || !api.nextOpeningAcrossSeasons) return;

  function dict(language) { return dictionaries[language] || dictionaries.fr || {}; }
  function pad(value) { return String(value).length < 2 ? '0' + value : String(value); }
  function minutes(value) { if (!value) return 0; var p=String(value).split(':'); return Number(p[0])*60+Number(p[1]); }
  function nowLocal() {
    var parts=new Intl.DateTimeFormat('fr-CA',{timeZone:timezone,year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',hourCycle:'h23'}).formatToParts(new Date());
    var values={};parts.forEach(function(part){values[part.type]=part.value;});
    return {date:values.year+'-'+values.month+'-'+values.day,minutes:Number(values.hour)*60+Number(values.minute)};
  }
  function slots(status) {
    return status && status.slots && status.slots.length ? status.slots : (status && status.openTime && status.closeTime ? [{open:status.openTime,close:status.closeTime}] : []);
  }
  function afterFinalClose(status, currentMinutes) {
    var list=slots(status); if(!list.length) return false;
    return currentMinutes >= minutes(list[list.length-1].close);
  }
  function headerTime(value,language) {
    if(!value)return '';
    var parts=String(value).split(':').map(Number),h=parts[0],m=parts[1];
    if(language==='fr')return h+'h'+(m?pad(m):'');
    if(language==='de')return h+(m?':'+pad(m):'')+' Uhr';
    var suffix=h>=12?'PM':'AM',display=h%12||12;
    return display+(m?':'+pad(m):'')+' '+suffix;
  }
  function addDays(date, amount) {
    var p=date.split('-').map(Number),d=new Date(Date.UTC(p[0],p[1]-1,p[2],12));
    d.setUTCDate(d.getUTCDate()+amount);
    return d.getUTCFullYear()+'-'+pad(d.getUTCMonth()+1)+'-'+pad(d.getUTCDate());
  }
  function nextInfo(date, language) {
    var next=api.nextOpeningAcrossSeasons(date); if(!next) return null;
    var tomorrow=next.date===addDays(date,1), time=headerTime(next.status.openTime,language);
    return {next:next,tomorrow:tomorrow,time:time};
  }
  function tomorrowLabel(language) {
    if(language==='en')return 'See you tomorrow!';
    if(language==='de')return 'Bis morgen!';
    return 'À demain !';
  }
  function nextLabel(language) {
    if(language==='en')return 'Next opening';
    if(language==='de')return 'Nächste Öffnung';
    return 'Prochaine ouverture';
  }
  function fromLabel(time, language) {
    if(language==='en')return 'From '+time;
    if(language==='de')return 'Ab '+time;
    return 'À partir de '+time;
  }

  function patchHome(root, now, status) {
    var language=root.getAttribute('data-htp-lang')||'fr',info=nextInfo(now.date,language); if(!info)return;
    var statusEl=root.querySelector('[data-htp-home-status]'),hoursEl=root.querySelector('[data-htp-home-hours]'),lastEl=root.querySelector('[data-htp-home-last-entry]');
    if(statusEl)statusEl.textContent=info.tomorrow?tomorrowLabel(language):nextLabel(language);
    if(hoursEl)hoursEl.textContent=fromLabel(info.time,language);
    if(lastEl){lastEl.textContent='';lastEl.hidden=true;}
    root.classList.remove('is-open','is-before-open');root.classList.add('is-after-close');
  }

  function patchHeaderStatus(root, now, status) {
    var language=root.getAttribute('data-htp-lang')||'fr',info=nextInfo(now.date,language); if(!info)return;
    root.textContent=info.tomorrow?tomorrowLabel(language):nextLabel(language);
    root.classList.remove('is-open');root.classList.add('is-closed');
  }

  function patchHeaderHour(root, now, status) {
    var language=root.getAttribute('data-htp-lang')||'fr',info=nextInfo(now.date,language); if(!info)return;
    root.textContent='';
    var main=document.createElement('span');main.className='parcs-ht-header-hour-main';main.textContent=fromLabel(info.time,language);root.appendChild(main);
    root.classList.remove('is-open');root.classList.add('is-closed');
  }

  function patchToday(root, now, status) {
    var language=root.getAttribute('data-htp-lang')||'fr',d=dict(language),info=nextInfo(now.date,language);
    var heading=root.querySelector('[data-htp-today-status]'),detail=root.querySelector('[data-htp-today-detail]');
    if(heading){heading.textContent=d.closedForToday||(language==='fr'?'Fermé pour aujourd’hui':language==='de'?'Für heute geschlossen':'Closed for today');heading.classList.remove('is-open');heading.classList.add('is-closed');}
    if(detail&&info)detail.textContent=(info.tomorrow?tomorrowLabel(language):nextLabel(language))+' · '+fromLabel(info.time,language);
  }

  function patch() {
    var now=nowLocal(),status=api.resolveAnyDay(now.date);
    if(!status || !status.open || !afterFinalClose(status,now.minutes))return;
    document.querySelectorAll('[data-htp-component="home-opening"]').forEach(function(root){patchHome(root,now,status);});
    document.querySelectorAll('[data-htp-component="header-status"]').forEach(function(root){patchHeaderStatus(root,now,status);});
    document.querySelectorAll('[data-htp-component="header-hour"]').forEach(function(root){patchHeaderHour(root,now,status);});
    document.querySelectorAll('[data-htp-component="today"]').forEach(function(root){patchToday(root,now,status);});
  }

  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',function(){setTimeout(patch,0);});
  else setTimeout(patch,0);
}());