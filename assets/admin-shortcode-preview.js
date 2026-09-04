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
          root.querySelectorAll(selector+' button[data-guide-'+key+']').forEach(function(item){
            item.classList.toggle('is-active',item===button);
          });
          if(key==='cycle')cycle=button.dataset.guideCycle;
          else lang=button.dataset.guideLanguage;
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
        pop.hidden=open;
        button.setAttribute('aria-expanded',open?'false':'true');
      });
    });
  }

  function previewCard(source,color){
    var card=document.createElement('section');
    card.className='htp-shortcode-preview-item';
    var label=source.getAttribute('data-label')||'Shortcode';
    var shortcode=source.getAttribute('data-shortcode')||'';
    card.innerHTML='<div class="htp-shortcode-preview-item-head"><div><h4></h4><code></code></div></div><div class="htp-real-shortcode-preview-canvas" data-htp-shortcode-preview-canvas></div>';
    card.querySelector('h4').textContent=label;
    card.querySelector('code').textContent=shortcode;
    var canvas=card.querySelector('[data-htp-shortcode-preview-canvas]');
    canvas.style.backgroundColor=color;
    moveContent(source,canvas);
    canvas.querySelectorAll('[data-htp-guides]').forEach(initGuidePreview);
    return card;
  }

  function init(){
    var section=document.getElementById('htp-preview');
    var sources=document.getElementById('parcs-ht-real-shortcode-preview-sources');
    if(!section||!sources||section.dataset.htpRealShortcodePreview==='1')return;
    section.dataset.htpRealShortcodePreview='1';

    var color=storedColor();
    var block=document.createElement('div');
    block.className='htp-real-shortcode-preview';
    block.innerHTML='<div class="htp-real-shortcode-preview-head">'+
      '<div><h3>Aperçus réels des shortcodes</h3><p>Après avoir enregistré vos modifications, cliquez sur « Mettre à jour les aperçus » pour régénérer tous les shortcodes avec les dernières données enregistrées.</p></div>'+
      '<div class="htp-real-shortcode-preview-tools">'+
        '<button type="button" class="button button-primary" data-htp-shortcode-preview-refresh>Mettre à jour les aperçus</button>'+
        '<label class="htp-real-shortcode-preview-bg"><span>Fond des aperçus</span><input type="color" value="'+color+'" data-htp-shortcode-preview-bg></label>'+
      '</div>'+
      '</div><div class="htp-shortcode-preview-list" data-htp-shortcode-preview-list></div>';

    var historyTitle=Array.prototype.find.call(section.querySelectorAll('h3'),function(el){return /Historique de sécurité/i.test(el.textContent||'');});
    if(historyTitle)section.insertBefore(block,historyTitle);else section.appendChild(block);

    var list=block.querySelector('[data-htp-shortcode-preview-list]');
    Array.prototype.slice.call(sources.querySelectorAll('[data-htp-shortcode-preview-source]')).forEach(function(source){
      list.appendChild(previewCard(source,color));
    });
    sources.remove();

    var picker=block.querySelector('[data-htp-shortcode-preview-bg]');
    picker.addEventListener('input',function(){
      var value=picker.value;
      block.querySelectorAll('[data-htp-shortcode-preview-canvas]').forEach(function(canvas){canvas.style.backgroundColor=value;});
      saveColor(value);
    });

    var refresh=block.querySelector('[data-htp-shortcode-preview-refresh]');
    refresh.addEventListener('click',function(){
      refresh.disabled=true;
      refresh.textContent='Mise à jour…';
      window.location.reload();
    });
  }

  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
}());
