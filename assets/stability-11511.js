(function(){
  'use strict';

  function qsa(root, selector){ return Array.prototype.slice.call((root || document).querySelectorAll(selector)); }

  function activeTariffKey(section){
    var active=section&&section.querySelector('[data-htp-tariff-tab][aria-selected="true"]');
    return active ? String(active.getAttribute('data-htp-tariff-tab')||'') : 'individual';
  }

  function activateTariff(section,key,focus){
    if(!section)return;
    var tabs=qsa(section,'[data-htp-tariff-tab]');
    var found=false;
    tabs.forEach(function(tab){
      var current=String(tab.getAttribute('data-htp-tariff-tab')||'')===key;
      if(current)found=true;
      tab.setAttribute('aria-selected',current?'true':'false');
      tab.setAttribute('tabindex',current?'0':'-1');
      var panelId=tab.getAttribute('aria-controls');
      var panel=panelId?document.getElementById(panelId):null;
      if(panel&&!section.contains(panel))panel=null;
      if(panel)panel.hidden=!current;
      if(current&&focus)tab.focus();
    });
    if(!found&&tabs.length){
      var fallback=String(tabs[0].getAttribute('data-htp-tariff-tab')||'individual');
      activateTariff(section,fallback,focus);
    }
  }

  function sectionIndex(section){
    return qsa(document,'.parcs-ht-tariffs').indexOf(section);
  }

  function updateYearTabs(section,year){
    qsa(section,'.parcs-ht-retail-year-tab').forEach(function(link){
      var active=String(link.getAttribute('data-htp-retail-year')||'')===String(year||'');
      link.classList.toggle('is-active',active);
      link.setAttribute('aria-selected',active?'true':'false');
    });
  }

  function replaceTariffSection(link){
    var section=link.closest('.parcs-ht-tariffs');
    if(!section)return;
    var nav=link.closest('.parcs-ht-retail-year-tabs');
    var keepKey=activeTariffKey(section);
    var index=sectionIndex(section);
    var year=String(link.getAttribute('data-htp-retail-year')||'');
    if(index<0)return;
    if(nav)nav.setAttribute('aria-busy','true');

    fetch(link.href,{credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}})
      .then(function(response){if(!response.ok)throw new Error('HTTP '+response.status);return response.text();})
      .then(function(html){
        var parsed=new DOMParser().parseFromString(html,'text/html');
        var sections=qsa(parsed,'.parcs-ht-tariffs');
        var incoming=sections[index]||sections[0];
        if(!incoming)throw new Error('Tariff section missing');
        var imported=document.importNode(incoming,true);
        section.replaceWith(imported);
        activateTariff(imported,keepKey,false);
        updateYearTabs(imported,year);
        try{window.history.replaceState(null,'',link.href);}catch(error){}
        document.dispatchEvent(new CustomEvent('parcsht:tariffs-updated',{detail:{year:year,section:imported}}));
      })
      .catch(function(){window.location.href=link.href;});
  }

  document.addEventListener('click',function(event){
    var yearLink=event.target.closest('.parcs-ht-retail-year-tab');
    if(yearLink){
      if(event.button!==0||event.metaKey||event.ctrlKey||event.shiftKey||event.altKey)return;
      event.preventDefault();
      if(yearLink.classList.contains('is-active'))return;
      replaceTariffSection(yearLink);
      return;
    }

    var tab=event.target.closest('[data-htp-tariff-tab]');
    if(!tab)return;
    var section=tab.closest('.parcs-ht-tariffs');
    if(!section)return;
    event.preventDefault();
    activateTariff(section,String(tab.getAttribute('data-htp-tariff-tab')||''),false);
  });

  document.addEventListener('keydown',function(event){
    var tab=event.target.closest&&event.target.closest('[data-htp-tariff-tab]');
    if(!tab||['ArrowLeft','ArrowRight','Home','End'].indexOf(event.key)===-1)return;
    var section=tab.closest('.parcs-ht-tariffs');
    var tabs=qsa(section,'[data-htp-tariff-tab]');
    if(!tabs.length)return;
    var index=tabs.indexOf(tab),next=index;
    if(event.key==='ArrowRight')next=(index+1)%tabs.length;
    if(event.key==='ArrowLeft')next=(index-1+tabs.length)%tabs.length;
    if(event.key==='Home')next=0;
    if(event.key==='End')next=tabs.length-1;
    event.preventDefault();
    activateTariff(section,String(tabs[next].getAttribute('data-htp-tariff-tab')||''),true);
  });
}());
