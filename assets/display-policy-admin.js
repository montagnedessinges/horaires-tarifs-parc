(function(){
  'use strict';
  var config=window.ParcsHTDisplayPolicy||{};
  function qs(root,sel){return (root||document).querySelector(sel);}
  function qsa(root,sel){return Array.prototype.slice.call((root||document).querySelectorAll(sel));}

  function mainForm(){
    var action=qs(document,'input[name="action"][value="parcs_ht_save"]');
    return action?action.closest('form'):null;
  }

  function addVisibilityControls(form){
    if(qs(form,'input[name="settings[general][public_display_from]"]'))return;
    var until=qs(form,'input[name="settings[general][public_display_until]"]');
    var end=qs(form,'input[name="settings[general][season_end]"]');
    var anchor=(until&&until.closest('.htp-field'))||(end&&end.closest('.htp-field'));
    if(!anchor||!anchor.parentNode)return;

    if(until){
      var small=until.closest('.htp-field').querySelector('small');
      if(small)small.textContent='Après cette date, la saison disparaît automatiquement du planning public. Les tarifs individuels ont en plus leur propre case Afficher.';
    }

    var from=document.createElement('label');
    from.className='htp-field htp-public-display-from';
    from.innerHTML='<span>Afficher au grand public à partir du</span><input type="date" name="settings[general][public_display_from]" value=""><small class="description">Date de publication de la saison publique. Laisser vide pour autoriser l’affichage dès publication.</small>';
    qs(from,'input').value=String(config.displayFrom||'');

    var force=document.createElement('label');
    force.className='htp-field htp-public-force-display';
    force.innerHTML='<span>Forçage public</span><span><input type="hidden" name="settings[general][public_force_display]" value="0"><input type="checkbox" name="settings[general][public_force_display]" value="1"> Afficher maintenant, même hors des dates prévues</span><small class="description">Prioritaire sur les dates de début et de fin. La saison doit tout de même être publiée.</small>';
    qs(force,'input[type="checkbox"]').checked=String(config.forceDisplay||'0')==='1';

    var retail=document.createElement('label');
    retail.className='htp-field htp-retail-tariffs-visible';
    retail.innerHTML='<span>Tarifs individuels / réduits</span><span><input type="hidden" name="settings[general][retail_tariffs_visible]" value="0"><input type="checkbox" name="settings[general][retail_tariffs_visible]" value="1"> Afficher les tarifs de cette année</span><small class="description">Indépendant des tarifs groupes. Si cette case est décochée, l’année n’apparaît pas dans les onglets des tarifs visiteurs.</small>';
    qs(retail,'input[type="checkbox"]').checked=String(config.retailTariffsVisible||'0')==='1';

    var groups=document.createElement('label');
    groups.className='htp-field htp-groups-schedule-visible';
    groups.innerHTML='<span>Horaires groupes</span><span><input type="hidden" name="settings[general][groups_schedule_visible]" value="0"><input type="checkbox" name="settings[general][groups_schedule_visible]" value="1"> Afficher cette année aux groupes</span><small class="description">Indépendant du grand public. Permet par exemple de montrer les horaires 2027 aux groupes pour préparer les devis.</small>';
    qs(groups,'input[type="checkbox"]').checked=String(config.groupsScheduleVisible||'0')==='1';

    anchor.parentNode.insertBefore(from,anchor.nextSibling);
    anchor.parentNode.insertBefore(force,from.nextSibling);
    anchor.parentNode.insertBefore(retail,force.nextSibling);
    anchor.parentNode.insertBefore(groups,retail.nextSibling);
  }

  function simplifyRetailColumns(){
    ['individual','reduced'].forEach(function(groupKey){
      var group=qs(document,'[data-htp-tariff-group="'+groupKey+'"]');
      if(!group)return;
      qsa(group,'[data-htp-tariff-column]').forEach(function(row,index){
        var id=qs(row,'[data-htp-column-id-input]');
        var raw=id?String(id.value||''):'';
        var channel=raw==='online'||raw==='onsite'?raw:(index===0?'online':'onsite');
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
      if(heading)heading.textContent='Tarifs En ligne / Sur place';
    });
  }

  function boot(){
    var form=mainForm();
    if(form)addVisibilityControls(form);
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
