(function(){
  'use strict';

  var config=window.ParcsHTPopup1200||{};
  if(!config.endpoint) return;

  var lang=String(config.language||'fr').slice(0,2).toLowerCase();
  if(['fr','en','de'].indexOf(lang)===-1) lang='fr';

  function storageKey(popup){
    return 'parcs_ht_popup1200_'+String(popup.id||'popup')+'_'+lang;
  }

  function isSuppressed(popup){
    var key=storageKey(popup);
    try{
      var raw=window.localStorage.getItem(key);
      if(!raw) return false;
      if(String(popup.reappearMode||'hours')==='once') return true;
      var last=parseInt(raw,10);
      if(!last) return false;
      var hours=Math.max(1,parseInt(popup.reappearHours||24,10));
      return (Date.now()-last)<hours*3600000;
    }catch(e){
      return false;
    }
  }

  function remember(popup){
    try{window.localStorage.setItem(storageKey(popup),String(Date.now()));}catch(e){}
  }

  function closeLabel(){
    if(lang==='en') return 'Close';
    if(lang==='de') return 'Schließen';
    return 'Fermer';
  }

  function dialogLabel(){
    if(lang==='en') return 'Information';
    if(lang==='de') return 'Information';
    return 'Information';
  }

  function show(popup){
    if(!popup||!popup.imageUrl) return;

    var previousFocus=document.activeElement;
    var previousOverflow=document.body.style.overflow;
    var overlay=document.createElement('div');
    overlay.className='parcs-ht-popup1200-overlay';
    overlay.setAttribute('role','presentation');

    var dialog=document.createElement('div');
    dialog.className='parcs-ht-popup1200-dialog';
    dialog.style.setProperty('--parcs-ht-popup1200-width',Math.max(320,Math.min(1200,parseInt(popup.width||620,10)))+'px');
    dialog.setAttribute('role','dialog');
    dialog.setAttribute('aria-modal','true');
    dialog.setAttribute('aria-label',popup.alt||dialogLabel());

    var close=document.createElement('button');
    close.type='button';
    close.className='parcs-ht-popup1200-close';
    close.setAttribute('aria-label',closeLabel());
    close.textContent='×';

    var image=document.createElement('img');
    image.className='parcs-ht-popup1200-image';
    image.src=popup.imageUrl;
    image.alt=popup.alt||'';
    image.decoding='async';

    if(popup.linkUrl){
      var link=document.createElement('a');
      link.className='parcs-ht-popup1200-link';
      link.href=popup.linkUrl;
      link.appendChild(image);
      link.addEventListener('click',function(){remember(popup);});
      dialog.appendChild(link);
    }else{
      dialog.appendChild(image);
    }

    dialog.appendChild(close);
    overlay.appendChild(dialog);
    document.body.appendChild(overlay);
    document.body.style.overflow='hidden';

    function dismiss(){
      remember(popup);
      document.removeEventListener('keydown',trap);
      if(overlay.parentNode) overlay.parentNode.removeChild(overlay);
      document.body.style.overflow=previousOverflow;
      if(previousFocus&&typeof previousFocus.focus==='function'){
        try{previousFocus.focus();}catch(e){}
      }
    }

    function trap(event){
      if(event.key==='Escape'){
        event.preventDefault();
        dismiss();
        return;
      }
      if(event.key!=='Tab') return;
      var focusables=dialog.querySelectorAll('a[href],button:not([disabled]),[tabindex]:not([tabindex="-1"])');
      if(!focusables.length){
        event.preventDefault();
        close.focus();
        return;
      }
      var first=focusables[0];
      var last=focusables[focusables.length-1];
      if(event.shiftKey&&document.activeElement===first){
        event.preventDefault();
        last.focus();
      }else if(!event.shiftKey&&document.activeElement===last){
        event.preventDefault();
        first.focus();
      }
    }

    close.addEventListener('click',dismiss);
    overlay.addEventListener('click',function(event){if(event.target===overlay)dismiss();});
    image.addEventListener('error',function(){
      if(overlay.parentNode) overlay.parentNode.removeChild(overlay);
      document.body.style.overflow=previousOverflow;
    });
    document.addEventListener('keydown',trap);
    close.focus();
  }

  function selectPopup(rows){
    for(var i=0;i<rows.length;i++){
      if(!isSuppressed(rows[i])) return rows[i];
    }
    return null;
  }

  var separator=config.endpoint.indexOf('?')===-1?'?':'&';
  var url=config.endpoint+separator+'lang='+encodeURIComponent(lang)+'&_='+Date.now();

  fetch(url,{
    credentials:'same-origin',
    cache:'no-store',
    headers:{'Accept':'application/json'}
  })
    .then(function(response){if(!response.ok)throw new Error('popup request failed');return response.json();})
    .then(function(data){
      var rows=data&&Array.isArray(data.popups)?data.popups:[];
      var popup=selectPopup(rows);
      if(popup) show(popup);
    })
    .catch(function(){});
})();