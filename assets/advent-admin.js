(function($){
  'use strict';

  var pageCache={};
  var navigationRequest=null;

  function updateMediaPreview(field,url){
    var preview=field.find('[data-advent-media-preview]');
    preview.empty();
    if(url){
      $('<img>',{src:url,alt:''}).appendTo(preview);
      preview.removeClass('is-empty').addClass('has-media');
    }else{
      $('<span>').text('Visuel 4:5 — à ajouter').appendTo(preview);
      preview.removeClass('has-media').addClass('is-empty');
    }
  }

  function syncClue(root){
    var checked=root.find('[data-advent-clue-toggle]').is(':checked');
    root.find('[data-advent-clue-fields]').prop('hidden',!checked);
  }

  function hydrateView(){
    $('[data-advent-clue-admin]').each(function(){syncClue($(this));});
  }

  function sameAdventPage(url){
    try{
      var parsed=new URL(url,window.location.href);
      return parsed.origin===window.location.origin&&parsed.searchParams.get('page')==='parcs-ht-advent';
    }catch(error){
      return false;
    }
  }

  function setBusy(busy){
    var root=document.querySelector('.htp-advent-admin');
    if(!root)return;
    root.classList.toggle('is-loading',!!busy);
    if(busy)root.setAttribute('aria-busy','true');
    else root.removeAttribute('aria-busy');
  }

  function replaceAdmin(html,url,pushHistory){
    var parser=new DOMParser();
    var doc=parser.parseFromString(html,'text/html');
    var incoming=doc.querySelector('.htp-advent-admin');
    var current=document.querySelector('.htp-advent-admin');
    if(!incoming||!current)throw new Error('Interface Calendrier de l\'Avent introuvable.');
    current.innerHTML=incoming.innerHTML;
    current.className=incoming.className;
    current.removeAttribute('aria-busy');
    hydrateView();
    if(pushHistory&&window.history&&window.history.pushState){
      window.history.pushState({advent:true},'',url);
    }
    window.scrollTo({top:Math.max(0,current.getBoundingClientRect().top+window.scrollY-40),behavior:'smooth'});
  }

  function loadAdventPage(url,pushHistory){
    if(!sameAdventPage(url)){
      window.location.href=url;
      return;
    }
    var absolute=new URL(url,window.location.href).toString();
    if(pageCache[absolute]){
      try{replaceAdmin(pageCache[absolute],absolute,pushHistory);}catch(error){window.location.href=absolute;}
      return;
    }
    if(navigationRequest&&navigationRequest.abort)navigationRequest.abort();
    navigationRequest=new AbortController();
    setBusy(true);
    fetch(absolute,{
      credentials:'same-origin',
      headers:{'X-Requested-With':'XMLHttpRequest'},
      signal:navigationRequest.signal
    }).then(function(response){
      if(!response.ok)throw new Error('HTTP '+response.status);
      return response.text();
    }).then(function(html){
      pageCache[absolute]=html;
      replaceAdmin(html,absolute,pushHistory);
    }).catch(function(error){
      if(error&&error.name==='AbortError')return;
      window.location.href=absolute;
    }).finally(function(){
      navigationRequest=null;
      setBusy(false);
    });
  }

  $(document).on('click','[data-advent-media-select]',function(){
    var button=$(this);
    var field=button.closest('[data-advent-media-field]');
    var kind=button.data('media-kind')||'image';
    var frame=wp.media({
      title:kind==='image'?'Choisir une image':'Choisir un média',
      multiple:false,
      library:kind==='image'?{type:'image'}:undefined
    });
    frame.on('select',function(){
      var item=frame.state().get('selection').first().toJSON();
      field.find('[data-advent-media-url]').val(item.url||'');
      field.find('[data-advent-media-source]').val('wordpress');
      updateMediaPreview(field,item.url||'');
    });
    frame.open();
  });

  $(document).on('click','[data-advent-media-clear]',function(){
    var field=$(this).closest('[data-advent-media-field]');
    field.find('[data-advent-media-url]').val('');
    field.find('[data-advent-media-source]').val('aucun');
    updateMediaPreview(field,'');
  });

  $(document).on('input','[data-advent-media-url]',function(){
    var field=$(this).closest('[data-advent-media-field]');
    var url=$(this).val().trim();
    if(url)field.find('[data-advent-media-source]').val('url');
    updateMediaPreview(field,url);
  });

  $(document).on('change','[data-advent-clue-toggle]',function(){syncClue($(this).closest('[data-advent-clue-admin]'));});

  $(document).on('click','[data-advent-copy-button]',function(){
    var button=$(this);
    var source=button.closest('.htp-advent-copy-box').find('[data-advent-copy-source]').get(0);
    if(!source)return;
    var text=source.value||'';
    function success(){
      var old=button.text();
      button.text('Copié');
      window.setTimeout(function(){button.text(old);},1200);
    }
    if(navigator.clipboard&&window.isSecureContext){
      navigator.clipboard.writeText(text).then(success).catch(function(){source.select();document.execCommand('copy');success();});
    }else{
      source.select();
      document.execCommand('copy');
      success();
    }
  });

  $(document).on('click','.htp-advent-admin a[href]',function(event){
    if(event.defaultPrevented||event.button!==0||event.metaKey||event.ctrlKey||event.shiftKey||event.altKey)return;
    var link=this;
    var href=link.href||'';
    if(!sameAdventPage(href))return;
    event.preventDefault();
    loadAdventPage(href,true);
  });

  $(document).on('change','[data-advent-campaign-select]',function(){
    var select=$(this);
    var base=select.data('base-url')||window.location.href;
    try{
      var url=new URL(base,window.location.origin);
      url.searchParams.set('campaign',select.val());
      loadAdventPage(url.toString(),true);
    }catch(error){
      window.location.href=base+'&campaign='+encodeURIComponent(select.val());
    }
  });

  $(document).on('submit','.htp-advent-admin form',function(){
    pageCache={};
  });

  window.addEventListener('popstate',function(){
    if(sameAdventPage(window.location.href))loadAdventPage(window.location.href,false);
  });

  hydrateView();
})(jQuery);
