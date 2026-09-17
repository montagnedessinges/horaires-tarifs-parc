(function(){
  'use strict';

  function rowIndex(row){
    var input=row.querySelector('[name*="settings[tariffs][payment_items]["]') || row.querySelector('[name*="payment_items"]');
    if(!input)return '';
    var match=String(input.name||'').match(/payment_items\]\[([^\]]+)\]/);
    return match?match[1]:'';
  }

  function defaultChannels(row){
    var icon=row.querySelector('select[name$="[icon]"]');
    var text=String(row.textContent||'').toLowerCase();
    var online=(icon&&icon.value==='card')||/(^|\s)(cb|carte bancaire|visa|mastercard|card)(\s|$)/i.test(text);
    return {onsite:true,online:online};
  }

  function hidden(name){
    var el=document.createElement('input');
    el.type='hidden';el.name=name;el.value='0';return el;
  }

  function checkbox(name,label,checked){
    var wrap=document.createElement('label');
    var box=document.createElement('input');
    box.type='checkbox';box.name=name;box.value='1';box.checked=!!checked;
    wrap.appendChild(hidden(name));wrap.appendChild(box);wrap.appendChild(document.createTextNode(' '+label));
    return wrap;
  }

  function enhance(row){
    if(!row||row.dataset.htpPaymentChannelsReady==='1')return;
    var index=rowIndex(row);if(index==='')return;
    row.dataset.htpPaymentChannelsReady='1';
    var config=(window.ParcsHTPaymentChannels&&window.ParcsHTPaymentChannels.items)||{};
    var state=config[index]||defaultChannels(row);
    var base='settings[tariffs][payment_items]['+index+'][channels]';
    var field=document.createElement('fieldset');
    field.className='htp-field htp-payment-channel-field';
    var legend=document.createElement('span');legend.textContent='Canaux acceptés';field.appendChild(legend);
    var choices=document.createElement('div');choices.className='htp-check-list';
    choices.appendChild(checkbox(base+'[onsite]','Sur place',state.onsite));
    choices.appendChild(checkbox(base+'[online]','En ligne',state.online));
    field.appendChild(choices);
    var grid=row.querySelector('.htp-grid');if(grid)grid.appendChild(field);
  }

  function boot(root){(root||document).querySelectorAll('.htp-payment-admin-row').forEach(enhance);}
  function start(){
    boot(document);
    var target=document.querySelector('.htp-payment-admin');
    if(!target||typeof MutationObserver==='undefined')return;
    new MutationObserver(function(records){records.forEach(function(record){record.addedNodes.forEach(function(node){if(node.nodeType===1)boot(node.matches&&node.matches('.htp-payment-admin-row')?node:node);});});}).observe(target,{childList:true,subtree:true});
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start);else start();
}());
