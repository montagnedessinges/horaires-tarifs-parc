(function(){
'use strict';
var labels={
    fr:{prev:'Mois précédent',next:'Mois suivant'},
    en:{prev:'Previous month',next:'Next month'},
    de:{prev:'Vorheriger Monat',next:'Nächster Monat'}
};
function meta(node,values){
    if(!node||!node.setAttribute)return;
    Object.keys(values||{}).forEach(function(key){
        var value=values[key];
        if(value===undefined||value===null||value==='')return;
        node.setAttribute('data-ga-'+key.replace(/_/g,'-'),String(value));
    });
}
function lang(node){
    var root=node&&node.closest?node.closest('[data-htp-lang]'):null;
    return root&&root.getAttribute('data-htp-lang')?root.getAttribute('data-htp-lang'):'fr';
}
function season(){
    var payload=window.ParcsHTPData||{},settings=payload.settings||{};
    return String(settings.activeSeasonYear||(settings.general||{}).year||'');
}
function quoteType(value){
    var normalized=String(value||'').toLowerCase();
    if(normalized.normalize)normalized=normalized.normalize('NFD').replace(/[\u0300-\u036f]/g,'');
    if(normalized.indexOf('handicap')!==-1||normalized.indexOf('disab')!==-1||normalized.indexOf('behinder')!==-1)return 'disability_group';
    return normalized?'standard_group':'unknown';
}
function numberValue(form,name){
    var field=form?form.querySelector('[name="'+name+'"]'):null;
    var value=field?Number(String(field.value||'').replace(',','.')):0;
    return isFinite(value)&&value>0?value:0;
}
function groupSize(form,type){
    var total=type==='disability_group'?numberValue(form,'nbrpersohandicape')+numberValue(form,'nbraccompa'):numberValue(form,'nbrenfants')+numberValue(form,'nbradultes');
    if(total<=0)return 'unknown';
    if(total<=20)return '1_20';
    if(total<=50)return '21_50';
    if(total<=100)return '51_100';
    return '101_plus';
}
function apply(){
    document.querySelectorAll('.parcs-ht-calendar[data-htp-lang]').forEach(function(calendar){
        var language=calendar.getAttribute('data-htp-lang')||'fr';
        var d=labels[language]||labels.fr;
        var prev=calendar.querySelector('[data-htp-prev]');
        var next=calendar.querySelector('[data-htp-next]');
        if(prev)prev.setAttribute('aria-label',d.prev);
        if(next)next.setAttribute('aria-label',d.next);
        calendar.querySelectorAll('[data-htp-date]').forEach(function(day){
            var date=day.getAttribute('data-htp-date')||'';
            meta(day,{event:'calendar_date_select',module:'calendar',content_language:language,season_year:date.slice(0,4),selected_month:date.slice(0,7),day_status:day.classList.contains('is-open')?'open':'closed',has_event:day.classList.contains('is-special-event')?'yes':'no'});
        });
        calendar.querySelectorAll('.parcs-ht-event-link').forEach(function(link){
            var selected=calendar.querySelector('[data-htp-date].is-selected');
            var date=selected?selected.getAttribute('data-htp-date')||'':'';
            var note=link.closest('.parcs-ht-event-note');
            meta(link,{event:'event_cta_click',module:'calendar',content_type:note&&note.classList.contains('is-period')?'period':'event',source:'calendar',content_language:language,season_year:date.slice(0,4)||season(),selected_month:date.slice(0,7)});
        });
        calendar.querySelectorAll('.parcs-ht-schedule-export-button').forEach(function(link){
            meta(link,{event:'document_download',module:'calendar',document_type:'schedule',season_year:season(),content_language:language});
        });
    });
    document.querySelectorAll('.parcs-ht-tariffs').forEach(function(root){
        root.querySelectorAll('[data-htp-tariff-tab]').forEach(function(tab){
            meta(tab,{event:'tariff_section_select',module:'tariffs',tariff_section:tab.getAttribute('data-htp-tariff-tab')||'',season_year:season(),content_language:lang(tab)});
        });
        root.querySelectorAll('.parcs-ht-tariff-export-button').forEach(function(link){
            meta(link,{event:'document_download',module:'tariffs',document_type:'tariffs',season_year:season(),content_language:lang(link)});
        });
        root.querySelectorAll('.parcs-ht-actions .parcs-ht-button.is-primary').forEach(function(link){
            meta(link,{event:'ticket_cta_click',module:'tariffs',source:'main_tariffs',season_year:season(),content_language:lang(link)});
        });
        root.querySelectorAll('.parcs-ht-special-buy').forEach(function(link){
            meta(link,{event:'special_offer_click',module:'tariffs',content_type:'offer',source:link.closest('.parcs-ht-group-tariffs-only')?'group_tariffs':'main_tariffs',season_year:season(),content_language:lang(link)});
        });
        root.querySelectorAll('.parcs-ht-group-tariffs-only .parcs-ht-panel-actions .parcs-ht-button,.parcs-ht-tariff-panel[id$="-panel-groups"] .parcs-ht-panel-actions .parcs-ht-button').forEach(function(link){
            meta(link,{event:'quote_cta_click',module:'group_quote',source:link.closest('.parcs-ht-group-tariffs-only')?'group_tariffs':'main_tariffs',season_year:season(),content_language:lang(link)});
        });
    });
    document.querySelectorAll('.parcs-ht-quote').forEach(function(root){
        var language=root.getAttribute('data-htp-lang')||'fr';
        var accessDate=root.querySelector('.parcs-ht-quote-access-date');
        var form=root.querySelector('.wpcf7 form,form.wpcf7-form');
        var visit=form?form.querySelector('[name="visite"]'):null;
        var group=form?form.querySelector('[name="groupedevis"]'):null;
        var type=quoteType(group?group.value:'');
        var size=form?groupSize(form,type):'unknown';
        var date=(visit&&visit.value)||(accessDate&&accessDate.value)||'';
        var common={module:'group_quote',quote_type:type,group_size:size,visit_year:date.slice(0,4),visit_month:date.slice(0,7),content_language:language};
        meta(root,common);
        if(accessDate)meta(accessDate,Object.assign({event:'quote_date_selected'},common));
        var formWrap=root.querySelector('.parcs-ht-quote-form');
        if(formWrap)meta(formWrap,Object.assign({view_event:'quote_form_open'},common));
        if(form)meta(form,Object.assign({success_event:'generate_lead',source:'quote_form'},common));
    });
}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',apply);else apply();
document.addEventListener('change',apply,true);
new MutationObserver(apply).observe(document.documentElement,{childList:true,subtree:true});
})();
