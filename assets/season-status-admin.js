(function(){
  'use strict';

  function hideLegacyPublication(){
    var form=document.querySelector('input[name="action"][value="parcs_ht_save"]');
    form=form?form.closest('form'):null;
    if(!form)return;
    var field=form.querySelector('input[name="settings[general][published]"]');
    if(!field)return;
    var label=field.closest('.htp-field');
    if(label)label.remove();else field.remove();
  }

  function boot(){
    hideLegacyPublication();
  }

  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
}());
