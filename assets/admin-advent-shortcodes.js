(function(){
'use strict';
function init(){var section=document.getElementById('htp-shortcodes');if(!section||section.textContent.indexOf('[parc_calendrier_avent]')!==-1)return;var table=section.querySelector('table');var body=table&&table.querySelector('tbody');if(!body)return;var count=table.querySelectorAll('thead th').length;var row=document.createElement('tr');if(count>=5){row.innerHTML='<th>Calendrier de l’Avent</th><td><code>[parc_calendrier_avent]</code></td><td><code>[parc_calendrier_avent_fr]</code></td><td><code>[parc_calendrier_avent_en]</code></td><td><code>[parc_calendrier_avent_de]</code></td>';}else{row.innerHTML='<th>Calendrier de l’Avent</th><td><code>[parc_calendrier_avent_fr]</code></td><td><code>[parc_calendrier_avent_en]</code></td><td><code>[parc_calendrier_avent_de]</code></td>';}body.appendChild(row);}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
}());
