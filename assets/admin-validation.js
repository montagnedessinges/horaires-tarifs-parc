(function(){
  'use strict';
  var config=window.ParcsHTValidation||{},active=Array.isArray(config.languages)?config.languages:['fr','en','de'];
  if(active.indexOf('fr')===-1)active.unshift('fr');

  function addLanguageSettings(){
    var general=document.getElementById('htp-general');if(!general||general.querySelector('[data-htp-public-languages]'))return;
    var box=document.createElement('div');box.className='htp-public-languages';box.setAttribute('data-htp-public-languages','1');
    box.innerHTML='<h3>Langues publiques actives</h3><p class="description">Le repère interne reste uniquement en français. Les contenus publics et leurs traductions restent facultatifs.</p><input type="hidden" name="parcs_ht_languages_present" value="1"><label style="margin-right:18px"><input type="checkbox" checked disabled> Français</label><input type="hidden" name="parcs_ht_languages[]" value="fr"><label style="margin-right:18px"><input type="checkbox" name="parcs_ht_languages[]" value="en" '+(active.indexOf('en')!==-1?'checked':'')+'> Anglais</label><label><input type="checkbox" name="parcs_ht_languages[]" value="de" '+(active.indexOf('de')!==-1?'checked':'')+'> Allemand</label>';
    var first=general.querySelector('.htp-grid');if(first&&first.parentNode)first.parentNode.insertBefore(box,first);else general.appendChild(box);
  }
  function fieldBySuffix(row,suffix){return row.querySelector('[name$="'+suffix+'"]');}
  function rowTitleField(row,lang){return fieldBySuffix(row,'[title]['+lang+']');}
  function rowInternalField(row){return fieldBySuffix(row,'[internal_label]')||fieldBySuffix(row,'[label]');}
  function rowLabel(row,index){var internal=rowInternalField(row),title=rowTitleField(row,'fr'),value=internal?String(internal.value||'').trim():'';if(!value&&title)value=String(title.value||'').trim();var start=fieldBySuffix(row,'[start]'),end=fieldBySuffix(row,'[end]');var dates=(start&&start.value?start.value:'')+(end&&end.value?' → '+end.value:'');return (value?'« '+value+' »':'ligne '+(index+1))+(dates?' ('+dates+')':'');}
  function isEnabled(row){var e=row.querySelector('input[type="checkbox"][name$="[enabled]"]');return !e||e.checked;}
  function clearErrors(){document.querySelectorAll('.htp-validation-error').forEach(function(el){el.classList.remove('htp-validation-error');el.removeAttribute('aria-invalid');});var old=document.querySelector('[data-htp-validation-summary]');if(old)old.remove();}
  function mark(field){if(!field)return;field.classList.add('htp-validation-error');field.setAttribute('aria-invalid','true');}
  function hasValue(field){return !!(field&&String(field.value||'').trim()!=='');}
  function validate(){clearErrors();var errors=[];
    ['htp-holidays','htp-exceptions'].forEach(function(sectionId){var section=document.getElementById(sectionId);if(!section)return;var rows=Array.prototype.slice.call(section.querySelectorAll('.htp-repeat-row'));rows.forEach(function(row,index){if(!isEnabled(row))return;var internal=rowInternalField(row);if(internal&&String(internal.value||'').trim()===''){errors.push((sectionId==='htp-exceptions'?'Exception ':'Période / événement ')+rowLabel(row,index)+' : repère interne FR obligatoire.');mark(internal);}});});
    document.querySelectorAll('#htp-regular .htp-repeat-row,#htp-exceptions .htp-repeat-row').forEach(function(row,index){
      if(!isEnabled(row))return;
      var o1=fieldBySuffix(row,'[open]'),c1=fieldBySuffix(row,'[close]'),o2=fieldBySuffix(row,'[open2]'),c2=fieldBySuffix(row,'[close2]');
      if(hasValue(o1)&&hasValue(c1)&&o1.value>=c1.value){errors.push('Créneau 1 invalide ligne '+(index+1)+'.');mark(o1);mark(c1);}
      var hasO2=hasValue(o2),hasC2=hasValue(c2);
      // Le créneau 2 est entièrement facultatif. Il n'est invalide que si un seul de ses deux champs est renseigné.
      if(hasO2!==hasC2){errors.push('Créneau 2 incomplet ligne '+(index+1)+'.');if(hasO2)mark(c2);else mark(o2);}
      if(hasO2&&hasC2&&o2.value>=c2.value){errors.push('Créneau 2 invalide ligne '+(index+1)+'.');mark(o2);mark(c2);}
      if(hasO2&&hasC2&&hasValue(c1)&&o2.value<c1.value){errors.push('Chevauchement entre créneau 1 et créneau 2 ligne '+(index+1)+'.');mark(o2);mark(c1);}
    });
    return errors;
  }
  function showSummary(errors){if(!errors.length)return;var wrap=document.querySelector('.htp-admin');if(!wrap)return;var div=document.createElement('div');div.className='notice notice-error';div.setAttribute('data-htp-validation-summary','1');div.innerHTML='<p><strong>Enregistrement bloqué : '+errors.length+' élément(s) à corriger.</strong></p><ul>'+errors.slice(0,15).map(function(e){return '<li>'+String(e).replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];})+'</li>';}).join('')+'</ul>';wrap.insertBefore(div,wrap.children[1]||null);div.scrollIntoView({behavior:'smooth',block:'start'});}
  function bindForm(){var form=document.querySelector('.htp-admin form[action*="admin-post.php"] input[name="action"][value="parcs_ht_save"]');form=form?form.closest('form'):null;if(!form)return;form.addEventListener('submit',function(event){var errors=validate();if(errors.length){event.preventDefault();event.stopImmediatePropagation();showSummary(errors);}},true);}
  function boot(){addLanguageSettings();bindForm();var style=document.createElement('style');style.textContent='.htp-public-languages{margin:16px 0;padding:14px;border:1px solid #dcdcde;border-radius:8px}.htp-validation-error{outline:2px solid #d63638!important;outline-offset:1px}';document.head.appendChild(style);}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
}());