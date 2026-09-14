(function(){
  'use strict';

  var config=window.ParcsHTAdvent||{};

  function meta(node,values){
    if(!node||!node.setAttribute)return;
    Object.keys(values||{}).forEach(function(key){
      var value=values[key];
      if(value===undefined||value===null||value==='')return;
      node.setAttribute('data-ga-'+key.replace(/_/g,'-'),String(value));
    });
  }

  function appendPreview(form){
    if(!config.previewNonce)return;
    form.append('preview_nonce',String(config.previewNonce));
    form.append('preview_date',String(config.previewDate||''));
    form.append('preview_time',String(config.previewTime||''));
  }

  function post(action,data){
    var form=new FormData();
    form.append('action',action);
    form.append('nonce',String(config.nonce||''));
    Object.keys(data||{}).forEach(function(key){form.append(key,String(data[key] == null ? '' : data[key]));});
    appendPreview(form);
    return fetch(String(config.ajaxUrl||''),{
      method:'POST',
      credentials:'same-origin',
      body:form
    }).then(function(response){
      return response.json().catch(function(){return null;}).then(function(payload){
        if(!response.ok||!payload||payload.success!==true){
          var message=payload&&payload.data&&payload.data.message?payload.data.message:'Impossible de charger ce contenu pour le moment.';
          throw new Error(message);
        }
        return payload.data||{};
      });
    });
  }

  function setBusy(root,busy){
    root.classList.toggle('is-loading',!!busy);
    root.querySelectorAll('[data-advent-day]').forEach(function(button){
      if(button.dataset.originalDisabled===undefined)button.dataset.originalDisabled=button.disabled?'1':'0';
      button.disabled=!!busy||button.dataset.originalDisabled==='1';
    });
  }

  function bindParticipation(root){
    var button=root.querySelector('[data-advent-participation-toggle]');
    var panel=root.querySelector('[data-advent-participation-panel]');
    if(!button||!panel)return;
    button.addEventListener('click',function(){
      var open=button.getAttribute('aria-expanded')==='true';
      button.setAttribute('aria-expanded',open?'false':'true');
      panel.hidden=open;
    });
  }

  function imageDialog(img,label){
    var overlay=document.createElement('div');
    overlay.className='parcs-ht-advent-lightbox';
    overlay.setAttribute('role','dialog');
    overlay.setAttribute('aria-modal','true');
    overlay.setAttribute('aria-label',label||'Agrandir le visuel');
    overlay.innerHTML='<button type="button" class="parcs-ht-advent-lightbox-close" aria-label="Fermer">×</button><div class="parcs-ht-advent-lightbox-inner"></div>';
    var clone=img.cloneNode(true);
    clone.removeAttribute('loading');
    overlay.querySelector('.parcs-ht-advent-lightbox-inner').appendChild(clone);
    document.body.appendChild(overlay);
    var close=overlay.querySelector('.parcs-ht-advent-lightbox-close');
    var previous=document.activeElement;
    function dismiss(){
      document.removeEventListener('keydown',keyHandler);
      overlay.remove();
      if(previous&&typeof previous.focus==='function')previous.focus();
    }
    function keyHandler(event){if(event.key==='Escape')dismiss();}
    close.addEventListener('click',dismiss);
    overlay.addEventListener('click',function(event){if(event.target===overlay)dismiss();});
    document.addEventListener('keydown',keyHandler);
    close.focus();
  }

  function tagDetail(root){
    var campaign=root.getAttribute('data-campaign-id')||'';
    var language=root.getAttribute('data-language')||'fr';
    var selected=root.querySelector('[data-advent-day].is-selected');
    var contentId=selected?selected.getAttribute('data-content-id')||'':'';

    root.querySelectorAll('.parcs-ht-advent-social-links a').forEach(function(link){
      var label=String(link.textContent||'').toLowerCase();
      meta(link,{event:'advent_social_click',module:'advent',platform:label.indexOf('instagram')!==-1?'instagram':(label.indexOf('facebook')!==-1?'facebook':'social'),campaign_id:campaign,content_id:contentId,content_language:language});
    });
    root.querySelectorAll('[data-advent-word-form]').forEach(function(form){
      meta(form,{submit_event:'advent_word_attempt',module:'advent',campaign_id:campaign,content_language:language});
    });
    root.querySelectorAll('.parcs-ht-advent-final.is-authorized').forEach(function(success){
      meta(success,{view_event:'advent_word_result',module:'advent',result:'success',campaign_id:campaign,content_language:language});
    });
    root.querySelectorAll('[data-advent-word-message]').forEach(function(message){
      if(!String(message.textContent||'').trim())return;
      meta(message,{view_event:'advent_word_result',module:'advent',result:'failure',campaign_id:campaign,content_language:language});
    });
    root.querySelectorAll('.parcs-ht-advent-final-form .wpcf7 form,.parcs-ht-advent-final-form form.wpcf7-form').forEach(function(form){
      meta(form,{success_event:'advent_entry_submit',module:'advent',campaign_id:campaign,content_language:language});
    });
  }

  function bindDetail(root){
    tagDetail(root);
    root.querySelectorAll('[data-advent-expand-image]').forEach(function(button){
      if(button.dataset.adventBound==='1')return;
      button.dataset.adventBound='1';
      button.addEventListener('click',function(){
        var img=button.querySelector('img');
        if(img)imageDialog(img,button.getAttribute('aria-label')||'');
      });
    });

    root.querySelectorAll('[data-advent-word-form]').forEach(function(form){
      if(form.dataset.adventBound==='1')return;
      form.dataset.adventBound='1';
      form.addEventListener('submit',function(event){
        event.preventDefault();
        var final=form.closest('[data-advent-final]');
        var message=final?final.querySelector('[data-advent-word-message]'):null;
        var input=form.querySelector('input[name="word"]');
        if(!input)return;
        var submit=form.querySelector('button[type="submit"]');
        if(submit)submit.disabled=true;
        if(message)message.textContent='';
        post(String(config.wordAction||'parcs_ht_advent_validate_word'),{
          campaign_id:root.getAttribute('data-campaign-id')||'',
          language:root.getAttribute('data-language')||'fr',
          word:input.value||''
        }).then(function(data){
          if(!final)return;
          final.outerHTML=String(data.html||'');
          bindDetail(root);
        }).catch(function(error){
          if(message)message.textContent=error.message;
          if(submit)submit.disabled=false;
          tagDetail(root);
        });
      });
    });
  }

  function bindDays(root){
    var detail=root.querySelector('[data-advent-detail]');
    if(!detail)return;
    var campaign=root.getAttribute('data-campaign-id')||'';
    var language=root.getAttribute('data-language')||'fr';
    root.querySelectorAll('[data-advent-day]').forEach(function(button){
      var match=String(button.textContent||'').match(/\d{1,2}/);
      meta(button,{event:'advent_day_open',module:'advent',campaign_id:campaign,content_id:button.getAttribute('data-content-id')||'',day_number:match?Number(match[0]):'',content_language:language});
      if(button.dataset.adventBound==='1')return;
      button.dataset.adventBound='1';
      button.addEventListener('click',function(){
        if(button.disabled)return;
        setBusy(root,true);
        root.querySelectorAll('[data-advent-day]').forEach(function(item){
          item.classList.toggle('is-selected',item===button);
          item.setAttribute('aria-pressed',item===button?'true':'false');
        });
        detail.setAttribute('aria-busy','true');
        post(String(config.dayAction||'parcs_ht_advent_day'),{
          campaign_id:root.getAttribute('data-campaign-id')||'',
          content_id:button.getAttribute('data-content-id')||'',
          language:root.getAttribute('data-language')||'fr'
        }).then(function(data){
          detail.innerHTML=String(data.html||'');
          detail.removeAttribute('aria-busy');
          setBusy(root,false);
          bindDetail(root);
          var heading=detail.querySelector('h3,.parcs-ht-advent-day-kicker');
          if(heading){heading.setAttribute('tabindex','-1');heading.focus({preventScroll:true});}
          detail.scrollIntoView({behavior:'smooth',block:'nearest'});
        }).catch(function(error){
          detail.innerHTML='<p class="parcs-ht-advent-error"></p>';
          var errorNode=detail.querySelector('.parcs-ht-advent-error');
          if(errorNode)errorNode.textContent=error.message;
          detail.removeAttribute('aria-busy');
          setBusy(root,false);
        });
      });
    });
  }

  function initRoot(root){
    if(root.dataset.adventInitialized==='1')return;
    root.dataset.adventInitialized='1';
    meta(root,{module:'advent',campaign_id:root.getAttribute('data-campaign-id')||'',content_language:root.getAttribute('data-language')||'fr'});
    bindParticipation(root);
    bindDays(root);
    bindDetail(root);
  }

  function init(){document.querySelectorAll('[data-advent-root]').forEach(initRoot);}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
}());
