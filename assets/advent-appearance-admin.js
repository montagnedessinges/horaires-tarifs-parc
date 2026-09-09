(function(){
  'use strict';

  var config=window.ParcsHTAdventAppearance||{};
  var campaigns=config.campaigns||{};
  var pickerDefaults=config.pickerDefaults||{};
  var scheduled=false;

  var fields=[
    {key:'primary',label:'Couleur principale',help:'Titres, liens, sélection et boutons principaux.'},
    {key:'secondary',label:'Couleur secondaire',help:'Boutons secondaires et éléments d’accompagnement.'},
    {key:'open_day',label:'Cases ouvertes',help:'Bordure et léger fond des cases déjà accessibles.'},
    {key:'today',label:'Case du jour',help:'Mise en avant de la case correspondant à la date du jour.'},
    {key:'locked',label:'Cases verrouillées',help:'Bordure et léger fond des cases pas encore ouvertes.'},
    {key:'special',label:'Indice & grand jeu',help:'Indices, révélation et bloc du grand jeu final.'}
  ];

  function escapeHtml(value){
    return String(value==null?'':value).replace(/[&<>"]/g,function(character){
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[character];
    });
  }

  function currentView(){
    try{
      var url=new URL(window.location.href);
      return url.searchParams.get('advent_view')||'campaign';
    }catch(error){
      return 'campaign';
    }
  }

  function currentCampaignId(root){
    var select=root?root.querySelector('[data-advent-campaign-select]'):null;
    if(select&&select.value)return select.value;
    try{
      var url=new URL(window.location.href);
      return url.searchParams.get('campaign')||'';
    }catch(error){
      return '';
    }
  }

  function paletteFor(campaignId){
    var saved=campaigns[campaignId]||{};
    var palette={};
    fields.forEach(function(field){palette[field.key]=saved[field.key]||'';});
    return palette;
  }

  function fieldHtml(field,palette){
    var custom=!!palette[field.key];
    var picker=palette[field.key]||pickerDefaults[field.key]||'#000000';
    return '<div class="htp-advent-color-setting" data-advent-color-setting="'+escapeHtml(field.key)+'">'+
      '<div class="htp-advent-color-copy"><strong>'+escapeHtml(field.label)+'</strong><span>'+escapeHtml(field.help)+'</span></div>'+
      '<label class="htp-advent-color-control">'+
        '<input type="checkbox" data-advent-color-enabled="'+escapeHtml(field.key)+'" '+(custom?'checked':'')+'>'+
        '<span>Personnaliser</span>'+
        '<input type="color" data-advent-color-picker="'+escapeHtml(field.key)+'" value="'+escapeHtml(picker)+'" '+(custom?'':'disabled')+'>'+
      '</label>'+
    '</div>';
  }

  function setMessage(card,text,isError){
    var message=card.querySelector('[data-advent-appearance-message]');
    if(!message)return;
    message.textContent=text||'';
    message.classList.toggle('is-error',!!isError);
    message.hidden=!text;
  }

  function setBusy(card,busy){
    card.classList.toggle('is-saving',!!busy);
    card.querySelectorAll('button,input').forEach(function(control){
      if(control.matches('[data-advent-color-picker]')){
        var key=control.getAttribute('data-advent-color-picker');
        var enabled=card.querySelector('[data-advent-color-enabled="'+key+'"]');
        control.disabled=!!busy||!(enabled&&enabled.checked);
      }else{
        control.disabled=!!busy;
      }
    });
  }

  function syncPickerState(card,key){
    var enabled=card.querySelector('[data-advent-color-enabled="'+key+'"]');
    var picker=card.querySelector('[data-advent-color-picker="'+key+'"]');
    if(!enabled||!picker)return;
    picker.disabled=!enabled.checked||card.classList.contains('is-saving');
  }

  function requestSave(card,campaignId,mode){
    if(!config.ajaxUrl||!config.action||!config.nonce)return;
    var resetMode=mode==='reset';
    var body=new FormData();
    body.append('action',config.action);
    body.append('nonce',config.nonce);
    body.append('campaign_id',campaignId);
    body.append('mode',resetMode?'reset':'save');
    if(!resetMode){
      fields.forEach(function(field){
        var enabled=card.querySelector('[data-advent-color-enabled="'+field.key+'"]');
        var picker=card.querySelector('[data-advent-color-picker="'+field.key+'"]');
        body.append('colors['+field.key+']',enabled&&enabled.checked&&picker?picker.value:'');
      });
    }
    setBusy(card,true);
    setMessage(card,'',false);
    fetch(config.ajaxUrl,{method:'POST',credentials:'same-origin',body:body})
      .then(function(response){return response.json();})
      .then(function(payload){
        if(!payload||!payload.success)throw new Error(payload&&payload.data&&payload.data.message?payload.data.message:'Enregistrement impossible.');
        campaigns[campaignId]=payload.data.settings||{};
        renderCard(card,campaignId);
        setMessage(card,payload.data.message||'Couleurs enregistrées.',false);
      })
      .catch(function(error){setMessage(card,error&&error.message?error.message:'Enregistrement impossible.',true);})
      .finally(function(){setBusy(card,false);});
  }

  function renderCard(card,campaignId){
    var palette=paletteFor(campaignId);
    card.setAttribute('data-campaign-id',campaignId);
    card.innerHTML=
      '<div class="htp-advent-appearance-head">'+
        '<div><h2>Couleurs du Calendrier de l’Avent</h2><p class="description">Ces couleurs sont propres à cette campagne. Une couleur désactivée reprend automatiquement l’apparence actuelle de l’extension.</p></div>'+
      '</div>'+
      '<div class="htp-advent-color-grid">'+fields.map(function(field){return fieldHtml(field,palette);}).join('')+'</div>'+
      '<div class="htp-advent-appearance-actions">'+
        '<button type="button" class="button button-primary" data-advent-appearance-save>Enregistrer les couleurs</button>'+
        '<button type="button" class="button" data-advent-appearance-reset>Revenir aux couleurs héritées</button>'+
        '<span class="htp-advent-appearance-message" data-advent-appearance-message hidden></span>'+
      '</div>';
  }

  function ensureCard(){
    scheduled=false;
    var root=document.querySelector('[data-htp-advent-workspace]');
    var existing=document.getElementById('htp-advent-appearance-card');
    if(!root||currentView()!=='campaign'){
      if(existing)existing.remove();
      return;
    }
    var campaignId=currentCampaignId(root);
    var host=root.querySelector('[data-htp-advent-view]');
    if(!campaignId||!host){
      if(existing)existing.remove();
      return;
    }
    if(existing){
      if(existing.getAttribute('data-campaign-id')!==campaignId)renderCard(existing,campaignId);
      return;
    }
    var card=document.createElement('section');
    card.id='htp-advent-appearance-card';
    card.className='htp-advent-card htp-advent-appearance-card';
    renderCard(card,campaignId);
    host.appendChild(card);
  }

  function scheduleEnsure(){
    if(scheduled)return;
    scheduled=true;
    window.requestAnimationFrame(ensureCard);
  }

  document.addEventListener('change',function(event){
    var enabled=event.target.closest('[data-advent-color-enabled]');
    if(!enabled)return;
    var card=enabled.closest('#htp-advent-appearance-card');
    if(!card)return;
    syncPickerState(card,enabled.getAttribute('data-advent-color-enabled'));
  });

  document.addEventListener('click',function(event){
    var save=event.target.closest('[data-advent-appearance-save]');
    var reset=event.target.closest('[data-advent-appearance-reset]');
    if(!save&&!reset)return;
    var card=event.target.closest('#htp-advent-appearance-card');
    if(!card)return;
    var campaignId=card.getAttribute('data-campaign-id')||'';
    if(!campaignId)return;
    requestSave(card,campaignId,reset?'reset':'save');
  });

  window.addEventListener('popstate',scheduleEnsure);
  document.addEventListener('DOMContentLoaded',scheduleEnsure);
  new MutationObserver(scheduleEnsure).observe(document.documentElement,{childList:true,subtree:true});
  scheduleEnsure();
})();
