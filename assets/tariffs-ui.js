(function(){
  'use strict';

  function qsa(root,selector){return Array.prototype.slice.call((root||document).querySelectorAll(selector));}

  function initTariffRoot(root){
    if(!root||root.dataset.htpUiReady==='1')return;
    root.dataset.htpUiReady='1';
    var tabs=qsa(root,'[data-htp-ui-tab]');
    function select(tab,focus){
      tabs.forEach(function(item){
        var active=item===tab;
        item.classList.toggle('is-active',active);
        item.setAttribute('aria-selected',active?'true':'false');
        item.tabIndex=active?0:-1;
        var panelId=item.getAttribute('aria-controls');
        var panel=panelId?document.getElementById(panelId):null;
        if(panel&&root.contains(panel))panel.hidden=!active;
      });
      if(focus&&tab)tab.focus();
    }
    tabs.forEach(function(tab,index){
      tab.addEventListener('click',function(){select(tab,false);});
      tab.addEventListener('keydown',function(event){
        if(event.key!=='ArrowRight'&&event.key!=='ArrowLeft'&&event.key!=='Home'&&event.key!=='End')return;
        event.preventDefault();
        var next=index;
        if(event.key==='ArrowRight')next=(index+1)%tabs.length;
        if(event.key==='ArrowLeft')next=(index-1+tabs.length)%tabs.length;
        if(event.key==='Home')next=0;
        if(event.key==='End')next=tabs.length-1;
        select(tabs[next],true);
      });
    });
  }

  function initYearRoot(root){
    if(!root||root.dataset.htpUiYearsReady==='1')return;
    root.dataset.htpUiYearsReady='1';
    var tabs=qsa(root,'[data-htp-ui-year]');
    var panels=qsa(root,'[data-htp-ui-year-panel]');
    function select(tab,focus){
      var year=String(tab.getAttribute('data-htp-ui-year')||'');
      tabs.forEach(function(item){
        var active=item===tab;
        item.classList.toggle('is-active',active);
        item.setAttribute('aria-selected',active?'true':'false');
        item.tabIndex=active?0:-1;
      });
      panels.forEach(function(panel){panel.hidden=String(panel.getAttribute('data-htp-ui-year-panel')||'')!==year;});
      if(focus)tab.focus();
    }
    tabs.forEach(function(tab,index){
      tab.addEventListener('click',function(){select(tab,false);});
      tab.addEventListener('keydown',function(event){
        if(event.key!=='ArrowRight'&&event.key!=='ArrowLeft'&&event.key!=='Home'&&event.key!=='End')return;
        event.preventDefault();
        var next=index;
        if(event.key==='ArrowRight')next=(index+1)%tabs.length;
        if(event.key==='ArrowLeft')next=(index-1+tabs.length)%tabs.length;
        if(event.key==='Home')next=0;
        if(event.key==='End')next=tabs.length-1;
        select(tabs[next],true);
      });
    });
  }

  function boot(root){
    qsa(root||document,'[data-htp-ui-tariffs]').forEach(initTariffRoot);
    qsa(root||document,'[data-htp-ui-years]').forEach(initYearRoot);
  }

  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',function(){boot(document);});else boot(document);
  document.addEventListener('parcsht:tariffs-updated',function(event){boot(event.detail&&event.detail.section?event.detail.section:document);});
}());
