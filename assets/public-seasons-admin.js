(function(){
  'use strict';
  var config=window.ParcsHTPublicSeasons||{};

  function qs(root,selector){return (root||document).querySelector(selector);}

  function mainForm(){
    var action=qs(document,'input[name="action"][value="parcs_ht_save"]');
    return action?action.closest('form'):null;
  }

  function buildField(form){
    if(qs(form,'input[name="settings[general][public_display_until]"]'))return;
    var anchor=qs(form,'input[name="settings[general][season_end]"]')||qs(form,'input[name="settings[general][season_start]"]');
    if(!anchor)return;
    var anchorField=anchor.closest('.htp-field')||anchor.parentNode;
    if(!anchorField||!anchorField.parentNode)return;

    var label=document.createElement('label');
    label.className='htp-field htp-public-display-until';
    label.innerHTML='<span>Afficher cette année jusqu’au</span><input type="date" name="settings[general][public_display_until]" value=""><small class="description">Après cette date, la saison disparaît automatiquement des onglets publics (horaires, tarifs et tarifs groupes). Laisser vide pour ne pas définir de date de retrait.</small>';
    var input=qs(label,'input');
    input.value=String(config.displayUntil||'');
    anchorField.parentNode.insertBefore(label,anchorField.nextSibling);
  }

  function boot(){
    var form=mainForm();
    if(!form)return;
    buildField(form);
  }

  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
}());
