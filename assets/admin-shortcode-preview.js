(function(){
  'use strict';

  var config=window.ParcsHTShortcodePreview||{};
  var rows=Array.isArray(config.rows)?config.rows:[];
  var STORAGE_BASE='parcs_ht_shortcode_preview_base';
  var STORAGE_LANG='parcs_ht_shortcode_preview_lang';
  var selectedBase='';
  var selectedLang='fr';
  var frame=null;
  var code=null;
  var label=null;
  var nav=null;
  var languageNav=null;
  var refresh=null;
  var status=null;

  function stored(key,fallback){
    try{return sessionStorage.getItem(key)||fallback;}catch(e){return fallback;}
  }
  function save(key,value){try{sessionStorage.setItem(key,value);}catch(e){}}
  function findRow(base){return rows.filter(function(row){return String(row.base||'')===base;})[0]||null;}
  function rowFor(base){return findRow(base)||rows[0]||null;}
  function currentDate(){var input=document.querySelector('[data-htp-preview-date]');return input&&input.value?input.value:String(config.defaultDate||'');}
  function currentTime(){var input=document.querySelector('[data-htp-preview-time]');return input&&input.value?input.value:String(config.defaultTime||'');}
  function currentBackground(){return '#ffffff';}

  function ensureTimeControl(){
    var controls=document.querySelector('.htp-preview-controls');
    var dateInput=document.querySelector('[data-htp-preview-date]');
    if(!controls||!dateInput)return;
    if(!dateInput.value&&config.defaultDate)dateInput.value=config.defaultDate;
    var timeInput=document.querySelector('[data-htp-preview-time]');
    if(!timeInput){
      var timeLabel=document.createElement('label');
      timeLabel.className='htp-field htp-preview-time-field';
      timeLabel.innerHTML='<span>Heure à tester</span><input type="time" data-htp-preview-time>';
      timeInput=timeLabel.querySelector('input');
      var button=controls.querySelector('[data-htp-preview-button]');
      if(button)controls.insertBefore(timeLabel,button);else controls.appendChild(timeLabel);
    }
    if(!timeInput.value&&config.defaultTime)timeInput.value=config.defaultTime;
  }

  function frameUrl(){
    var row=rowFor(selectedBase);
    if(!row)return 'about:blank';
    var params=new URLSearchParams();
    params.set('action',String(config.action||'parcs_ht_shortcode_preview_frame'));
    params.set('_ajax_nonce',String(config.nonce||''));
    params.set('base',String(row.base||''));
    params.set('lang',selectedLang);
    params.set('date',currentDate());
    params.set('time',currentTime());
    params.set('background',currentBackground());
    if(config.season)params.set('season',String(config.season));
    return String(config.ajaxUrl||window.ajaxurl||'')+'?'+params.toString();
  }

  function updateChrome(){
    var row=rowFor(selectedBase);
    if(!row)return;
    if(label)label.textContent=String(row.label||row.base||'Shortcode');
    if(code)code.textContent=(row.shortcodes&&row.shortcodes[selectedLang])?row.shortcodes[selectedLang]:('['+row.base+'_'+selectedLang+']');
    if(nav){
      nav.querySelectorAll('[data-htp-preview-base]').forEach(function(button){
        var active=button.getAttribute('data-htp-preview-base')===row.base;
        button.classList.toggle('is-active',active);
        button.setAttribute('aria-current',active?'true':'false');
      });
    }
    if(languageNav){
      languageNav.querySelectorAll('[data-htp-preview-lang]').forEach(function(button){
        var active=button.getAttribute('data-htp-preview-lang')===selectedLang;
        button.classList.toggle('button-primary',active);
        button.setAttribute('aria-selected',active?'true':'false');
      });
    }
  }

  function loadPreview(){
    var row=rowFor(selectedBase);
    if(!row||!frame)return;
    selectedBase=String(row.base||'');
    updateChrome();
    if(status)status.textContent='Chargement de l’aperçu…';
    if(refresh){refresh.disabled=true;refresh.textContent='Mise à jour…';}
    frame.style.backgroundColor=currentBackground();
    frame.src=frameUrl();
  }

  function selectBase(base){
    var row=findRow(base);
    if(!row)return;
    selectedBase=String(row.base||'');
    save(STORAGE_BASE,selectedBase);
    loadPreview();
  }

  function selectLanguage(language){
    if(['fr','en','de'].indexOf(language)===-1)return;
    selectedLang=language;
    save(STORAGE_LANG,language);
    loadPreview();
  }

  function buildWorkbench(section){
    var historyTitle=Array.prototype.find.call(section.querySelectorAll('h3'),function(el){return /Historique de sécurité/i.test(el.textContent||'');});
    var block=document.createElement('div');
    block.className='htp-real-shortcode-preview';
    block.innerHTML='<div class="htp-real-shortcode-preview-head">'+
      '<div><h3>Aperçu des shortcodes</h3><p>Choisissez un shortcode et une langue. Seul l’aperçu sélectionné est chargé.</p><p><strong>Conseil d’intégration :</strong> placez les shortcodes dans une section du site à fond blanc. Le fond du shortcode reste transparent afin de s’intégrer naturellement à la page.</p></div>'+
      '<div class="htp-real-shortcode-preview-tools">'+
        '<button type="button" class="button button-primary" data-htp-shortcode-preview-refresh>Mettre à jour l’aperçu</button>'+
      '</div></div>'+
      '<div class="htp-shortcode-preview-workbench">'+
        '<nav class="htp-shortcode-preview-nav" aria-label="Shortcodes à prévisualiser" data-htp-shortcode-preview-nav></nav>'+
        '<section class="htp-shortcode-preview-viewer">'+
          '<div class="htp-shortcode-preview-viewer-head"><div><strong data-htp-shortcode-preview-label></strong><br><code data-htp-preview-code></code></div><div class="htp-shortcode-preview-languages" role="tablist" aria-label="Langue de l’aperçu" data-htp-preview-languages></div></div>'+
          '<div class="htp-shortcode-preview-status" data-htp-preview-status aria-live="polite"></div>'+
          '<iframe class="htp-real-shortcode-preview-frame" title="Aperçu du shortcode" data-htp-preview-frame></iframe>'+
        '</section></div>';
    if(historyTitle)section.insertBefore(block,historyTitle);else section.appendChild(block);

    nav=block.querySelector('[data-htp-shortcode-preview-nav]');
    languageNav=block.querySelector('[data-htp-preview-languages]');
    frame=block.querySelector('[data-htp-preview-frame]');
    code=block.querySelector('[data-htp-preview-code]');
    label=block.querySelector('[data-htp-preview-label]');
    refresh=block.querySelector('[data-htp-shortcode-preview-refresh]');
    status=block.querySelector('[data-htp-preview-status]');

    rows.forEach(function(row){
      var button=document.createElement('button');
      button.type='button';
      button.className='htp-shortcode-preview-nav-button';
      button.textContent=String(row.label||row.base||'Shortcode');
      button.setAttribute('data-htp-preview-base',String(row.base||''));
      button.addEventListener('click',function(){selectBase(String(row.base||''));});
      nav.appendChild(button);
    });

    ['fr','en','de'].forEach(function(language){
      var button=document.createElement('button');
      button.type='button';
      button.className='button button-small';
      button.textContent=language.toUpperCase();
      button.setAttribute('role','tab');
      button.setAttribute('data-htp-preview-lang',language);
      button.addEventListener('click',function(){selectLanguage(language);});
      languageNav.appendChild(button);
    });

    refresh.addEventListener('click',loadPreview);
    frame.addEventListener('load',function(){
      if(status)status.textContent='';
      if(refresh){refresh.disabled=false;refresh.textContent='Mettre à jour l’aperçu';}
    });
  }

  function init(){
    var section=document.getElementById('htp-preview');
    if(!section||!rows.length||section.dataset.htpLazyShortcodePreview==='1')return;
    section.dataset.htpLazyShortcodePreview='1';
    ensureTimeControl();
    buildWorkbench(section);

    var storedBase=stored(STORAGE_BASE,'');
    var initialRow=findRow(storedBase)||rows[0];
    selectedBase=String((initialRow||{}).base||'');
    selectedLang=stored(STORAGE_LANG,'fr');
    if(['fr','en','de'].indexOf(selectedLang)===-1)selectedLang='fr';
    loadPreview();

    document.addEventListener('click',function(event){
      if(event.target.closest('[data-htp-preview-button]'))setTimeout(loadPreview,0);
    });
  }

  window.addEventListener('message',function(event){
    if(event.origin!==window.location.origin||!frame||event.source!==frame.contentWindow)return;
    var data=event.data||{};
    if(data.type!=='parcs-ht-preview-size')return;
    var height=Math.max(280,Math.min(2400,Number(data.height)||0));
    if(height)frame.style.height=height+'px';
  });

  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
}());
