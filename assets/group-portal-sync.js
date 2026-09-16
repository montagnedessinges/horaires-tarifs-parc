(function(){
  'use strict';

  function years(root,name){
    return String(root.getAttribute(name)||'').split(',').filter(function(year){return /^20\d{2}$/.test(year);});
  }

  function selectTariffs(root,year){
    var available=years(root,'data-group-tariff-years').indexOf(year)!==-1;
    var content=root.querySelector('[data-group-tariff-content]');
    var empty=root.querySelector('[data-group-tariff-empty]');
    if(content)content.hidden=!available;
    if(empty)empty.hidden=available;

    root.querySelectorAll('.parcs-ht-group-tariff-years-ui [data-htp-ui-year-panel]').forEach(function(panel){
      panel.hidden=!available||panel.getAttribute('data-htp-ui-year-panel')!==year;
    });
    root.querySelectorAll('.parcs-ht-group-tariff-years-ui [data-htp-ui-year]').forEach(function(button){
      var on=available&&button.getAttribute('data-htp-ui-year')===year;
      button.classList.toggle('is-active',on);
      button.setAttribute('aria-selected',on?'true':'false');
      button.tabIndex=on?0:-1;
    });
  }

  function selectCalendar(root,year){
    var available=years(root,'data-group-schedule-years').indexOf(year)!==-1;
    var content=root.querySelector('[data-group-calendar-content]');
    var empty=root.querySelector('[data-group-calendar-empty]');
    if(content)content.hidden=!available;
    if(empty)empty.hidden=available;
    if(!available||!content)return;

    var calendar=content.querySelector('[data-htp-component="calendar"]');
    if(!calendar)return;
    var button=calendar.querySelector('[data-htp-year="'+year+'"]');
    if(button&&button.getAttribute('aria-pressed')!=='true')button.click();
  }

  function selectYear(root,year){
    if(!/^20\d{2}$/.test(String(year||'')))return;
    root.setAttribute('data-group-selected-year',year);
    root.querySelectorAll('[data-group-year]').forEach(function(button){
      var on=button.getAttribute('data-group-year')===year;
      button.classList.toggle('is-active',on);
      button.setAttribute('aria-selected',on?'true':'false');
    });
    selectTariffs(root,year);
    selectCalendar(root,year);
  }

  function init(root){
    root.querySelectorAll('[data-group-main-tab]').forEach(function(button){
      button.addEventListener('click',function(){
        var key=button.getAttribute('data-group-main-tab');
        root.querySelectorAll('[data-group-main-tab]').forEach(function(other){
          var on=other===button;
          other.classList.toggle('is-active',on);
          other.setAttribute('aria-selected',on?'true':'false');
        });
        root.querySelectorAll('[data-group-main-panel]').forEach(function(panel){
          panel.hidden=panel.getAttribute('data-group-main-panel')!==key;
        });
      });
    });

    root.querySelectorAll('[data-group-year]').forEach(function(button){
      button.addEventListener('click',function(){selectYear(root,button.getAttribute('data-group-year'));});
    });

    var selected=String(root.getAttribute('data-group-selected-year')||'');
    if(selected)selectYear(root,selected);
  }

  function boot(){document.querySelectorAll('[data-group-portal]').forEach(init);}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
}());
