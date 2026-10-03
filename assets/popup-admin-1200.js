(function($){
  'use strict';

  function pageIsPeriods(){
    try { return new URL(window.location.href).searchParams.get('page') === 'parcs-ht-periods'; }
    catch(e) { return false; }
  }

  function cleanupLegacyPopupControls(){
    if(!pageIsPeriods()) return;
    $('.htp-popup-block').remove();
    $('.htp-1174-card-head .description').each(function(){
      var text=$(this).text();
      if(text.indexOf('éventuel pop-up')!==-1){
        $(this).text(text.replace(', sa couleur et son éventuel pop-up', ' et sa couleur'));
      }
    });
  }

  function rowIndex(row){
    return String(row.attr('data-popup1200-index')||'');
  }

  function replaceIndex(root, oldIndex, newIndex){
    root.attr('data-popup1200-index', newIndex);
    root.find('[name]').each(function(){
      var name=String($(this).attr('name')||'');
      $(this).attr('name', name.replace('popups['+oldIndex+']','popups['+newIndex+']'));
    });
  }

  function syncRow(row){
    var enabled=row.find('[data-popup1200-enabled]').is(':checked');
    var published=String(row.find('[data-popup1200-published]').val()||'0')==='1';
    var name=$.trim(row.find('[data-popup1200-name]').val()||'')||'Nouveau pop-up';
    row.find('[data-popup1200-summary]').text(name);
    row.find('.htp-1200-summary span').text(enabled?(published?'Activé · publié':'Activé · brouillon'):'Désactivé');

    var mode=String(row.find('[data-popup1200-reappear]').val()||'hours');
    row.find('[data-popup1200-hours-wrap]').prop('hidden',mode==='once');

    var size=String(row.find('[data-popup1200-size]').val()||'medium');
    row.find('[data-popup1200-custom-width]').prop('hidden',size!=='custom');

    row.find('[data-popup1200-media]').each(function(){
      var media=$(this);
      var has=media.find('[data-popup1200-media-preview] img').length>0;
      media.find('[data-popup1200-media-clear]').prop('hidden',!has);
    });
  }

  function syncAll(){
    $('[data-popup1200-row]').each(function(){syncRow($(this));});
  }

  function updatePreview(media,url){
    var preview=media.find('[data-popup1200-media-preview]');
    preview.empty();
    if(url){
      $('<img>',{src:url,alt:''}).appendTo(preview);
      preview.addClass('has-image');
    }else{
      $('<span>').text('Aucune image').appendTo(preview);
      preview.removeClass('has-image');
    }
    syncRow(media.closest('[data-popup1200-row]'));
  }

  function uniqueIndex(){
    return String(Date.now())+String(Math.floor(Math.random()*10000));
  }

  function addRow(){
    var template=$('[data-popup1200-template]').html();
    if(!template) return;
    var index=uniqueIndex();
    var html=template.replaceAll('__INDEX__',index);
    var row=$(html);
    $('[data-popup1200-list]').append(row);
    syncRow(row);
    row.find('[data-popup1200-name]').trigger('focus');
  }

  function duplicateRow(row){
    var old=rowIndex(row);
    var next=uniqueIndex();
    var clone=row.clone(false,false);
    replaceIndex(clone,old,next);
    clone.removeClass('is-template');
    clone.find('[data-popup1200-id]').val('');
    clone.find('[data-popup1200-enabled]').prop('checked',false);
    clone.find('[data-popup1200-published]').val('0');
    var name=$.trim(clone.find('[data-popup1200-name]').val()||'Nouveau pop-up');
    clone.find('[data-popup1200-name]').val(name+' — copie');
    row.after(clone);
    syncRow(clone);
    clone.find('[data-popup1200-name]').trigger('focus');
  }

  function chooseMedia(button){
    var media=button.closest('[data-popup1200-media]');
    var frame=wp.media({
      title:'Choisir une image pour le pop-up',
      button:{text:'Utiliser cette image'},
      multiple:false,
      library:{type:'image'}
    });
    frame.on('select',function(){
      var item=frame.state().get('selection').first().toJSON();
      media.find('[data-popup1200-image-id]').val(item.id||0);
      media.find('[data-popup1200-legacy-url]').val('');
      updatePreview(media,item.url||'');
    });
    frame.open();
  }

  function clearMedia(button){
    var media=button.closest('[data-popup1200-media]');
    media.find('[data-popup1200-image-id]').val('0');
    media.find('[data-popup1200-legacy-url]').val('');
    updatePreview(media,'');
  }

  function popupWidth(row){
    var size=String(row.find('[data-popup1200-size]').val()||'medium');
    if(size==='small') return 480;
    if(size==='large') return 800;
    if(size==='custom'){
      var value=parseInt(row.find('[data-popup1200-custom-width] input').val()||'620',10);
      return Math.max(320,Math.min(1200,value||620));
    }
    return 620;
  }

  function closePreview(){
    $('.htp-1200-preview-modal').remove();
    $(document).off('keydown.popup1200preview');
  }

  function previewRow(row){
    var lang=String(row.find('[data-popup1200-preview-lang]').val()||'fr');
    var field=row.find('[data-popup1200-lang="'+lang+'"]');
    var src=field.find('[data-popup1200-media-preview] img').attr('src')||'';
    if(!src){
      window.alert('Aucune image n’est configurée pour cette langue.');
      return;
    }
    closePreview();
    var overlay=$('<div>',{class:'htp-1200-preview-modal'});
    var dialog=$('<div>',{class:'htp-1200-preview-dialog'}).css('--popup-preview-width',popupWidth(row)+'px');
    var close=$('<button>',{type:'button',class:'htp-1200-preview-close','aria-label':'Fermer'}).text('×');
    var img=$('<img>',{src:src,alt:''});
    var link=$.trim(field.find('input[type="url"]').first().val()||'');
    if(link){
      $('<a>',{href:link,target:'_blank',rel:'noopener noreferrer'}).append(img).appendTo(dialog);
    }else{
      dialog.append(img);
    }
    dialog.append(close);
    overlay.append(dialog);
    $('body').append(overlay);
    close.trigger('focus');
    close.on('click',closePreview);
    overlay.on('click',function(e){if(e.target===overlay[0])closePreview();});
    $(document).on('keydown.popup1200preview',function(e){if(e.key==='Escape')closePreview();});
  }

  $(document).on('click','[data-popup1200-add]',addRow);
  $(document).on('click','[data-popup1200-remove]',function(){
    if(window.confirm('Supprimer ce pop-up ?')) $(this).closest('[data-popup1200-row]').remove();
  });
  $(document).on('click','[data-popup1200-duplicate]',function(){duplicateRow($(this).closest('[data-popup1200-row]'));});
  $(document).on('click','[data-popup1200-media-select]',function(){chooseMedia($(this));});
  $(document).on('click','[data-popup1200-media-clear]',function(){clearMedia($(this));});
  $(document).on('click','[data-popup1200-preview]',function(){previewRow($(this).closest('[data-popup1200-row]'));});
  $(document).on('input change','[data-popup1200-row] input,[data-popup1200-row] select',function(){syncRow($(this).closest('[data-popup1200-row]'));});

  cleanupLegacyPopupControls();
  syncAll();
})(jQuery);
