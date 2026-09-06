(function(){
  'use strict';

  var STORAGE_KEY='parcs_ht_shortcode_preview_bg';

  function storedColor(){
    try{
      var value=sessionStorage.getItem(STORAGE_KEY)||'#ffffff';
      return /^#[0-9a-f]{6}$/i.test(value)?value:'#ffffff';
    }catch(e){return '#ffffff';}
  }

  function saveColor(value){
    try{sessionStorage.setItem(STORAGE_KEY,value);}catch(e){}
  }

  function moveContent(source,canvas){
    while(source.firstChild)canvas.appendChild(source.firstChild);
  }

  function initGuidePreview(root){
    if(!root||root.dataset.htpPreviewGuideReady==='1')return;
    root.dataset.htpPreviewGuideReady='1';
    var cycle='all',lang='all';
    function apply(){
      root.querySelectorAll('[data-guide-card]').forEach(function(card){
        var cycleMatch=cycle==='all'||card.dataset.cycle===cycle;
        var langMatch=lang==='all'||(' '+card.dataset.languages+' ').indexOf(' '+lang+' ')!==-1;
        card.hidden=!(cycleMatch&&langMatch);
      });
    }
    function bind(selector,key){
      root.querySelectorAll(selector+' button[data-guide-'+key+']').forEach(function(button){
        button.addEventListener('click',function(){
          root.querySelectorAll(selector+' button[data-guide-'+key+']').forEach(function(item){item.classList.toggle('is-active',item===button);});
          if(key==='cycle')cycle=button.dataset.guideCycle;else lang=button.dataset.guideLanguage;
          apply();
        });
      });
    }
    bind('[data-guide-cycle-filters]','cycle');
    bind('[data-guide-language-filters]','language');
    root.querySelectorAll('[data-guide-info]').forEach(function(button){
      button.addEventListener('click',function(event){
        event.stopPropagation();
        var pop=button.parentNode.querySelector('[data-guide-info-pop]');
        if(!pop)return;
        var open=!pop.hidden;
        root.querySelectorAll('[data-guide-info-pop]').forEach(function(item){item.hidden=true;});
        root.querySelectorAll('[data-guide-info]').forEach(function(item){item.setAttribute('aria-expanded','false');});
        pop.hidden=open;button.setAttribute('aria-expanded',open?'false':'true');
      });
    });
  }

  function initGroupTariffPreview(root){
    if(!root||root.dataset.htpPreviewGroupTariffReady==='1')return;
    root.dataset.htpPreviewGroupTariffReady='1';
    root.querySelectorAll('[data-htp-group-year-tab]').forEach(function(button){
      button.addEventListener('click',function(){
        var year=button.getAttribute('data-htp-group-year-tab');
        root.querySelectorAll('[data-htp-group-year-tab]').forEach(function(item){item.setAttribute('aria-selected',item===button?'true':'false');});
        root.querySelectorAll('[data-htp-group-year-panel]').forEach(function(panel){panel.hidden=panel.getAttribute('data-htp-group-year-panel')!==year;});
      });
    });
  }

  function initCanvas(canvas){
    canvas.querySelectorAll('[data-htp-guides]').forEach(initGuidePreview);
    canvas.querySelectorAll('[data-htp-group-tariffs]').forEach(initGroupTariffPreview);
  }

  function previewCard(sources,color){
    var first=sources[0];
    var card=document.createElement('section');
    card.className='htp-shortcode-preview-item';
    var label=first.getAttribute('data-label')||'Shortcode';
    card.innerHTML='<div class="htp-shortcode-preview-item-head"><div><h4></h4><code data-htp-preview-code></code></div><div class="htp-shortcode-preview-languages" role="tablist" aria-label="Langue de l’aperçu"></div></div><div data-htp-preview-canvases></div>';
    card.querySelector('h4').textContent=label;
    var languageNav=card.querySelector('.htp-shortcode-preview-languages');
    var canvases=card.querySelector('[data-htp-preview-canvases]');
    var code=card.querySelector('[data-htp-preview-code]');

    function activate(language){
      card.querySelectorAll('[data-htp-preview-lang-button]').forEach(function(button){
        var active=button.getAttribute('data-htp-preview-lang-button')===language;
        button.classList.toggle('button-primary',active);button.setAttribute('aria-selected',active?'true':'false');
      });
      card.querySelectorAll('[data-htp-preview-language-canvas]').forEach(function(canvas){canvas.hidden=canvas.getAttribute('data-htp-preview-language-canvas')!==language;});
      var activeSource=sources.filter(function(source){return source.getAttribute('data-lang')===language;})[0]||first;
      code.textContent=activeSource.getAttribute('data-shortcode')||'';
    }

    ['fr','en','de'].forEach(function(language){
      var source=sources.filter(function(item){return item.getAttribute('data-lang')===language;})[0];
      if(!source)return;
      var button=document.createElement('button');button.type='button';button.className='button button-small';button.textContent=language.toUpperCase();button.setAttribute('data-htp-preview-lang-button',language);button.setAttribute('role','tab');button.addEventListener('click',function(){activate(language);});languageNav.appendChild(button);
      var canvas=document.createElement('div');canvas.className='htp-real-shortcode-preview-canvas';canvas.setAttribute('data-htp-shortcode-preview-canvas','');canvas.setAttribute('data-htp-preview-language-canvas',language);canvas.style.backgroundColor=color;moveContent(source,canvas);initCanvas(canvas);canvases.appendChild(canvas);
    });
    activate('fr');
    return card;
  }

  function init(){
    var section=document.getElementById('htp-preview');
    var sourcesRoot=document.getElementById('parcs-ht-real-shortcode-preview-sources');
    if(!section||!sourcesRoot||section.dataset.htpRealShortcodePreview==='1')return;
    section.dataset.htpRealShortcodePreview='1';

    var color=storedColor();
    var block=document.createElement('div');
    block.className='htp-real-shortcode-preview';
    block.innerHTML='<div class="htp-real-shortcode-preview-head">'+
      '<div><h3>Aperçus réels des shortcodes</h3><p>Tous les shortcodes du registre sont testables ici en FR, EN et DE. Après un enregistrement, cliquez sur « Mettre à jour les aperçus » pour recharger les vraies données.</p></div>'+
      '<div class="htp-real-shortcode-preview-tools">'+
        '<button type="button" class="button button-primary" data-htp-shortcode-preview-refresh>Mettre à jour les aperçus</button>'+
        '<label class="htp-real-shortcode-preview-bg"><span>Fond des aperçus</span><input type="color" value="'+color+'" data-htp-shortcode-preview-bg></label>'+
      '</div>'+
      '</div><div class="htp-shortcode-preview-list" data-htp-shortcode-preview-list></div>';

    var historyTitle=Array.prototype.find.call(section.querySelectorAll('h3'),function(el){return /Historique de sécurité/i.test(el.textContent||'');});
    if(historyTitle)section.insertBefore(block,historyTitle);else section.appendChild(block);

    var byBase={};
    Array.prototype.slice.call(sourcesRoot.querySelectorAll('[data-htp-shortcode-preview-source]')).forEach(function(source){
      var base=source.getAttribute('data-base')||source.getAttribute('data-shortcode')||'shortcode';
      if(!byBase[base])byBase[base]=[];
      byBase[base].push(source);
    });
    var list=block.querySelector('[data-htp-shortcode-preview-list]');
    Object.keys(byBase).forEach(function(base){list.appendChild(previewCard(byBase[base],color));});
    sourcesRoot.remove();

    var picker=block.querySelector('[data-htp-shortcode-preview-bg]');
    picker.addEventListener('input',function(){
      var value=picker.value;
      block.querySelectorAll('[data-htp-shortcode-preview-canvas]').forEach(function(canvas){canvas.style.backgroundColor=value;});
      saveColor(value);
    });

    var refresh=block.querySelector('[data-htp-shortcode-preview-refresh]');
    refresh.addEventListener('click',function(){refresh.disabled=true;refresh.textContent='Mise à jour…';window.location.reload();});
  }

  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
}());
