(function(){
  'use strict';
  var config=window.ParcsHTPublicVisibility||{};
  function fieldExists(name){return !!document.querySelector('[name="'+name+'"]');}
  function addField(grid,label,name,type,value,options){
    if(!grid||fieldExists(name))return;
    var wrap=document.createElement('label');wrap.className='htp-year-control';
    var text=document.createElement('span');text.textContent=label;wrap.appendChild(text);
    var input;
    if(type==='select'){
      input=document.createElement('select');
      (options||[]).forEach(function(item){var option=document.createElement('option');option.value=item.value;option.textContent=item.label;if(String(value)===String(item.value))option.selected=true;input.appendChild(option);});
    }else{
      input=document.createElement('input');input.type=type;input.value=value||'';
    }
    input.name=name;wrap.appendChild(input);grid.appendChild(wrap);
  }
  function boot(){
    var grid=document.querySelector('.htp-year-controls-grid');if(!grid)return;
    addField(grid,'Afficher cette année à partir du','settings[general][public_display_from]','date',config.from||'');
    addField(grid,'Masquer cette année après le','settings[general][public_display_until]','date',config.until||'');
    addField(grid,'Forcer l’affichage sans tenir compte de ces dates','settings[general][public_force_display]','select',String(config.force||'0'),[
      {value:'0',label:'NON — respecter les dates'},
      {value:'1',label:'OUI — forcer l’affichage'}
    ]);
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
}());
