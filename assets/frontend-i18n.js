(function(){
'use strict';
var labels={
    fr:{prev:'Mois précédent',next:'Mois suivant'},
    en:{prev:'Previous month',next:'Next month'},
    de:{prev:'Vorheriger Monat',next:'Nächster Monat'}
};
function apply(){
    document.querySelectorAll('.parcs-ht-calendar[data-htp-lang]').forEach(function(calendar){
        var lang=calendar.getAttribute('data-htp-lang')||'fr';
        var d=labels[lang]||labels.fr;
        var prev=calendar.querySelector('[data-htp-prev]');
        var next=calendar.querySelector('[data-htp-next]');
        if(prev)prev.setAttribute('aria-label',d.prev);
        if(next)next.setAttribute('aria-label',d.next);
    });
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',apply);else apply();
})();
