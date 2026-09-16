(function(){
  'use strict';
  var config=window.ParcsHTPublicVisibility||{};

  function existingInput(name){
    return document.querySelector('[name="'+name+'"]');
  }

  function makeDateField(grid,label,name,value,description){
    if(!grid)return;
    var input=existingInput(name),oldWrap=input?input.closest('label'):null;
    if(!input){
      input=document.createElement('input');
      input.type='date';
      input.name=name;
      input.value=value||'';
    }else{
      input.type='date';
      if(!input.value&&value)input.value=value;
      if(oldWrap&&oldWrap.parentNode)oldWrap.parentNode.removeChild(oldWrap);
    }

    var wrap=document.createElement('label');
    wrap.className='htp-year-control htp-year-control-date';
    var text=document.createElement('span');text.textContent=label;wrap.appendChild(text);
    wrap.appendChild(input);
    if(description){var help=document.createElement('small');help.className='description';help.textContent=description;wrap.appendChild(help);}
    grid.appendChild(wrap);
  }

  function removeLegacyForce(){
    var input=existingInput('settings[general][public_force_display]');
    if(!input)return;
    var wrap=input.closest('label')||input.parentNode;
    if(wrap&&wrap.parentNode)wrap.parentNode.removeChild(wrap);
  }

  function boot(){
    var grid=document.querySelector('.htp-year-controls-grid');if(!grid)return;
    makeDateField(
      grid,
      'Activer automatiquement toute l’année à partir du',
      'settings[general][public_display_from]',
      config.from||'',
      'À partir de cette date, calendrier, tarifs visiteurs, horaires groupes, devis groupes et tarifs groupes sont affichés même si leur interrupteur est sur NON.'
    );
    makeDateField(
      grid,
      'Désactiver automatiquement toute l’année à partir du',
      'settings[general][public_display_until]',
      config.until||'',
      'À partir de cette date, toute l’année disparaît du site même si ses interrupteurs sont sur OUI.'
    );
    removeLegacyForce();
  }

  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
}());
