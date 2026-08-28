(function(){
  'use strict';
  var data=window.ParcsHTTariffOffers||{};
  function qs(root,sel){return (root||document).querySelector(sel);}
  function qsa(root,sel){return Array.prototype.slice.call((root||document).querySelectorAll(sel));}
  function esc(v){return String(v||'').replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];});}
  function groupPanel(panel,key){
    var rows=qsa(panel,'.parcs-ht-price-row');var meta=Array.isArray(data[key])?data[key]:[];if(!rows.length||!meta.length)return;
    var last='';
    rows.forEach(function(row,i){var m=meta[i]||{};var offer=m.special?String(m.offer||'').trim():'';if(!offer){last='';return;}row.classList.add('htp-offer-group-row');row.setAttribute('data-htp-offer-group',offer);if(offer!==last){var head=document.createElement('div');head.className='parcs-ht-offer-group-title';head.innerHTML='<strong>'+esc(offer)+'</strong>';row.parentNode.insertBefore(head,row);last=offer;}});
  }
  function boot(){qsa(document,'[data-htp-component="tariffs"] [data-htp-tariff-tab]').forEach(function(tab){var key=tab.getAttribute('data-htp-tariff-tab');var panel=qs(document,'#'+CSS.escape(tab.getAttribute('aria-controls')||''));if(panel)groupPanel(panel,key);});var style=document.createElement('style');style.textContent='.parcs-ht-offer-group-title{margin:18px 0 6px;padding:10px 12px;border-radius:8px;background:rgba(0,0,0,.05)}.htp-offer-group-row+.htp-offer-group-row{margin-top:0}';document.head.appendChild(style);}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
}());
