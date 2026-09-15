(function(){
  'use strict';
  function qsa(root,sel){return Array.prototype.slice.call((root||document).querySelectorAll(sel));}
  function clean(v){return String(v||'').replace(/\s+/g,' ').trim();}

  function enhancePanel(panel){
    if(!panel||panel.getAttribute('data-htp-retail-channels-ready')==='1')return;
    panel.setAttribute('data-htp-retail-channels-ready','1');
    var head=panel.querySelector('.parcs-ht-price-head');
    var labels=[];
    if(head){
      labels=qsa(head,'span').slice(1).map(function(el){return clean(el.textContent);});
      head.remove();
    }
    if(!labels.length)labels=['En ligne','Sur place'];
    qsa(panel,'.parcs-ht-price-row').forEach(function(row){
      var cells=qsa(row,'.parcs-ht-price-cell');
      cells.forEach(function(cell,index){
        var value=cell.querySelector('b');
        if(!value||clean(value.textContent)===''){
          cell.hidden=true;
          return;
        }
        if(!cell.querySelector('.parcs-ht-price-channel-label')){
          var label=document.createElement('small');
          label.className='parcs-ht-price-channel-label';
          label.textContent=labels[index]||'';
          cell.insertBefore(label,cell.firstChild);
        }
      });
      var values=row.querySelector('.parcs-ht-price-values');
      if(values){
        values.style.setProperty('--htp-visible-price-count',String(cells.filter(function(cell){return !cell.hidden;}).length||1));
      }
    });
  }

  function boot(root){
    qsa(root||document,'.parcs-ht-tariff-panel').forEach(function(panel){
      var id=String(panel.id||'');
      if(/-panel-(individual|reduced)$/.test(id))enhancePanel(panel);
    });
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',function(){boot(document);});else boot(document);
  document.addEventListener('parcsht:tariffs-updated',function(event){boot(event.detail&&event.detail.section?event.detail.section:document);});
}());
