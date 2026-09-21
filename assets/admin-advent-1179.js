(function($){
  'use strict';

  var pageCache={};
  var navigationRequest=null;
  var newPage='parcs-ht-advent-1179';

  function normalizeUrl(url){
    try{
      var parsed=new URL(url,window.location.href);
      if(parsed.origin!==window.location.origin)return parsed.toString();
      if(parsed.searchParams.get('page')==='parcs-horaires-tarifs'&&parsed.searchParams.get('tab')==='htp-advent'){
        parsed.searchParams.set('page',newPage);
        parsed.searchParams.delete('tab');
        parsed.searchParams.delete('season');
      }
      return parsed.toString();
    }catch(error){
      return url;
    }
  }

  function sameAdventPage(url){
    try{
      var parsed=new URL(normalizeUrl(url),window.location.href);
      return parsed.origin===window.location.origin&&parsed.searchParams.get('page')===newPage;
    }catch(error){return false;}
  }

  function updateMediaPreview(field,url){
    var preview=field.find('[data-advent-media-preview]');
    preview.empty();
    if(url){$('<img>',{src:url,alt:''}).appendTo(preview);preview.removeClass('is-empty').addClass('has-media');}
    else{$('<span>').text('Visuel 4:5 — à ajouter').appendTo(preview);preview.removeClass('has-media').addClass('is-empty');}
  }

  function syncClue(root){root.find('[data-advent-clue-fields]').prop('hidden',!root.find('[data-advent-clue-toggle]').is(':checked'));}
  function hydrateView(){$('[data-advent-clue-admin]').each(function(){syncClue($(this));});}

  function setBusy(busy){
    var root=document.querySelector('[data-htp-advent-workspace]');
    if(!root)return;
    root.classList.toggle('is-loading',!!busy);
    if(busy)root.setAttribute('aria-busy','true');else root.removeAttribute('aria-busy');
  }

  function replaceAdmin(html,url,pushHistory){
    var parser=new DOMParser();
    var doc=parser.parseFromString(html,'text/html');
    var incoming=doc.querySelector('[data-htp-advent-workspace]');
    var current=document.querySelector('[data-htp-advent-workspace]');
    if(!incoming||!current)throw new Error('Interface Calendrier de l\'Avent introuvable.');
    current.replaceWith(incoming);
    hydrateView();
    if(pushHistory&&window.history&&window.history.pushState)window.history.pushState({advent:true},'',url);
    window.scrollTo({top:Math.max(0,incoming.getBoundingClientRect().top+window.scrollY-40),behavior:'smooth'});
  }

  function loadAdventPage(url,pushHistory){
    var absolute=normalizeUrl(url);
    if(!sameAdventPage(absolute)){window.location.href=absolute;return;}
    if(pageCache[absolute]){try{replaceAdmin(pageCache[absolute],absolute,pushHistory);}catch(error){window.location.href=absolute;}return;}
    if(navigationRequest&&navigationRequest.abort)navigationRequest.abort();
    navigationRequest=new AbortController();
    setBusy(true);
    var requestUrl=new URL(absolute);
    requestUrl.searchParams.set('advent_fragment','1');
    fetch(requestUrl.toString(),{credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'},signal:navigationRequest.signal})
      .then(function(response){if(!response.ok)throw new Error('HTTP '+response.status);return response.text();})
      .then(function(html){pageCache[absolute]=html;replaceAdmin(html,absolute,pushHistory);})
      .catch(function(error){if(error&&error.name==='AbortError')return;window.location.href=absolute;})
      .finally(function(){navigationRequest=null;setBusy(false);});
  }

  $(document).on('click','[data-advent-media-select]',function(){
    var button=$(this),field=button.closest('[data-advent-media-field]'),kind=button.data('media-kind')||'image';
    var frame=wp.media({title:kind==='image'?'Choisir une image':'Choisir un média',multiple:false,library:kind==='image'?{type:'image'}:undefined});
    frame.on('select',function(){var item=frame.state().get('selection').first().toJSON();field.find('[data-advent-media-url]').val(item.url||'');field.find('[data-advent-media-source]').val('wordpress');updateMediaPreview(field,item.url||'');});
    frame.open();
  });

  $(document).on('click','[data-advent-media-clear]',function(){var field=$(this).closest('[data-advent-media-field]');field.find('[data-advent-media-url]').val('');field.find('[data-advent-media-source]').val('aucun');updateMediaPreview(field,'');});
  $(document).on('input','[data-advent-media-url]',function(){var field=$(this).closest('[data-advent-media-field]'),url=$(this).val().trim();if(url)field.find('[data-advent-media-source]').val('url');updateMediaPreview(field,url);});
  $(document).on('change','[data-advent-clue-toggle]',function(){syncClue($(this).closest('[data-advent-clue-admin]'));});

  $(document).on('click','[data-advent-copy-button]',function(){
    var button=$(this),source=button.closest('.htp-advent-copy-box').find('[data-advent-copy-source]').get(0);if(!source)return;
    var text=source.value||'';
    function success(){var old=button.text();button.text('Copié');window.setTimeout(function(){button.text(old);},1200);}
    if(navigator.clipboard&&window.isSecureContext)navigator.clipboard.writeText(text).then(success).catch(function(){source.select();document.execCommand('copy');success();});
    else{source.select();document.execCommand('copy');success();}
  });

  $(document).on('click','[data-htp-advent-workspace] a[href]',function(event){
    if(event.defaultPrevented||event.button!==0||event.metaKey||event.ctrlKey||event.shiftKey||event.altKey)return;
    var href=this.href||'';if(!sameAdventPage(href))return;event.preventDefault();loadAdventPage(href,true);
  });

  $(document).on('change','[data-advent-campaign-select]',function(){
    var select=$(this),base=normalizeUrl(select.data('base-url')||window.location.href);
    try{var url=new URL(base,window.location.origin);url.searchParams.set('campaign',select.val());loadAdventPage(url.toString(),true);}
    catch(error){window.location.href=base+'&campaign='+encodeURIComponent(select.val());}
  });

  $(document).on('submit','[data-htp-advent-workspace] form',function(){pageCache={};});
  window.addEventListener('popstate',function(){if(sameAdventPage(window.location.href))loadAdventPage(window.location.href,false);});
  hydrateView();
})(jQuery);
