(function(){
  'use strict';

  function qs(root,sel){return (root||document).querySelector(sel);}
  function qsa(root,sel){return Array.prototype.slice.call((root||document).querySelectorAll(sel));}
  function suffix(el,s){return el&&el.name&&el.name.slice(-s.length)===s;}
  function field(row,s){return qsa(row,'[name]').find(function(el){return suffix(el,s);})||null;}
  function esc(v){return String(v||'').replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];});}
  function activeLangs(){var box=qs(document,'[data-htp-public-languages]');if(!box)return ['fr'];var out=['fr'];qsa(box,'input[name="parcs_ht_languages[]"]:checked').forEach(function(el){if(out.indexOf(el.value)===-1)out.push(el.value);});return out;}

  function enhanceColumns(){
    qsa(document,'[data-htp-tariff-column]').forEach(function(row){
      if(qs(row,'[data-htp-column-visible]'))return;
      var id=qs(row,'[data-htp-column-id-input]');if(!id||!id.name)return;
      var base=id.name.replace(/\[id\]$/,'');
      var label=document.createElement('label');label.className='htp-field';label.setAttribute('data-htp-column-visible','1');
      label.innerHTML='<span>Affichage public</span><input type="hidden" name="'+esc(base)+'[visible]" value="0"><span><input type="checkbox" name="'+esc(base)+'[visible]" value="1" checked> Afficher cette colonne</span>';
      row.appendChild(label);
    });
  }

  function translateFields(base,label,type){
    var html='<div class="htp-field htp-tariff-offer-translations"><span>'+esc(label)+'</span><div class="htp-grid htp-grid-3">';
    ['fr','en','de'].forEach(function(lang){html+='<label><small>'+lang.toUpperCase()+'</small>'+(type==='textarea'?'<textarea rows="2" name="'+esc(base)+'['+lang+']"></textarea>':'<input type="text" name="'+esc(base)+'['+lang+']">')+'</label>';});
    return html+'</div></div>';
  }

  function enhanceSpecialRows(){
    qsa(document,'[data-htp-tariff-row]').forEach(function(row){
      var type=field(row,'[row_type]');if(!type)return;
      var settings=qs(row,'[data-htp-special-offer-settings]');if(!settings||qs(settings,'[data-htp-offer-advanced]'))return;
      var any=qs(row,'[name*="[tariffs]"]');if(!any||!any.name)return;
      var m=any.name.match(/^(settings\[tariffs\]\[(?:individual|reduced|groups)\]\[[^\]]+\])/);if(!m)return;
      var base=m[1];
      qsa(settings,'.htp-field > span').forEach(function(span){
        if(span.textContent.indexOf('Afficher l’offre à partir')!==-1)span.textContent='Début de vente / affichage';
        if(span.textContent.indexOf('Masquer l’offre après')!==-1)span.textContent='Fin de vente / affichage';
      });
      var wrap=document.createElement('div');wrap.className='htp-offer-advanced';wrap.setAttribute('data-htp-offer-advanced','1');
      wrap.innerHTML='<hr><h4>Regrouper plusieurs billets dans une même offre</h4><p class="description">Saisissez le même repère interne sur plusieurs lignes (ex. « Billets Juin »). Ce repère est uniquement interne et reste en français.</p><label class="htp-field"><span>Repère interne de l’offre (FR)</span><input type="text" name="'+esc(base)+'[offer_group]" placeholder="Ex. Billets Juin"></label><div class="htp-check-list"><label><input type="hidden" name="'+esc(base)+'[offer_popup]" value="0"><input type="checkbox" name="'+esc(base)+'[offer_popup]" value="1" data-htp-offer-popup-toggle> Lier cette offre à un pop-up</label></div><div data-htp-offer-popup-fields hidden>'+translateFields(base+'[offer_popup_title]','Titre public du pop-up','text')+translateFields(base+'[offer_popup_message]','Message public du pop-up','textarea')+translateFields(base+'[offer_popup_button_label]','Texte du bouton','text')+translateFields(base+'[offer_popup_button_url]','Lien du bouton','text')+'</div>';
      settings.appendChild(wrap);
      var toggle=qs(wrap,'[data-htp-offer-popup-toggle]'),fields=qs(wrap,'[data-htp-offer-popup-fields]');
      if(toggle&&fields)toggle.addEventListener('change',function(){fields.hidden=!toggle.checked;});
    });
  }

  function value(row,s){var el=field(row,s);return el?String(el.value||'').trim():'';}
  function checked(row,s){var el=field(row,s);return !!(el&&el.checked);}
  function translation(row,s,lang){return value(row,s+'['+lang+']');}

  function previewTariffs(){
    var section=qs(document,'#htp-preview');if(!section)return;
    var result=qs(section,'[data-htp-preview-result]');if(!result)return;
    var card=qs(section,'[data-htp-tariff-preview]');
    if(!card){card=document.createElement('div');card.className='htp-preview-result htp-tariff-preview';card.setAttribute('data-htp-tariff-preview','1');result.insertAdjacentElement('afterend',card);}
    var dateInput=qs(section,'[data-htp-preview-date]');var date=dateInput&&dateInput.value?dateInput.value:'';
    var lang=(activeLangs()[0]||'fr');
    var html='<h3>Aperçu des tarifs'+(date?' au '+esc(date):'')+'</h3><p class="description">Cet aperçu utilise aussi les données de la saison brouillon ouverte dans l’administration. Rien n’est publié tant que la saison reste en brouillon.</p>';
    qsa(document,'[data-htp-tariff-group]').forEach(function(group){
      var key=group.getAttribute('data-htp-tariff-group')||'';var title=qs(group,'h3');
      var cols=qsa(group,'[data-htp-tariff-column]').filter(function(c){var cb=qs(c,'[data-htp-column-visible] input[type="checkbox"]');return !cb||cb.checked;}).map(function(c){var l=qs(c,'input[name$="[label][fr]"]');return l?l.value:'Tarif';});
      var rows=[];
      qsa(group,'[data-htp-tariff-row]').forEach(function(row){
        var enabled=qs(row,'input[type="checkbox"][name$="[enabled]"]');if(enabled&&!enabled.checked)return;
        var special=value(row,'[row_type]')==='special';
        if(special&&date){var from=value(row,'[display_from]'),to=value(row,'[display_to]');if(from&&date<from)return;if(to&&date>to)return;}
        var label=translation(row,'[label]',lang)||translation(row,'[label]','fr')||'Tarif';
        var offer=special?value(row,'[offer_group]'):'';
        var cells=qsa(row,'[data-htp-tariff-cell]').filter(function(cell){var cid=cell.getAttribute('data-col-id');return cols.length===0||qsa(group,'[data-htp-tariff-column]').filter(function(c){var cb=qs(c,'[data-htp-column-visible] input[type="checkbox"]');return (!cb||cb.checked)&&c.getAttribute('data-col-id')===cid;}).length>0;}).map(function(cell){var i=qs(cell,'input[name$="[value]"]');return i?i.value:'';});
        rows.push({label:label,offer:offer,cells:cells,special:special,from:value(row,'[display_from]'),to:value(row,'[display_to]')});
      });
      if(!rows.length)return;
      html+='<div class="htp-preview-tariff-group"><h4>'+esc(title?title.textContent:key)+'</h4>';
      var lastOffer=null;
      rows.forEach(function(r){if(r.offer&&r.offer!==lastOffer){html+='<div class="htp-preview-offer-title">'+esc(r.offer)+'</div>';lastOffer=r.offer;}if(!r.offer)lastOffer=null;html+='<div class="htp-preview-tariff-row"><strong>'+esc(r.label)+'</strong><span>'+r.cells.map(esc).join(' · ')+'</span>'+(r.special&&r.from?'<small>Vente : '+esc(r.from)+(r.to?' → '+esc(r.to):'')+'</small>':'')+'</div>';});
      html+='</div>';
    });
    card.innerHTML=html;
  }

  function bindPreview(){var section=qs(document,'#htp-preview');if(!section)return;var button=qs(section,'[data-htp-preview-button]');if(button)button.addEventListener('click',function(){setTimeout(previewTariffs,0);});var date=qs(section,'[data-htp-preview-date]');if(date)date.addEventListener('change',previewTariffs);previewTariffs();}

  function boot(){enhanceColumns();enhanceSpecialRows();bindPreview();var root=qs(document,'#htp-tariffs');if(root){new MutationObserver(function(){enhanceColumns();enhanceSpecialRows();}).observe(root,{childList:true,subtree:true});}}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
}());
