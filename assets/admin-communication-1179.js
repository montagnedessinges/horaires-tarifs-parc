(function($){
  'use strict';

  var root=$('[data-htp-popup-1179]');
  if(!root.length)return;
  var list=root.find('[data-popup-list]');
  var template=root.find('[data-popup-template]');

  function checked(row,selector){return row.find(selector).is(':checked');}
  function value(row,selector){return $.trim(row.find(selector).val()||'');}

  function sync(row){
    var enabled=checked(row,'[data-popup-enabled]');
    row.find('[data-popup-details]').prop('hidden',!enabled);
    var showButton=checked(row,'[data-popup-button-enabled]');
    row.find('[data-popup-button-details]').prop('hidden',!showButton);
    var title=value(row,'[data-popup-title="fr"]')||value(row,'[data-popup-title="en"]')||value(row,'[data-popup-title="de"]')||'Pop-up sans titre';
    row.find('[data-popup-summary-title]').text(title);
    var published=row.find('[data-popup-published]').val()==='1';
    row.find('[data-popup-summary-state]').text(enabled?(published?'Actif · publié':'Actif · brouillon'):'Désactivé');
  }

  function color(name,fallback){
    var field=root.find('[name="settings[general]['+name+']"]');
    return field.length&&field.val()?field.val():fallback;
  }

  function opacityColor(hex,opacity){
    hex=String(hex||'').replace('#','');
    if(!/^[0-9a-f]{6}$/i.test(hex))return 'rgba(0,0,0,.68)';
    var r=parseInt(hex.slice(0,2),16),g=parseInt(hex.slice(2,4),16),b=parseInt(hex.slice(4,6),16);
    return 'rgba('+r+','+g+','+b+','+Math.max(0,Math.min(100,parseInt(opacity,10)||0))/100+')';
  }

  function ensureModal(){
    var modal=$('#htp-1179-preview-modal');
    if(modal.length)return modal;
    modal=$('<div id="htp-1179-preview-modal" class="htp-1179-preview-modal" hidden><div class="htp-1179-preview-overlay" data-popup-preview-close></div><div class="htp-1179-preview-dialog" role="dialog" aria-modal="true"><button type="button" class="htp-1179-preview-close" data-popup-preview-close aria-label="Fermer">×</button><h2></h2><p></p><a href="#" target="_blank" rel="noopener"></a></div></div>');
    $('body').append(modal);
    return modal;
  }

  function preview(row){
    var lang=row.find('[data-popup-preview-language]').val()||'fr';
    var title=value(row,'[data-popup-title="'+lang+'"]')||value(row,'[data-popup-title="fr"]')||'Aperçu du pop-up';
    var message=value(row,'[data-popup-message="'+lang+'"]')||value(row,'[data-popup-message="fr"]');
    var buttonLabel=value(row,'[data-popup-button-label="'+lang+'"]')||value(row,'[data-popup-button-label="fr"]');
    var showButton=checked(row,'[data-popup-button-enabled]')&&buttonLabel!=='';
    var modal=ensureModal(),dialog=modal.find('.htp-1179-preview-dialog');
    dialog.css({
      background:color('alert_bg_color','#006757'),
      color:color('alert_text_color','#ffffff'),
      borderColor:color('alert_border_color','#ef7b5b'),
      borderWidth:(parseInt(root.find('[name="settings[general][alert_border_width]"]').val(),10)||0)+'px',
      borderRadius:(parseInt(root.find('[name="settings[general][alert_radius]"]').val(),10)||0)+'px'
    });
    modal.find('.htp-1179-preview-overlay').css('background',opacityColor(color('alert_overlay_color','#000000'),root.find('[name="settings[general][alert_overlay_opacity]"]').val()));
    dialog.find('h2').text(title).css('color',color('alert_title_color','#ffffff'));
    dialog.find('p').text(message).css('color',color('alert_text_color','#ffffff'));
    dialog.find('a').text(buttonLabel).toggle(showButton).css({background:color('alert_button_bg_color','#ef7b5b'),color:color('alert_button_text_color','#ffffff'),borderColor:color('alert_button_border_color','#ef7b5b')});
    dialog.find('.htp-1179-preview-close').css({background:color('alert_close_bg_color','#ffffff'),color:color('alert_close_text_color','#222222')});
    modal.prop('hidden',false);
  }

  root.on('click','[data-popup-add]',function(){
    var index='new_'+Date.now();
    var html=template.html().split('__INDEX__').join(index);
    var row=$(html);
    list.append(row);
    sync(row);
    row.find('[data-popup-enabled]').trigger('focus');
  });

  root.on('click','[data-popup-remove]',function(){
    var row=$(this).closest('[data-popup-row]');
    var title=$.trim(row.find('[data-popup-summary-title]').text())||'ce pop-up';
    if(window.confirm('Supprimer « '+title+' » ?'))row.remove();
  });

  root.on('change input','[data-popup-enabled],[data-popup-published],[data-popup-button-enabled],[data-popup-title]',function(){sync($(this).closest('[data-popup-row]'));});
  root.on('click','[data-popup-preview]',function(){preview($(this).closest('[data-popup-row]'));});
  $(document).on('click','[data-popup-preview-close]',function(){$('#htp-1179-preview-modal').prop('hidden',true);});
  $(document).on('keydown',function(event){if(event.key==='Escape')$('#htp-1179-preview-modal').prop('hidden',true);});

  list.find('[data-popup-row]').each(function(){sync($(this));});
})(jQuery);
