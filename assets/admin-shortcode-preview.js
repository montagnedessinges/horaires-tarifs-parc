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

  function init(){
    var section=document.getElementById('htp-preview');
    var source=document.getElementById('parcs-ht-real-shortcode-preview-source');
    if(!section||!source||section.dataset.htpRealShortcodePreview==='1')return;
    section.dataset.htpRealShortcodePreview='1';

    var block=document.createElement('div');
    block.className='htp-real-shortcode-preview';
    block.innerHTML='<div class="htp-real-shortcode-preview-head">'+
      '<div><h3>Aperçu réel du shortcode complet</h3><p>Enregistrez vos réglages puis consultez ici le même rendu que le shortcode public.</p></div>'+
      '<label class="htp-real-shortcode-preview-bg"><span>Fond de l’aperçu</span><input type="color" value="'+storedColor()+'" data-htp-shortcode-preview-bg></label>'+
      '</div><div class="htp-real-shortcode-preview-canvas" data-htp-shortcode-preview-canvas></div>';

    var historyTitle=Array.prototype.find.call(section.querySelectorAll('h3'),function(el){return /Historique de sécurité/i.test(el.textContent||'');});
    if(historyTitle)section.insertBefore(block,historyTitle);else section.appendChild(block);

    var canvas=block.querySelector('[data-htp-shortcode-preview-canvas]');
    var picker=block.querySelector('[data-htp-shortcode-preview-bg]');
    canvas.style.backgroundColor=storedColor();

    while(source.firstChild)canvas.appendChild(source.firstChild);
    source.remove();

    picker.addEventListener('input',function(){
      canvas.style.backgroundColor=picker.value;
      saveColor(picker.value);
    });
  }

  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
}());
