(function(){
  'use strict';
  var config=window.ParcsHTDisplayPolicy||{};
  function qs(root,sel){return (root||document).querySelector(sel);}
  function qsa(root,sel){return Array.prototype.slice.call((root||document).querySelectorAll(sel));}

  function mainForm(){
    var action=qs(document,'input[name="action"][value="parcs_ht_save"]');
    return action?action.closest('form'):null;
  }

  function simplifyRetailColumns(){
    ['individual','reduced'].forEach(function(groupKey){
      var group=qs(document,'[data-htp-tariff-group="'+groupKey+'"]');
      if(!group)return;
      qsa(group,'[data-htp-tariff-column]').forEach(function(row,index){
        var id=qs(row,'[data-htp-column-id-input]');
        var raw=id?String(id.value||''):'';
        var channel=raw==='online'||raw==='onsite'?raw:(index===0?'onsite':'online');
        var name=channel==='online'?'En ligne':'Sur place';
        qsa(row,'input[name*="[label]"]').forEach(function(input){input.readOnly=true;});
        var labelWrap=qs(row,'.htp-tariff-column-label');
        if(labelWrap){
          labelWrap.setAttribute('data-fixed-channel',channel);
          var first=qs(labelWrap,'.htp-field > span');
          if(first)first.textContent=name+' — libellé fixe';
        }
        qsa(row,'[data-htp-remove-column],.htp-sort-handle-column,.htp-order-buttons,[data-htp-column-visible]').forEach(function(el){el.hidden=true;});
      });
      qsa(group,'button').forEach(function(btn){
        if(/ajouter.*colonne/i.test(String(btn.textContent||'')))btn.hidden=true;
      });
      var heading=qs(group,'.htp-tariff-columns h4,.htp-tariff-columns h3');
      if(heading)heading.textContent='Tarifs Sur place / En ligne';
    });
  }

  function boot(){
    var form=mainForm();

    simplifyRetailColumns();
    var root=qs(document,'#htp-tariffs');
    if(root){
      var pending=false;
      new MutationObserver(function(mutations){
        var relevant=mutations.some(function(m){return Array.prototype.some.call(m.addedNodes||[],function(n){return n&&n.nodeType===1&&((n.matches&&n.matches('[data-htp-tariff-column],[data-htp-tariff-row]'))||(n.querySelector&&n.querySelector('[data-htp-tariff-column],[data-htp-tariff-row]')));});});
        if(!relevant||pending)return;
        pending=true;
        window.requestAnimationFrame(function(){pending=false;simplifyRetailColumns();});
      }).observe(root,{childList:true,subtree:true});
    }
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
}());
