(function () {
  'use strict';

  var payload = window.ParcsHTPData || {};
  var settings = payload.settings || {};
  var dictionaries = payload.dictionary || {};
  var siteTimezone = settings.timezone || 'Europe/Paris';

  function dictionary(language) { return dictionaries[language] || dictionaries.fr || {}; }
  var seasons = settings.seasons || {};
  var activeSeasonYear = String(settings.activeSeasonYear || '');
  var dayCache = Object.create(null);
  var anyDayCache = Object.create(null);
  var nextOpeningCache = Object.create(null);
  function publishedYears() { return Object.keys(seasons).sort(); }
  function hydrateSeason(year) {
    year = String(year || '');
    var season = seasons[year] || null;
    activeSeasonYear = season ? year : '';
    settings.general = settings.general || {};
    settings.general.year = activeSeasonYear;
    settings.general.season_start = season ? (season.season_start || '') : '';
    settings.general.season_end = season ? (season.season_end || '') : '';
    settings.regularPeriods = season ? (season.regularPeriods || []) : [];
    settings.schoolHolidays = season ? (season.schoolHolidays || []) : [];
    settings.specialPeriods = season ? (season.specialPeriods || []) : [];
    settings.publicHolidays = season ? (season.publicHolidays || []) : [];
    settings.domainRules = season ? (season.domainRules || []) : [];
    settings.exceptions = season ? (season.exceptions || []) : [];
    // Un changement de saison invalide uniquement les calculs dépendant de la saison active.
    dayCache = Object.create(null);
    nextOpeningCache = Object.create(null);
  }
  hydrateSeason(activeSeasonYear || publishedYears()[0] || '');
  function translated(value, language) {
    if (!value || typeof value !== 'object') return typeof value === 'string' ? value : '';
    return value[language] || value.fr || '';
  }
  function translatedExact(value, language) {
    if (!value || typeof value !== 'object') return typeof value === 'string' ? value : '';
    return value[language] || '';
  }
  function text(template, values) {
    var output = template || '';
    Object.keys(values || {}).forEach(function (key) { output = output.replace(new RegExp('\\{' + key + '\\}', 'g'), values[key]); });
    return output;
  }
  function pad(value) { return String(value).length < 2 ? '0' + value : String(value); }
  function isoDate(year, month, day) { return year + '-' + pad(month) + '-' + pad(day); }
  function dateObject(ymd) {
    var parts = ymd.split('-').map(Number);
    return new Date(Date.UTC(parts[0], parts[1] - 1, parts[2], 12, 0, 0));
  }
  function addDays(ymd, amount) {
    var date = dateObject(ymd);
    date.setUTCDate(date.getUTCDate() + amount);
    return isoDate(date.getUTCFullYear(), date.getUTCMonth() + 1, date.getUTCDate());
  }
  function weekday(ymd) { var day = dateObject(ymd).getUTCDay(); return day === 0 ? 7 : day; }
  function parisNow() {
    var parts = new Intl.DateTimeFormat('fr-CA', {timeZone:siteTimezone,year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',hourCycle:'h23'}).formatToParts(new Date());
    var values = {};
    parts.forEach(function (part) { values[part.type] = part.value; });
    return {date:values.year+'-'+values.month+'-'+values.day,time:values.hour+':'+values.minute,minutes:Number(values.hour)*60+Number(values.minute),dateTime:values.year+'-'+values.month+'-'+values.day+'T'+values.hour+':'+values.minute};
  }
  function minutes(timeValue) { if (!timeValue) return 0; var parts=timeValue.split(':'); return Number(parts[0])*60+Number(parts[1]); }
  function fromMinutes(value) { value=((value%1440)+1440)%1440; return pad(Math.floor(value/60))+':'+pad(value%60); }
  function timeLabel(value, language) {
    if (!value) return '';
    var parts=value.split(':').map(Number), hour=parts[0], minute=parts[1];
    if (language==='en') { var suffix=hour>=12?'PM':'AM'; var display=hour%12||12; return display+(minute?':'+pad(minute):'')+' '+suffix; }
    if (language==='de') return hour+(minute?':'+pad(minute):'')+' Uhr';
    return hour+' h'+(minute?' '+pad(minute):'');
  }
  function contrastText(color) {
    var hex=String(color||'').replace('#','');
    if(hex.length===3)hex=hex.split('').map(function(c){return c+c;}).join('');
    if(!/^[0-9a-f]{6}$/i.test(hex))return '#1f2926';
    var r=parseInt(hex.slice(0,2),16),g=parseInt(hex.slice(2,4),16),b=parseInt(hex.slice(4,6),16);
    var luminance=(0.299*r+0.587*g+0.114*b)/255;
    return luminance<0.56?'#ffffff':'#1f2926';
  }
  function dateLabel(ymd, language, options) {
    var locale=language==='en'?'en-GB':(language==='de'?'de-DE':'fr-FR');
    return new Intl.DateTimeFormat(locale, options || {weekday:'long',day:'numeric',month:'long',year:'numeric',timeZone:siteTimezone}).format(dateObject(ymd));
  }
  function monthLabel(monthKey, language) {
    return dateLabel(monthKey+'-01', language, {month:'long',year:'numeric',timeZone:siteTimezone});
  }
  function enabled(row) { return row && String(row.enabled)==='1'; }
  function inRange(date, start, end) { return !!start && !!end && date>=start && date<=end; }
  function schoolHolidayRow(date) {
    return (settings.schoolHolidays||[]).find(function (row) { return enabled(row)&&inRange(date,row.start,row.end); }) || null;
  }
  function specialPeriodRows(date, publicOnly) {
    return (settings.specialPeriods||[]).filter(function (row) {
      if(!enabled(row)||!inRange(date,row.start,row.end))return false;
      if(publicOnly && String(row.show_on_calendar)==='0')return false;
      return true;
    });
  }
  function inSchoolHoliday(date) {
    return !!schoolHolidayRow(date);
  }

  function specialIcon(row) {
    var icons={star:'★',camera:'⌾',calendar:'▣',gift:'◇',music:'♪',leaf:'◆',flag:'⚑',heart:'♥',info:'●'};
    return icons[String((row||{}).icon||'star')] || '★';
  }
  function eventIconSvg(name) {
    var common='viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"';
    var paths={
      star:'<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.3l-5.6 2.9 1.1-6.2L3 9.6l6.2-.9L12 3Z"/>',
      camera:'<rect x="3" y="6" width="18" height="14" rx="2"/><path d="M8 6l1.5-2h5L16 6"/><circle cx="12" cy="13" r="4"/>',
      calendar:'<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 10h18"/>',
      gift:'<path d="M4 10h16v11H4zM2 7h20v3H2zM12 7v14"/><path d="M12 7H8.5A2.5 2.5 0 1 1 11 4.5V7Zm0 0h3.5A2.5 2.5 0 1 0 13 4.5V7Z"/>',
      music:'<path d="M9 18V6l10-2v12"/><circle cx="6" cy="18" r="3"/><circle cx="16" cy="16" r="3"/>',
      leaf:'<path d="M20 4C10 4 5 8 5 15c0 3 2 5 5 5 7 0 10-8 10-16Z"/><path d="M5 20c2-5 6-8 11-11"/>',
      flag:'<path d="M5 21V4M5 5h12l-2 4 2 4H5"/>',
      heart:'<path d="M20.8 5.7a5.5 5.5 0 0 0-7.8 0L12 6.7l-1-1a5.5 5.5 0 0 0-7.8 7.8L12 22l8.8-8.5a5.5 5.5 0 0 0 0-7.8Z"/>',
      info:'<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/>'
    };
    return '<svg '+common+'>'+(paths[name]||paths.star)+'</svg>';
  }
  function isEventRow(row) { return String((row||{}).kind||'') === 'event'; }
  function periodMarker(row) { return String((row||{}).kind||'') === 'school_holiday' ? '●' : '•'; }

  function publicHolidayRow(date) {
    return (settings.publicHolidays||[]).find(function (row) { return enabled(row)&&row.date===date; }) || null;
  }
  function isPublicHoliday(date) { return !!publicHolidayRow(date); }

  function resolveDay(date) {
    if (dayCache[date]) return dayCache[date];
    var seasonStart=String((settings.general||{}).season_start||''),seasonEnd=String((settings.general||{}).season_end||'');
    if((seasonStart&&date<seasonStart)||(seasonEnd&&date>seasonEnd))return dayCache[date]={inSeason:false,open:false,exceptional:false,type:'outside',color:'#eeeeee',slots:[]};
    var exceptions=(settings.exceptions||[]).filter(function (row) { return enabled(row)&&inRange(date,row.start,row.end); }).slice();
    exceptions.sort(function (a,b) {
      var priority=Number(b.priority||0)-Number(a.priority||0);
      if (priority) return priority;
      if (a.type===b.type) return 0;
      return a.type==='closed'?-1:1;
    });
    if (exceptions.length) {
      var exception=exceptions[0];
      if (exception.type==='closed') return dayCache[date]={open:false,exceptional:true,type:'closed',exception:exception,color:'#eeeeee'};
      if (exception.open&&exception.close) {var slots=[{open:exception.open,close:exception.close}];if(exception.open2&&exception.close2)slots.push({open:exception.open2,close:exception.close2});return dayCache[date]={open:true,exceptional:true,type:'hours',openTime:exception.open,closeTime:exception.close,lastEntryMinutes:exception.last_entry_minutes,color:(settings.general||{}).accent_color||'#ef7b5b',exception:exception,slots:slots};}
    }
    var isoWeekday=String(weekday(date));
    var period=(settings.regularPeriods||[]).find(function (row) { return enabled(row)&&inRange(date,row.start,row.end)&&(row.weekdays||[]).map(String).indexOf(isoWeekday)!==-1; });
    if (!period||!period.open||!period.close) return dayCache[date]={open:false,exceptional:false,type:'closed',color:'#eeeeee'};
    var slots=[{open:period.open,close:period.close}];if(period.open2&&period.close2)slots.push({open:period.open2,close:period.close2});return dayCache[date]={open:true,exceptional:false,type:'regular',openTime:period.open,closeTime:period.close,lastEntryMinutes:period.last_entry_minutes,color:period.color||'#9AAA8B',period:period,slots:slots};
  }


  function resolveDayInSeason(date, season) {
    season=season||{};
    if((season.season_start&&date<season.season_start)||(season.season_end&&date>season.season_end))return {inSeason:false,open:false,exceptional:false,type:'outside',color:'#eeeeee',slots:[]};
    var exceptions=(season.exceptions||[]).filter(function (row) { return enabled(row)&&inRange(date,row.start,row.end); }).slice();
    exceptions.sort(function (a,b) { var priority=Number(b.priority||0)-Number(a.priority||0); if(priority)return priority; if(a.type===b.type)return 0; return a.type==='closed'?-1:1; });
    if(exceptions.length){var ex=exceptions[0];if(ex.type==='closed')return {open:false,exceptional:true,type:'closed',exception:ex,color:'#eeeeee'};if(ex.open&&ex.close){var slots=[{open:ex.open,close:ex.close}];if(ex.open2&&ex.close2)slots.push({open:ex.open2,close:ex.close2});return {open:true,exceptional:true,type:'hours',openTime:ex.open,closeTime:ex.close,lastEntryMinutes:ex.last_entry_minutes,color:(settings.general||{}).accent_color||'#ef7b5b',exception:ex,slots:slots};}}
    var isoWeekday=String(weekday(date));
    var period=(season.regularPeriods||[]).find(function(row){return enabled(row)&&inRange(date,row.start,row.end)&&(row.weekdays||[]).map(String).indexOf(isoWeekday)!==-1;});
    if(!period||!period.open||!period.close)return {open:false,exceptional:false,type:'closed',color:'#eeeeee'};
    var slots=[{open:period.open,close:period.close}];if(period.open2&&period.close2)slots.push({open:period.open2,close:period.close2});return {open:true,exceptional:false,type:'regular',openTime:period.open,closeTime:period.close,lastEntryMinutes:period.last_entry_minutes,color:period.color||'#9AAA8B',period:period,slots:slots};
  }
  function resolveAnyDay(date){
    if (anyDayCache[date]) return anyDayCache[date];
    var year=String(date||'').slice(0,4),season=seasons[year]||null;
    anyDayCache[date]=season?resolveDayInSeason(date,season):{open:false,exceptional:false,type:'closed',color:'#eeeeee'};
    return anyDayCache[date];
  }
  function nextOpeningAcrossSeasons(afterDate) {
    var key='all|'+afterDate;
    if(Object.prototype.hasOwnProperty.call(nextOpeningCache,key))return nextOpeningCache[key];
    for(var offset=1;offset<=1095;offset++){var date=addDays(afterDate,offset),status=resolveAnyDay(date);if(status.open){nextOpeningCache[key]={date:date,status:status};return nextOpeningCache[key];}}
    nextOpeningCache[key]=null;return null;
  }
  function dayRanges(status,language) {
    return statusSlots(status).map(function(slot){
      return timeLabel(slot.open,language)+'–'+timeLabel(slot.close,language);
    }).join(' / ');
  }
  function lastEntryTime(status,closeOverride) {
    var specific=String(status.lastEntryMinutes||'');
    var offset=specific!==''?Number(specific):Number((settings.general||{}).last_entry_minutes||0);
    var slots=statusSlots(status),lastSlot=slots.length?slots[slots.length-1]:null;
    var closing=closeOverride||(lastSlot&&lastSlot.close)||status.closeTime;
    return fromMinutes(minutes(closing)-offset);
  }
  function nextOpening(afterDate) {
    var key='active|'+activeSeasonYear+'|'+afterDate;
    if(Object.prototype.hasOwnProperty.call(nextOpeningCache,key))return nextOpeningCache[key];
    for (var offset=1;offset<=730;offset++) { var date=addDays(afterDate,offset),status=resolveDay(date); if(status.open){nextOpeningCache[key]={date:date,status:status};return nextOpeningCache[key];} }
    nextOpeningCache[key]=null;return null;
  }
  function domainRule(date) {
    var resolved = resolveDay(date);
    if(!resolved || !resolved.open)return null;
    var blockingPeriod=specialPeriodRows(date,false).find(function(row){return !isEventRow(row)&&String(row.skip_domain_rules)==='1';});
    if(blockingPeriod)return null;
    var activeExceptions=(settings.exceptions||[]).filter(function (row) { return enabled(row)&&inRange(date,row.start,row.end); }).slice();
    activeExceptions.sort(function (a,b) { var priority=Number(b.priority||0)-Number(a.priority||0); if(priority)return priority; if(a.type===b.type)return 0; return a.type==='closed'?-1:1; });
    if(activeExceptions.length && String(activeExceptions[0].apply_domain_rules)==='0') return null;
    var day=weekday(date);
    return (settings.domainRules||[]).find(function (row) {
      if(!enabled(row)||!inRange(date,row.start,row.end))return false;
      if((row.weekdays||[]).map(String).indexOf(String(day))===-1)return false;
      if(String(row.exclude_weekends)==='1'&&(day===6||day===7))return false;
      if(String(row.exclude_school_holidays)==='1'&&inSchoolHoliday(date))return false;
      if(String(row.exclude_public_holidays)==='1'&&isPublicHoliday(date))return false;
      return true;
    })||null;
  }
  function activeAlert() {
    var current=parisNow(),now=current.dateTime;
    var alert=(settings.alerts||[]).find(function (row) { return enabled(row)&&row.start&&now>=row.start&&(!row.end||now<=row.end); });
    if(alert)return alert;
    var closure=(settings.exceptions||[]).filter(function(row){return enabled(row)&&row.type==='closed'&&inRange(current.date,row.start,row.end);}).sort(function(a,b){return Number(b.priority||0)-Number(a.priority||0);})[0];
    return closure?{title:closure.title,message:closure.message,button_label:{fr:'',en:'',de:''},button_url:''}:null;
  }
  function monthKeys() {
    if(!activeSeasonYear)return [];
    var keys=[];
    for(var month=1;month<=12;month++)keys.push(activeSeasonYear+'-'+pad(month));
    return keys;
  }
  function initialMonth() {
    var keys=monthKeys(),now=parisNow();
    if(!keys.length)return '';
    var current=now.date.slice(0,7);
    if(keys.indexOf(current)!==-1)return current;
    var next=nextOpening(now.date);
    if(next&&keys.indexOf(next.date.slice(0,7))!==-1)return next.date.slice(0,7);
    return keys[0];
  }

  function initToday(root) {
    var language=root.getAttribute('data-htp-lang')||'fr',d=dictionary(language),now=parisNow(),status=resolveDay(now.date);
    root.querySelector('[data-htp-today-kicker]').textContent=d.today;
    var heading=root.querySelector('[data-htp-today-status]'),detail=root.querySelector('[data-htp-today-detail]');heading.classList.remove('is-open','is-closed');
    if(!activeSeasonYear){heading.textContent=d.notAvailable||'';detail.textContent='';return;}
    if(!status.open){heading.classList.add('is-closed');heading.textContent=d.closedToday;var next=nextOpeningAcrossSeasons(now.date),closedBits=[];if(status.exceptional&&status.exception&&String(status.exception.show_public_marker)!=='0'){closedBits.push(d.exceptionalClosure);var closedContext=translated(status.exception.context,language);if(closedContext)closedBits.push(closedContext);}if(next)closedBits.push(text(d.nextOpening,{date:dateLabel(next.date,language,{weekday:'long',day:'numeric',month:'long',timeZone:siteTimezone}),time:timeLabel(next.status.openTime,language)}));detail.textContent=closedBits.join(' · ');return;}
    var open=minutes(status.openTime),message,currentSlot=activeSlot(status,now.minutes),upcomingSlot=nextSlot(status,now.minutes);
    if(currentSlot){message=d.openNow;heading.classList.add('is-open');}
    else if(upcomingSlot){
      message=now.minutes<open?text(d.opensToday,{open:timeLabel(upcomingSlot.open,language)}):text(d.reopensToday||d.opensToday,{open:timeLabel(upcomingSlot.open,language)});
    }else{message=d.closedForToday;var nextAfter=nextOpeningAcrossSeasons(now.date);heading.textContent=message;detail.textContent=nextAfter?text(d.nextOpening,{date:dateLabel(nextAfter.date,language,{weekday:'long',day:'numeric',month:'long',timeZone:siteTimezone}),time:timeLabel(nextAfter.status.openTime,language)}):'';return;}
    heading.textContent=message;
    var todayRange=dayRanges(status,language);
    var todayBits=[todayRange,text(d.lastEntry,{time:timeLabel(lastEntryTime(status),language)})];
    if(status.exceptional && status.exception && String(status.exception.show_public_marker)!=='0'){
      todayBits.push(status.type==='closed'?d.exceptionalClosure:d.exceptionalHours);
      var todayContext=translated(status.exception.context,language); if(todayContext) todayBits.push(todayContext);
    }
    detail.textContent=todayBits.join(' · ');
  }

  function monthSummary(monthKey,language) {
    var year=Number(monthKey.slice(0,4)),month=Number(monthKey.slice(5,7)),days=new Date(Date.UTC(year,month,0)).getUTCDate(),seen=[];
    for(var day=1;day<=days;day++){var status=resolveDay(isoDate(year,month,day));if(!status.open)continue;var label=dayRanges(status,language);if(seen.indexOf(label)===-1)seen.push(label);}
    if(!seen.length)return dictionary(language).closed;
    return text(dictionary(language).monthHours,{hours:seen.join(' · ')});
  }
  function dayAria(date,status,language) {
    var d=dictionary(language),label=dateLabel(date,language);
    var events=specialPeriodRows(date),suffix=(events.length?', '+(translated((settings.general||{}).event_legend_label,language)||d.event):'');
    if(!status.open)return label+', '+(status.exceptional && (!status.exception || String(status.exception.show_public_marker)!=='0')?d.exceptionalClosure:d.closed)+suffix;
    return label+', '+dayRanges(status,language)+(status.exceptional && (!status.exception || String(status.exception.show_public_marker)!=='0')?', '+d.exceptionalHours:'')+suffix;
  }

  var domainTooltipSequence = 0;

  function defaultDomainTooltip(language) {
    if (language === 'en') return 'This temporary interruption allows our teams to take their break. The rest of the park remains accessible during this time.';
    if (language === 'de') return 'Diese vorübergehende Unterbrechung ermöglicht unserem Team eine Pause. Der übrige Park bleibt während dieser Zeit zugänglich.';
    return 'Cette interruption temporaire permet à nos équipes de prendre leur pause. Le reste du parc reste accessible pendant ce temps.';
  }

  function domainTooltipButtonLabel(language) {
    if (language === 'en') return 'Why is access temporarily interrupted?';
    if (language === 'de') return 'Warum ist der Zugang vorübergehend unterbrochen?';
    return 'Pourquoi cet accès est-il temporairement interrompu ?';
  }

  function closeDomainTooltips(exceptWrap) {
    document.querySelectorAll('.parcs-ht-domain-help-wrap').forEach(function (wrap) {
      if (exceptWrap && wrap === exceptWrap) return;
      var button = wrap.querySelector('.parcs-ht-domain-help');
      var bubble = wrap.querySelector('.parcs-ht-domain-tooltip');
      if (button) button.setAttribute('aria-expanded', 'false');
      if (bubble) bubble.hidden = true;
    });
  }

  function createDomainTooltip(rule, language) {
    var enabledTooltip = String(rule && rule.show_tooltip !== undefined ? rule.show_tooltip : '1') === '1';
    if (!enabledTooltip) return null;

    var message = translated(rule.tooltip_text, language) || defaultDomainTooltip(language);
    if (!message) return null;

    domainTooltipSequence += 1;
    var wrap = document.createElement('span');
    wrap.className = 'parcs-ht-domain-help-wrap';

    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'parcs-ht-domain-help';
    button.textContent = '!';
    button.setAttribute('aria-expanded', 'false');
    button.setAttribute('aria-label', domainTooltipButtonLabel(language));

    var bubble = document.createElement('span');
    bubble.className = 'parcs-ht-domain-tooltip';
    bubble.id = 'parcs-ht-domain-tooltip-' + domainTooltipSequence;
    bubble.setAttribute('role', 'tooltip');
    bubble.textContent = message;
    bubble.hidden = true;
    button.setAttribute('aria-describedby', bubble.id);

    button.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();
      var willOpen = bubble.hidden;
      closeDomainTooltips(willOpen ? wrap : null);
      bubble.hidden = !willOpen;
      button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    });
    bubble.addEventListener('click', function (event) { event.stopPropagation(); });

    wrap.appendChild(button);
    wrap.appendChild(bubble);
    return wrap;
  }

  document.addEventListener('click', function (event) {
    if (!event.target.closest('.parcs-ht-domain-help-wrap')) closeDomainTooltips();
  });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') closeDomainTooltips();
  });
  function renderDayDetail(root,date,language) {
    var d=dictionary(language),status=resolveDay(date),box=root.querySelector('[data-htp-day-detail]');
    box.innerHTML='';
    var heading=document.createElement('h3');heading.textContent=dateLabel(date,language);box.appendChild(heading);
    var hours=document.createElement('p');hours.className='parcs-ht-day-hours';
    if(status.open)hours.textContent=dayRanges(status,language);else hours.textContent=(status.exceptional&&(!status.exception||String(status.exception.show_public_marker)!=='0'))?d.exceptionalClosure:d.closed;
    var parkHoursTitle=translated((settings.general||{}).calendar_hours_title,language)||(language==='en'?'Park opening hours':language==='de'?'Öffnungszeiten des Parks':'Horaires du parc');
    var parkTitle=document.createElement('p');parkTitle.className='parcs-ht-park-hours-title';parkTitle.textContent=parkHoursTitle;box.appendChild(parkTitle);
    box.appendChild(hours);
    if(status.open){var last=document.createElement('p');last.className='parcs-ht-day-last';last.textContent=text(d.lastEntry,{time:timeLabel(lastEntryTime(status),language)});box.appendChild(last);}
    if(status.exceptional && (!status.exception || String(status.exception.show_public_marker)!=='0')){var context=translated(status.exception.context,language),title=translated(status.exception.title,language),message=translated(status.exception.message,language),note=document.createElement('div');note.className='parcs-ht-exception-note';var parts=[];if(context)parts.push(context);if(title)parts.push(title);if(message)parts.push(message);note.textContent=parts.join(' — ');if(note.textContent)box.appendChild(note);}
    var holiday=((settings.general||{}).show_public_holidays==='1')?publicHolidayRow(date):null;if(holiday){var holidayText=translated((settings.general||{}).holiday_message,language)||d.publicHoliday;var holidayNote=document.createElement('p');holidayNote.className='parcs-ht-holiday-note';holidayNote.textContent=holidayText;box.appendChild(holidayNote);}
    if(status.open){
      var rule=domainRule(date);
      if(rule){
        var domain=document.createElement('div');
        domain.className='parcs-ht-domain-note';
        var domainColor=String(rule.color||(settings.general||{}).highlight_color||'#e7c55b');
        domain.style.setProperty('--htp-domain-color',domainColor);
        var title=translated(rule.public_title,language)||'';
        var titleRow=document.createElement('div');
        titleRow.className='parcs-ht-domain-title-row';
        if(title){var dt=document.createElement('strong');dt.textContent=title;titleRow.appendChild(dt);}
        var tooltip=createDomainTooltip(rule,language);
        if(tooltip)titleRow.appendChild(tooltip);
        if(titleRow.childNodes.length)domain.appendChild(titleRow);
        if(String(rule.auto_details)!=='0'){
          var values={pause_start:timeLabel(rule.pause_start,language),resume:timeLabel(rule.resume,language),last_entry:timeLabel(rule.last_entry,language)};
          var accessTemplate=translated(rule.access_message,language)||(language==='en'?'This area is not accessible from {pause_start} to {resume}.':language==='de'?'Dieser Bereich ist von {pause_start} bis {resume} nicht zugänglich.':'Cette zone n’est pas accessible de {pause_start} à {resume}.');
          var detailsTemplate=translated(rule.details_message,language)||(language==='en'?'Last admission: {last_entry} · Visits resume: {resume}':language==='de'?'Letzter Einlass: {last_entry} · Besuche wieder ab: {resume}':'Dernière entrée : {last_entry} · Reprise des visites : {resume}');
          if(rule.pause_start&&rule.resume){var accessLine=document.createElement('p');accessLine.className='parcs-ht-domain-access';accessLine.textContent=text(accessTemplate,values);domain.appendChild(accessLine);}
          if(rule.last_entry||rule.resume){var detailsLine=document.createElement('p');detailsLine.className='parcs-ht-domain-times';detailsLine.textContent=text(detailsTemplate,values);domain.appendChild(detailsLine);}
        }
        var info=translated(rule.info,language);
        if(info){var ip=document.createElement('p');ip.className='parcs-ht-domain-extra';ip.textContent=info;domain.appendChild(ip);}
        if(domain.childNodes.length)box.appendChild(domain);
      }
    }
    var events=specialPeriodRows(date,true);
    events.forEach(function(eventRow){
      var eventTitle=translated(eventRow.title,language)||eventRow.internal_label||(isEventRow(eventRow)?d.event:'');
      var eventWrap=document.createElement('div');eventWrap.className='parcs-ht-event-note '+(isEventRow(eventRow)?'is-event':'is-period');eventWrap.style.setProperty('--htp-event-color',isEventRow(eventRow)?'#e7c55b':(eventRow.color||'#7b61a8'));
      var eventHead=document.createElement('div');eventHead.className='parcs-ht-event-head';
      if(eventTitle){var eh=document.createElement('h4');eh.className='parcs-ht-event-title';if(isEventRow(eventRow)){var em=document.createElement('span');em.className='parcs-ht-event-title-icon';em.innerHTML=eventIconSvg('star');eh.appendChild(em);}eh.appendChild(document.createTextNode((isEventRow(eventRow)?' ':'')+eventTitle));eventHead.appendChild(eh);}
      var eventButton=translated(eventRow.button_label,language),eventUrl=translatedExact(eventRow.button_url,language);if(String(eventRow.show_button||'0')==='1'&&eventButton&&eventUrl){var ea=document.createElement('a');ea.className='parcs-ht-button parcs-ht-event-link';ea.href=eventUrl;ea.textContent=eventButton;eventHead.appendChild(ea);}
      if(eventHead.childNodes.length)eventWrap.appendChild(eventHead);
      var eventMessage=translated(eventRow.message,language);if(eventMessage){var ep=document.createElement('p');ep.textContent=eventMessage;eventWrap.appendChild(ep);}
      box.appendChild(eventWrap);
    });
  }
  function renderPeriodLegends(root,monthKey,language) {
    var host=root.querySelector('[data-htp-period-legends]');if(!host)return;host.innerHTML='';
    var monthStart=monthKey+'-01',y=Number(monthKey.slice(0,4)),m=Number(monthKey.slice(5,7)),monthEnd=isoDate(y,m,new Date(Date.UTC(y,m,0)).getUTCDate());
    var hasVisibleEvent=false,seen={};(settings.specialPeriods||[]).forEach(function(row){
      if(!enabled(row)||String(row.show_on_calendar)==='0'||!row.start||!row.end)return;
      if(row.end<monthStart||row.start>monthEnd)return;
      if(isEventRow(row)){hasVisibleEvent=true;return;}
      var label=translated(row.title,language)||row.internal_label||'';if(!label||seen[label])return;seen[label]=true;
      var item=document.createElement('span');item.className='parcs-ht-period-legend-item';
      var band=document.createElement('i');band.className='parcs-ht-period-legend';band.style.color=row.color||'#7b61a8';band.setAttribute('aria-hidden','true');
      var textEl=document.createElement('span');textEl.textContent=label;item.appendChild(band);item.appendChild(textEl);host.appendChild(item);
    });
    host.hidden=!host.childNodes.length;
    var eventWrap=root.querySelector('[data-htp-event-legend-wrap]');
    var eventLegend=root.querySelector('[data-htp-legend-event]');
    if(eventLegend)eventLegend.textContent=translated((settings.general||{}).event_legend_label,language)||dictionary(language).event||'Événement';
    if(eventWrap)eventWrap.hidden=!hasVisibleEvent;
  }

  function renderMonth(root,monthKey,language) {
    var year=Number(monthKey.slice(0,4)),month=Number(monthKey.slice(5,7)),daysCount=new Date(Date.UTC(year,month,0)).getUTCDate(),firstWeekday=weekday(monthKey+'-01'),now=parisNow();
    var grid=root.querySelector('[data-htp-calendar-grid]');grid.innerHTML='';
    var weekdays=document.createElement('div');weekdays.className='parcs-ht-weekdays';
    var weekdayLabels=language==='en'?['Mon','Tue','Wed','Thu','Fri','Sat','Sun']:(language==='de'?['Mo','Di','Mi','Do','Fr','Sa','So']:['L','M','M','J','V','S','D']);
    weekdayLabels.forEach(function(label){var span=document.createElement('span');span.textContent=label;weekdays.appendChild(span);});grid.appendChild(weekdays);
    var days=document.createElement('div');days.className='parcs-ht-days';
    for(var blank=1;blank<firstWeekday;blank++){var empty=document.createElement('span');empty.className='parcs-ht-empty';empty.setAttribute('aria-hidden','true');days.appendChild(empty);}
    var selectedDate=(now.date.slice(0,7)===monthKey)?now.date:'';
    for(var day=1;day<=daysCount;day++){
      var date=isoDate(year,month,day),status=resolveDay(date),holiday=((settings.general||{}).show_public_holidays==='1')?publicHolidayRow(date):null,events=specialPeriodRows(date,true),button=document.createElement('button');button.type='button';button.className='parcs-ht-day '+(status.open?'is-open':'is-closed')+(holiday?' is-public-holiday':'')+(events.length?' is-special-event':'');if(holiday){button.style.setProperty('--htp-holiday-border',(settings.general||{}).holiday_border_color||'#e7c55b');button.style.setProperty('--htp-holiday-border-width',String((settings.general||{}).holiday_border_width||3)+'px');}button.setAttribute('data-htp-date',date);button.setAttribute('aria-label',dayAria(date,status,language));var dayNumber=document.createElement('span');dayNumber.className='parcs-ht-day-number';dayNumber.textContent=String(day);button.appendChild(dayNumber);
      if(date===now.date)button.classList.add('is-today');
      if(status.exceptional && (!status.exception || String(status.exception.show_public_marker)!=='0')){var symbol=document.createElement('i');symbol.className='parcs-ht-symbol '+(status.type==='closed'?'is-closed':'is-hours');symbol.setAttribute('aria-hidden','true');symbol.textContent=status.type==='closed'?'×':'!';button.appendChild(symbol);}if(events.length){var eventMarker=events.find(isEventRow),periodRows=events.filter(function(r){return !isEventRow(r);});if(eventMarker){var star=document.createElement('i');var isLong=String(eventMarker.display_mode||'spot')==='long';star.className='parcs-ht-event-star is-event '+(isLong?'is-long':'is-spot');star.setAttribute('aria-hidden','true');if(isLong){star.textContent='•';}else{star.innerHTML=eventIconSvg('star');}button.appendChild(star);}periodRows.slice(0,3).forEach(function(periodRow,index){var band=document.createElement('i');band.className='parcs-ht-period-band';band.setAttribute('aria-hidden','true');band.style.setProperty('--htp-period-color',periodRow.color||'#7b61a8');band.style.setProperty('--htp-period-index',String(index));button.appendChild(band);});}
      days.appendChild(button);
    }
    grid.appendChild(days);root.querySelector('[data-htp-month-summary]').textContent=monthSummary(monthKey,language);renderPeriodLegends(root,monthKey,language);
    var selected=selectedDate||monthKey+'-01',selectedButton=days.querySelector('[data-htp-date="'+selected+'"]');
    if(selectedButton){selectedButton.classList.add('is-selected');renderDayDetail(root,selected,language);}else root.querySelector('[data-htp-day-detail]').textContent=dictionary(language).selectDate;
  }
  function initCalendar(root) {
    var language=root.getAttribute('data-htp-lang')||'fr',d=dictionary(language);
    var yearList=root.querySelector('[data-htp-year-list]');
    var years=publishedYears();
    function renderYearButtons(){
      if(!yearList)return;
      yearList.innerHTML='';
      yearList.hidden=years.length<=1;
      years.forEach(function(year){
        var button=document.createElement('button');button.type='button';button.textContent=year;button.setAttribute('data-htp-year',year);button.setAttribute('aria-pressed',year===activeSeasonYear?'true':'false');yearList.appendChild(button);
      });
    }
    function setupSeason(year){
      hydrateSeason(year);
      renderYearButtons();
      var keys=monthKeys(),current=initialMonth();
      root.querySelector('[data-htp-calendar-kicker]').textContent=d.calendar;
      root.querySelector('[data-htp-calendar-title]').textContent=activeSeasonYear||'';
      root.querySelector('[data-htp-legend-hours]').textContent=d.exceptionalHours;root.querySelector('[data-htp-legend-closed]').textContent=d.exceptionalClosure;var eventLegend=root.querySelector('[data-htp-legend-event]');if(eventLegend)eventLegend.textContent=translated((settings.general||{}).event_legend_label,language)||d.event||'Événement';
      var list=root.querySelector('[data-htp-month-list]');list.innerHTML='';
      keys.forEach(function(key){var button=document.createElement('button');button.type='button';button.setAttribute('data-htp-month',key);button.setAttribute('role','tab');button.setAttribute('aria-selected',key===current?'true':'false');button.textContent=dateLabel(key+'-01',language,{month:'long',timeZone:siteTimezone});list.appendChild(button);});
      function selectMonth(key){
        if(keys.indexOf(key)===-1)return;
        current=key;
        var index=keys.indexOf(key),activeButton=null;
        list.querySelectorAll('[data-htp-month]').forEach(function(button){
          var buttonKey=button.getAttribute('data-htp-month'),buttonIndex=keys.indexOf(buttonKey),selected=buttonKey===key;
          button.setAttribute('aria-selected',selected?'true':'false');
          button.classList.toggle('is-near-month',Math.abs(buttonIndex-index)<=1);
          if(selected)activeButton=button;
        });
        renderMonth(root,key,language);
        root.querySelector('[data-htp-prev]').disabled=index<=0;
        root.querySelector('[data-htp-next]').disabled=index>=keys.length-1;
        if(activeButton)requestAnimationFrame(function(){activeButton.scrollIntoView({behavior:'smooth',block:'nearest',inline:'center'});});
      }
      root._htpSelectMonth=selectMonth;root._htpKeys=keys;root._htpCurrent=function(){return current;};
      if(current){selectMonth(current);}else{root.querySelector('[data-htp-calendar-grid]').innerHTML='';root.querySelector('[data-htp-month-summary]').textContent='';root.querySelector('[data-htp-day-detail]').textContent=d.notAvailable||d.selectDate;root.querySelector('[data-htp-prev]').disabled=true;root.querySelector('[data-htp-next]').disabled=true;}
    }
    if(yearList)yearList.addEventListener('click',function(event){var b=event.target.closest('[data-htp-year]');if(b)setupSeason(b.getAttribute('data-htp-year'));});
    root.querySelector('[data-htp-month-list]').addEventListener('click',function(event){var button=event.target.closest('[data-htp-month]');if(button&&root._htpSelectMonth)root._htpSelectMonth(button.getAttribute('data-htp-month'));});
    root.querySelector('[data-htp-prev]').addEventListener('click',function(){var keys=root._htpKeys||[],current=root._htpCurrent?root._htpCurrent():'',index=keys.indexOf(current);if(index>0)root._htpSelectMonth(keys[index-1]);});
    root.querySelector('[data-htp-next]').addEventListener('click',function(){var keys=root._htpKeys||[],current=root._htpCurrent?root._htpCurrent():'',index=keys.indexOf(current);if(index>=0&&index<keys.length-1)root._htpSelectMonth(keys[index+1]);});
    root.querySelector('[data-htp-calendar-grid]').addEventListener('click',function(event){var button=event.target.closest('[data-htp-date]');if(!button)return;root.querySelectorAll('.parcs-ht-day.is-selected').forEach(function(item){item.classList.remove('is-selected');});button.classList.add('is-selected');renderDayDetail(root,button.getAttribute('data-htp-date'),language);});
    var touchStart=0;root.querySelector('[data-htp-calendar-grid]').addEventListener('touchstart',function(event){touchStart=event.changedTouches[0].clientX;},{passive:true});root.querySelector('[data-htp-calendar-grid]').addEventListener('touchend',function(event){var keys=root._htpKeys||[],current=root._htpCurrent?root._htpCurrent():'',delta=event.changedTouches[0].clientX-touchStart,index=keys.indexOf(current);if(Math.abs(delta)<55)return;if(delta<0&&index<keys.length-1)root._htpSelectMonth(keys[index+1]);if(delta>0&&index>0)root._htpSelectMonth(keys[index-1]);},{passive:true});
    setupSeason(activeSeasonYear || years[0] || '');
  }

  function daysBetween(a,b){return Math.max(0,Math.round((dateObject(b)-dateObject(a))/86400000));}
  function shortDate(date,language){return dateLabel(date,language,{day:'numeric',month:'long',year:'numeric',timeZone:siteTimezone});}
  function headerTime(value,language){if(!value)return '';var parts=value.split(':').map(Number),h=parts[0],m=parts[1];if(language==='fr')return h+'h'+(m?pad(m):'');if(language==='de')return h+(m?':'+pad(m):'')+' Uhr';return timeLabel(value,language);}
  function statusSlots(status){return (status.slots&&status.slots.length)?status.slots:[{open:status.openTime,close:status.closeTime}];}
  function activeSlot(status,nowMinutes){var slots=statusSlots(status);for(var i=0;i<slots.length;i++){if(nowMinutes>=minutes(slots[i].open)&&nowMinutes<minutes(slots[i].close))return slots[i];}return null;}
  function nextSlot(status,nowMinutes){var slots=statusSlots(status);for(var i=0;i<slots.length;i++){if(nowMinutes<minutes(slots[i].open))return slots[i];}return null;}
  function allRanges(status,language){return statusSlots(status).map(function(x){return language==='fr'?headerTime(x.open,language)+'–'+headerTime(x.close,language):headerTime(x.open,language)+' – '+headerTime(x.close,language);}).join(' / ');}
  function headerRange(status,language){if(status.slots&&status.slots.length>1)return allRanges(status,language);if(language==='fr')return headerTime(status.openTime,language)+' à '+headerTime(status.closeTime,language);if(language==='de')return headerTime(status.openTime,language)+' – '+headerTime(status.closeTime,language);return headerTime(status.openTime,language)+' – '+headerTime(status.closeTime,language);}
  function compactNextDate(date,language){
    var nowYear=parseInt(String(parisNow().date).slice(0,4),10),nextYear=parseInt(String(date).slice(0,4),10),opts={day:'numeric',month:'long',timeZone:siteTimezone};
    if(nextYear!==nowYear)opts.year='numeric';
    return dateLabel(date,language,opts);
  }
  function compactLastEntry(status,language,d){
    var time=lastEntryTime(status,status.closeTime);
    if(!time)return '';
    return text(d.lastEntryCompact||d.lastEntry,{time:headerTime(time,language)});
  }
  function setHeaderHourLines(root,primary,secondary){
    root.textContent='';
    if(primary){var main=document.createElement('span');main.className='parcs-ht-header-hour-main';main.textContent=primary;root.appendChild(main);}
    if(secondary){var detail=document.createElement('span');detail.className='parcs-ht-header-hour-detail';detail.textContent=secondary;root.appendChild(detail);}
  }
  function initHeaderHour(root){
    var language=root.getAttribute('data-htp-lang')||'fr',d=dictionary(language),now=parisNow(),status=resolveAnyDay(now.date),primary='',secondary='';
    if(status.open){
      var open=minutes(status.openTime),close=minutes(status.closeTime);
      var hs=activeSlot(status,now.minutes),hn=nextSlot(status,now.minutes);
      if(hs||hn){primary=allRanges(status,language);if(hs){var hss=Object.assign({},status,{closeTime:hs.close});secondary=compactLastEntry(hss,language,d);}else secondary='';
      }else{
        var nextAfter=nextOpeningAcrossSeasons(now.date);
        if(nextAfter){
          var afterDays=daysBetween(now.date,nextAfter.date),afterTime=headerTime(nextAfter.status.openTime,language);
          if(afterDays===1)primary=text(d.openingAt||d.opensTomorrowAt,{time:afterTime,date:compactNextDate(nextAfter.date,language)});
          else primary=text(d.nextOpeningCompact||d.nextOpening,{date:compactNextDate(nextAfter.date,language),time:afterTime});
        }else primary=d.notAvailable||'';
      }
    }else{
      var next=nextOpeningAcrossSeasons(now.date);
      if(!next)primary=d.notAvailable||d.closedShort||d.closed;
      else {
        var days=daysBetween(now.date,next.date),openingTime=headerTime(next.status.openTime,language);
        if(days===1)primary=text(d.openingAt||d.opensTomorrowAt,{time:openingTime,date:compactNextDate(next.date,language)});
        else primary=text(d.nextOpeningCompact||d.nextOpening,{date:compactNextDate(next.date,language),time:openingTime});
      }
    }
    setHeaderHourLines(root,primary,secondary);
    root.classList.toggle('is-closed',!status.open);
    root.classList.toggle('is-open',!!status.open);
  }

  function initHeaderStatus(root){
    var language=root.getAttribute('data-htp-lang')||'fr',d=dictionary(language),now=parisNow(),status=resolveAnyDay(now.date),value='',isOpenNow=false;
    if(status.open){
      var open=minutes(status.openTime),close=minutes(status.closeTime);
      isOpenNow=!!activeSlot(status,now.minutes);
      var upcoming=nextSlot(status,now.minutes);
      if(isOpenNow)value=d.openNow||'OPEN';
      else if(upcoming)value=(language==='fr'?'Réouverture à ':language==='de'?'Wieder geöffnet um ':'Reopens at ')+headerTime(upcoming.open,language);
      else if(now.minutes<open)value=d.openTodayCompact||d.openToday||d.openNow||'OPEN';
      else {
        var nextAfter=nextOpeningAcrossSeasons(now.date),afterDays=nextAfter?daysBetween(now.date,nextAfter.date):0;
        value=(nextAfter&&afterDays===1)?(d.seeYouTomorrow||d.opensTomorrowAt||d.closedForToday):(d.nextOpeningLabel||d.nextOpening||d.closedForToday||d.closedShort);
      }
    }else{
      var next=nextOpeningAcrossSeasons(now.date),days=next?daysBetween(now.date,next.date):0;
      value=(next&&days===1)?(d.seeYouTomorrow||d.opensTomorrowAt||d.closedToday):(next?(d.nextOpeningLabel||d.nextOpening||d.closedToday):(d.closedToday||d.closedShort||d.closed||'Closed'));
    }
    root.textContent=value;
    root.classList.toggle('is-open',isOpenNow);
    root.classList.toggle('is-closed',!isOpenNow);
  }

  function initHomeOpening(root){
    var language=root.getAttribute('data-htp-lang')||'fr',d=dictionary(language),now=parisNow(),status=resolveAnyDay(now.date);
    var statusEl=root.querySelector('[data-htp-home-status]'),hoursEl=root.querySelector('[data-htp-home-hours]'),lastEl=root.querySelector('[data-htp-home-last-entry]');
    if(!statusEl||!hoursEl||!lastEl)return;
    var statusText='',hoursText='',lastText='';
    if(status.open){
      var open=minutes(status.openTime),close=minutes(status.closeTime);
      var currentSlot=activeSlot(status,now.minutes),upcomingSlot=nextSlot(status,now.minutes);
      if(currentSlot){
        statusText=d.openNow||'Open'; hoursText=allRanges(status,language);
        var slotStatus=Object.assign({},status,{closeTime:currentSlot.close}); lastText=compactLastEntry(slotStatus,language,d);
      }else if(upcomingSlot){
        statusText=(language==='fr'?'Réouverture à ':language==='de'?'Wieder geöffnet um ':'Reopens at ')+headerTime(upcomingSlot.open,language);
        hoursText=allRanges(status,language); lastText='';
      }else{
        var nextAfter=nextOpeningAcrossSeasons(now.date);
        if(nextAfter){
          var afterDays=daysBetween(now.date,nextAfter.date),afterTime=headerTime(nextAfter.status.openTime,language);
          if(afterDays===1){
            statusText=d.seeYouTomorrow||'See you tomorrow!';
            hoursText=text(d.openingAt||d.opensTomorrowAt,{time:afterTime,date:compactNextDate(nextAfter.date,language)});
          }else{
            statusText=d.nextOpeningLabel||'Next opening';
            hoursText=text(d.nextOpeningCompact||d.nextOpening,{date:compactNextDate(nextAfter.date,language),time:afterTime});
          }
        }else{
          statusText=d.closedShort||d.closed||'';
          hoursText=d.notAvailable||'';
        }
      }
    }else{
      var next=nextOpeningAcrossSeasons(now.date);
      if(next){
        var days=daysBetween(now.date,next.date),openingTime=headerTime(next.status.openTime,language);
        if(days===1){
          statusText=d.seeYouTomorrow||'See you tomorrow!';
          hoursText=text(d.openingAt||d.opensTomorrowAt,{time:openingTime,date:compactNextDate(next.date,language)});
        }else{
          statusText=d.nextOpeningLabel||'Next opening';
          hoursText=text(d.nextOpeningCompact||d.nextOpening,{date:compactNextDate(next.date,language),time:openingTime});
        }
      }else{
        statusText=d.closedShort||d.closed||'';
        hoursText=d.notAvailable||'';
      }
    }
    statusEl.textContent=statusText;
    hoursEl.textContent=hoursText;
    lastEl.textContent=lastText;
    lastEl.hidden=!lastText;
    var homeActive=status.open?activeSlot(status,now.minutes):null,homeUpcoming=status.open?nextSlot(status,now.minutes):null;
    root.classList.toggle('is-open',!!homeActive);
    root.classList.toggle('is-before-open',!!(status.open&&!homeActive&&homeUpcoming));
    root.classList.toggle('is-after-close',!!(status.open&&!homeActive&&!homeUpcoming));
  }

  function initTariffs(root) {
    var note=root.querySelector('[data-htp-groups-closed-note]');if(note)note.hidden=resolveAnyDay(parisNow().date).open;
    var tabs=Array.prototype.slice.call(root.querySelectorAll('[data-htp-tariff-tab]'));
    function select(tab){tabs.forEach(function(item){var active=item===tab;item.setAttribute('aria-selected',active?'true':'false');item.tabIndex=active?0:-1;var panel=root.querySelector('#'+item.getAttribute('aria-controls'));if(panel)panel.hidden=!active;});}
    tabs.forEach(function(tab,index){tab.addEventListener('click',function(){select(tab);});tab.addEventListener('keydown',function(event){if(event.key!=='ArrowRight'&&event.key!=='ArrowLeft')return;event.preventDefault();var next=(index+(event.key==='ArrowRight'?1:-1)+tabs.length)%tabs.length;select(tabs[next]);tabs[next].focus();});});
  }
  function initAlert(root) {
    var alert=activeAlert();if(!alert)return;
    var language=root.getAttribute('data-htp-lang')||'fr',d=dictionary(language),title=translated(alert.title,language),message=translated(alert.message,language),buttonLabel=translated(alert.button_label,language),buttonUrl=translatedExact(alert.button_url,language),display=root.getAttribute('data-htp-display');
    if(!title&&!message)return;
    var content=document.createElement('section');content.className='parcs-ht-alert';var heading=document.createElement('h2');heading.textContent=title||d.exceptionalClosure;content.appendChild(heading);if(message){var paragraph=document.createElement('p');paragraph.textContent=message;content.appendChild(paragraph);}if(String(alert.show_button||'0')==='1'&&buttonLabel&&buttonUrl){var link=document.createElement('a');link.className='parcs-ht-button';link.href=buttonUrl;link.textContent=buttonLabel;content.appendChild(link);}
    root.hidden=false;
    if(display!=='popup'){root.appendChild(content);return;}
    var previousFocus=document.activeElement;
    var modal=document.createElement('div');modal.className='parcs-ht-modal';modal.setAttribute('role','dialog');modal.setAttribute('aria-modal','true');modal.setAttribute('aria-label',title||d.exceptionalClosure);
    var dialog=document.createElement('div');dialog.className='parcs-ht-modal-dialog';var close=document.createElement('button');close.type='button';close.className='parcs-ht-modal-close';close.setAttribute('aria-label',d.closePopup);close.textContent='×';dialog.appendChild(close);dialog.appendChild(content);modal.appendChild(dialog);root.appendChild(modal);document.body.classList.add('parcs-ht-modal-open');
    function onKeydown(event){
      if(event.key==='Escape'){dismiss();return;}
      if(event.key!=='Tab')return;
      var focusable=modal.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])');
      if(!focusable.length){event.preventDefault();close.focus();return;}
      var first=focusable[0],last=focusable[focusable.length-1];
      if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus();}
      else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}
    }
    function dismiss(){document.removeEventListener('keydown',onKeydown);document.body.classList.remove('parcs-ht-modal-open');root.hidden=true;modal.remove();if(previousFocus&&typeof previousFocus.focus==='function')previousFocus.focus();}
    close.addEventListener('click',dismiss);modal.addEventListener('click',function(event){if(event.target===modal)dismiss();});document.addEventListener('keydown',onKeydown);close.focus();
  }

  function boot() {
    document.querySelectorAll('[data-htp-component="today"]').forEach(initToday);
    document.querySelectorAll('[data-htp-component="calendar"]').forEach(initCalendar);
    document.querySelectorAll('[data-htp-component="tariffs"]').forEach(initTariffs);
    document.querySelectorAll('[data-htp-component="alert"]').forEach(initAlert);
    document.querySelectorAll('[data-htp-component="header-hour"]').forEach(initHeaderHour);
    document.querySelectorAll('[data-htp-component="header-status"]').forEach(initHeaderStatus);
    document.querySelectorAll('[data-htp-component="home-opening"]').forEach(initHomeOpening);
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
  window.ParcsHTP={resolveDay:resolveDay,resolveAnyDay:resolveAnyDay,domainRule:domainRule,nextOpening:nextOpening,nextOpeningAcrossSeasons:nextOpeningAcrossSeasons,lastEntryTime:lastEntryTime,dayRanges:dayRanges,monthSummary:monthSummary,dayAria:dayAria};
}());
