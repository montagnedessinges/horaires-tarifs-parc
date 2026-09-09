(function($){
  'use strict';

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

  function syncClue(root){
    var checked=root.find('[data-advent-clue-toggle]').is(':checked');
    root.find('[data-advent-clue-fields]').prop('hidden',!checked);
  }
  $('[data-advent-clue-admin]').each(function(){syncClue($(this));});
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

  $('[data-advent-campaign-select]').on('change',function(){
    var select=$(this);
    var base=select.data('base-url')||window.location.href;
    try{
      var url=new URL(base,window.location.origin);
      url.searchParams.set('campaign',select.val());
      window.location.href=url.toString();
    }catch(error){
      window.location.href=base+'&campaign='+encodeURIComponent(select.val());
    }
  });
})(jQuery);
