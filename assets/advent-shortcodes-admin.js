(function(){
  'use strict';

  function appendRow(tbody,label,base){
    if(!tbody||tbody.querySelector('code[data-advent-shortcode="'+base+'"]'))return;
    var tr=document.createElement('tr');
    var th=document.createElement('th');
    th.textContent=label;
    tr.appendChild(th);
    ['', '_fr', '_en', '_de'].forEach(function(suffix){
      var td=document.createElement('td');
      var code=document.createElement('code');
      code.textContent='['+base+suffix+']';
      code.setAttribute('data-advent-shortcode',base);
      td.appendChild(code);
      tr.appendChild(td);
    });
    tbody.appendChild(tr);
  }

  function init(){
    var tbody=document.querySelector('#htp-shortcodes table tbody');
    if(!tbody)return;
    appendRow(tbody,'Calendrier de l’Avent','parc_calendrier_avent');
    appendRow(tbody,'Règlement du Calendrier de l’Avent','parc_reglement_avent');
  }

  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);
  else init();
})();
